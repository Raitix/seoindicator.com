#!/usr/bin/env python3
import argparse
import json
import os
import posixpath
import shutil
import subprocess
import sys
import tempfile
import time
import uuid
from dataclasses import dataclass, field
from datetime import datetime, timezone
from pathlib import Path
from typing import Dict, List, Optional

import paramiko

PROJECT_ROOT = Path(__file__).resolve().parents[1]
WORKSPACE_ROOT = PROJECT_ROOT.parent
OPENCLAW_ROOT = WORKSPACE_ROOT.parent
SECRETS_FILE = OPENCLAW_ROOT / 'secrets' / 'seoindicator.env'
DEPLOYMENTS_DIR = OPENCLAW_ROOT / 'logs' / 'deployments'
LOCK_FILE = DEPLOYMENTS_DIR / 'deploy.lock'
PLAYWRIGHT_CONFIG = PROJECT_ROOT / 'frontend' / 'playwright.prod.config.ts'
PLAYWRIGHT_SPEC = PROJECT_ROOT / 'frontend' / 'e2e' / 'prod-deploy-smoke.spec.ts'


def now_iso() -> str:
    return datetime.now(timezone.utc).isoformat()


def read_env_file(path: Path) -> Dict[str, str]:
    result: Dict[str, str] = {}
    for raw_line in path.read_text(encoding='utf-8').splitlines():
        line = raw_line.strip()
        if not line or line.startswith('#') or '=' not in line:
            continue
        key, value = line.split('=', 1)
        result[key.strip()] = value.strip()
    return result


@dataclass
class StepRecord:
    name: str
    status: str = 'pending'
    startedAt: Optional[str] = None
    endedAt: Optional[str] = None
    details: str = ''


class DeploymentLog:
    def __init__(self, deployment_id: str, trigger: str, git_ref: str, branch: str):
        DEPLOYMENTS_DIR.mkdir(parents=True, exist_ok=True)
        self.path = DEPLOYMENTS_DIR / f'{deployment_id}.json'
        self.data = {
            'id': deployment_id,
            'trigger': trigger,
            'gitRef': git_ref,
            'branch': branch,
            'status': 'running',
            'startedAt': now_iso(),
            'endedAt': None,
            'durationMs': None,
            'summary': '',
            'projectRoot': str(PROJECT_ROOT),
            'productionUrl': 'https://seoindicator.com',
            'commit': None,
            'steps': [],
            'events': [],
        }
        self.write()

    def write(self):
        self.path.write_text(json.dumps(self.data, indent=2), encoding='utf-8')

    def add_event(self, level: str, message: str):
        self.data['events'].append({
            'ts': now_iso(),
            'level': level,
            'message': message,
        })
        self.write()

    def start_step(self, name: str):
        step = {
            'name': name,
            'status': 'running',
            'startedAt': now_iso(),
            'endedAt': None,
            'details': '',
        }
        self.data['steps'].append(step)
        self.write()

    def finish_step(self, name: str, status: str, details: str = ''):
        for step in reversed(self.data['steps']):
            if step['name'] == name and step['status'] == 'running':
                step['status'] = status
                step['endedAt'] = now_iso()
                if details:
                    step['details'] = details[:12000]
                break
        self.write()

    def set_commit(self, commit: str):
        self.data['commit'] = commit
        self.write()

    def finalize(self, status: str, summary: str):
        ended_at = now_iso()
        started_at = datetime.fromisoformat(self.data['startedAt'])
        ended_dt = datetime.fromisoformat(ended_at)
        duration_ms = int((ended_dt - started_at).total_seconds() * 1000)
        self.data['status'] = status
        self.data['endedAt'] = ended_at
        self.data['durationMs'] = duration_ms
        self.data['summary'] = summary
        self.write()


class DeployError(RuntimeError):
    pass


def command_name(name: str) -> str:
    if os.name == 'nt' and not name.lower().endswith(('.exe', '.cmd', '.bat')):
        if name in {'npm', 'npx'}:
            return f'{name}.cmd'
    return name


def run_local(command: List[str], cwd: Path, env: Optional[Dict[str, str]] = None, timeout: int = 3600) -> subprocess.CompletedProcess:
    merged_env = os.environ.copy()
    if env:
        merged_env.update(env)
    normalized_command = command[:]
    if normalized_command:
        normalized_command[0] = command_name(normalized_command[0])
    return subprocess.run(
        normalized_command,
        cwd=str(cwd),
        env=merged_env,
        text=True,
        encoding='utf-8',
        errors='replace',
        capture_output=True,
        timeout=timeout,
        check=False,
    )


def ensure_lock(deployment_id: str):
    DEPLOYMENTS_DIR.mkdir(parents=True, exist_ok=True)
    if LOCK_FILE.exists():
        try:
            data = json.loads(LOCK_FILE.read_text(encoding='utf-8'))
            raise DeployError(f"Another deployment is already running ({data.get('id', 'unknown')}).")
        except json.JSONDecodeError:
            raise DeployError('Another deployment is already running (lock file present).')
    LOCK_FILE.write_text(json.dumps({'id': deployment_id, 'startedAt': now_iso()}), encoding='utf-8')


def release_lock():
    if LOCK_FILE.exists():
        LOCK_FILE.unlink()


def shorten_output(text: str, limit: int = 12000) -> str:
    text = (text or '').strip()
    if len(text) <= limit:
        return text
    head = text[: limit // 2]
    tail = text[-limit // 2 :]
    return f"{head}\n\n... output truncated ...\n\n{tail}"


def upload_directory(sftp: paramiko.SFTPClient, local_dir: Path, remote_dir: str):
    def ensure_remote_dir(path: str):
        parts = path.strip('/').split('/')
        current = ''
        for part in parts:
            current = current + '/' + part
            try:
                sftp.stat(current)
            except IOError:
                sftp.mkdir(current)

    ensure_remote_dir(remote_dir)

    for root, dirs, files in os.walk(local_dir):
        rel_root = Path(root).relative_to(local_dir)
        remote_root = remote_dir if str(rel_root) == '.' else posixpath.join(remote_dir, rel_root.as_posix())
        ensure_remote_dir(remote_root)
        for file_name in files:
            local_file = Path(root) / file_name
            remote_file = posixpath.join(remote_root, file_name)
            sftp.put(str(local_file), remote_file)


def quote_single(value: str) -> str:
    return value.replace("'", "'\"'\"'")


def create_temp_worktree(ref: str, log: DeploymentLog) -> Path:
    temp_root = WORKSPACE_ROOT / 'temp'
    temp_root.mkdir(parents=True, exist_ok=True)
    temp_dir = Path(tempfile.mkdtemp(prefix='seoindicator-deploy-', dir=str(temp_root)))
    result = run_local(['git', 'worktree', 'add', '--detach', str(temp_dir), ref], cwd=PROJECT_ROOT)
    details = shorten_output((result.stdout or '') + '\n' + (result.stderr or ''))
    if result.returncode != 0:
        shutil.rmtree(temp_dir, ignore_errors=True)
        raise DeployError(f'Failed to create temp worktree for {ref}:\n{details}')
    log.add_event('info', f'Created temp worktree at {temp_dir} for ref {ref}')
    return temp_dir


def ensure_dependency_link(temp_dir: Path, source_dir: Path, relative_path: str):
    source = source_dir / relative_path
    target = temp_dir / relative_path
    if target.exists() or target.is_symlink():
        return
    target.parent.mkdir(parents=True, exist_ok=True)
    if os.name == 'nt':
        subprocess.run(['cmd', '/c', 'mklink', '/J', str(target), str(source)], check=True, capture_output=True, text=True)
    else:
        os.symlink(source, target, target_is_directory=True)


def remove_temp_worktree(temp_dir: Path):
    try:
        run_local(['git', 'worktree', 'remove', '--force', str(temp_dir)], cwd=PROJECT_ROOT, timeout=600)
    except Exception:
        pass
    shutil.rmtree(temp_dir, ignore_errors=True)


def resolve_ref(ref: str) -> str:
    result = run_local(['git', 'rev-parse', ref], cwd=PROJECT_ROOT)
    if result.returncode != 0:
        raise DeployError(f'Unable to resolve git ref {ref}: {shorten_output(result.stderr)}')
    return result.stdout.strip()


def fetch_origin(log: DeploymentLog):
    log.start_step('Fetch origin')
    result = run_local(['git', 'fetch', 'origin', 'main'], cwd=PROJECT_ROOT, timeout=1800)
    details = shorten_output((result.stdout or '') + '\n' + (result.stderr or ''))
    if result.returncode != 0:
        log.finish_step('Fetch origin', 'failed', details)
        raise DeployError(f'git fetch failed:\n{details}')
    log.finish_step('Fetch origin', 'success', details or 'origin/main refreshed')


def build_frontend(temp_dir: Path, log: DeploymentLog):
    log.start_step('Build frontend')
    frontend_temp = temp_dir / 'frontend'
    ensure_dependency_link(temp_dir, PROJECT_ROOT, 'frontend/node_modules')
    env = {
        'NEXT_TELEMETRY_DISABLED': '1',
    }

    commands = [
        ['node', 'scripts/generate-og-images.mjs'],
        ['node', 'scripts/generate-audit-og-images.mjs'],
        ['node', 'node_modules/next/dist/bin/next', 'build'],
    ]

    chunks = []
    for command in commands:
        result = run_local(command, cwd=frontend_temp, env=env, timeout=7200)
        chunk = f"$ {' '.join(command)}\n{(result.stdout or '').strip()}\n{(result.stderr or '').strip()}".strip()
        chunks.append(chunk)
        if result.returncode != 0:
            details = shorten_output('\n\n'.join(chunks))
            log.finish_step('Build frontend', 'failed', details)
            raise DeployError(f'Frontend build failed:\n{details}')

    out_dir = frontend_temp / 'out'
    if not out_dir.exists():
        log.finish_step('Build frontend', 'failed', 'frontend/out was not generated')
        raise DeployError('Frontend build did not produce frontend/out')
    details = shorten_output('\n\n'.join(chunks))
    log.finish_step('Build frontend', 'success', details or 'frontend/out generated')
    return out_dir


def deploy_remote(frontend_out_dir: Path, env: Dict[str, str], git_ref: str, log: DeploymentLog):
    log.start_step('Deploy to production')
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(
        hostname=env['SSH_HOST'],
        port=int(env['SSH_PORT']),
        username=env['SSH_USERNAME'],
        password=env['SSH_PASSWORD'],
        timeout=30,
    )

    try:
        remote_script = f"""
set -e
DEPLOY_PATH='{quote_single(env['SSH_DEPLOY_PATH'])}'
PAT='{quote_single(env['GH_PAT'])}'
cd "$DEPLOY_PATH"

git remote set-url origin "https://${{PAT}}@github.com/Raitix/seoindicator.com.git"
git fetch origin
git reset --hard {quote_single(git_ref)}
cd backend
if [ ! -f .env ]; then
  echo '.env is missing on production server' >&2
  exit 1
fi
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
php artisan config:clear || true
php artisan cache:clear || true
php artisan migrate --force
php artisan db:seed --class=CreditPackageSeeder --force || true
php artisan config:cache || true
php artisan route:cache || true
cd ..
chmod -R 755 "$DEPLOY_PATH/backend/storage" || true
chmod -R 755 "$DEPLOY_PATH/backend/bootstrap/cache" || true
"""
        stdin, stdout, stderr = ssh.exec_command(remote_script, timeout=3600)
        exit_status = stdout.channel.recv_exit_status()
        remote_output = shorten_output((stdout.read().decode('utf-8', errors='ignore') or '') + '\n' + (stderr.read().decode('utf-8', errors='ignore') or ''))
        if exit_status != 0:
            log.finish_step('Deploy to production', 'failed', remote_output)
            raise DeployError(f'Remote backend deployment failed:\n{remote_output}')

        sftp = ssh.open_sftp()
        try:
            upload_directory(sftp, frontend_out_dir, env['SSH_DEPLOY_PATH'])
        finally:
            sftp.close()

        log.finish_step('Deploy to production', 'success', remote_output or f'Deployed commit {git_ref}')
    finally:
        ssh.close()


def run_playwright(log: DeploymentLog):
    log.start_step('Run production Playwright smoke tests')
    env = {
        'PLAYWRIGHT_BASE_URL': 'https://seoindicator.com',
    }
    result = run_local(
        ['node', 'scripts/prod-smoke-check.mjs'],
        cwd=PROJECT_ROOT / 'frontend',
        env=env,
        timeout=3600,
    )
    details = shorten_output((result.stdout or '') + '\n' + (result.stderr or ''))
    if result.returncode != 0:
        log.finish_step('Run production Playwright smoke tests', 'failed', details)
        raise DeployError(f'Playwright smoke tests failed:\n{details}')
    log.finish_step('Run production Playwright smoke tests', 'success', details or 'Production smoke tests passed')


def main():
    parser = argparse.ArgumentParser(description='Deploy SEOIndicator from the local machine without GitHub Actions.')
    parser.add_argument('--trigger', default='manual')
    parser.add_argument('--git-ref', default='origin/main')
    parser.add_argument('--branch', default='main')
    parser.add_argument('--skip-playwright', action='store_true')
    args = parser.parse_args()

    DEPLOYMENTS_DIR.mkdir(parents=True, exist_ok=True)
    deployment_id = datetime.now(timezone.utc).strftime('%Y%m%dT%H%M%SZ') + '-' + uuid.uuid4().hex[:8]
    log = DeploymentLog(deployment_id, args.trigger, args.git_ref, args.branch)
    temp_dir: Optional[Path] = None

    try:
        ensure_lock(deployment_id)
        if not SECRETS_FILE.exists():
            raise DeployError(f'Secrets file not found: {SECRETS_FILE}')

        env = read_env_file(SECRETS_FILE)
        required = [
            'SSH_HOST', 'SSH_PORT', 'SSH_USERNAME', 'SSH_PASSWORD', 'SSH_DEPLOY_PATH',
            'GH_PAT',
        ]
        missing = [key for key in required if not env.get(key)]
        if missing:
            raise DeployError('Missing required secrets: ' + ', '.join(missing))
        fetch_origin(log)
        resolved_ref = resolve_ref(args.git_ref)
        log.set_commit(resolved_ref)

        temp_dir = create_temp_worktree(resolved_ref, log)
        frontend_out_dir = build_frontend(temp_dir, log)
        deploy_remote(frontend_out_dir, env, resolved_ref, log)

        if not args.skip_playwright:
            run_playwright(log)

        log.finalize('success', f'Deployment of {resolved_ref[:12]} completed successfully.')
        print(log.path)
        return 0
    except Exception as exc:
        log.add_event('error', str(exc))
        log.finalize('failed', str(exc))
        print(str(exc), file=sys.stderr)
        return 1
    finally:
        if temp_dir:
            remove_temp_worktree(temp_dir)
        release_lock()


if __name__ == '__main__':
    sys.exit(main())
