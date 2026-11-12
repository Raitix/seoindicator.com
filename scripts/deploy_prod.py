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


# Top-level directories the frontend export owns. Everything else in the
# deploy path (backend/, api/, TASKS/, ...) is part of the repository and
# must never be touched by the prune step.
FRONTEND_ROUTE_ROOTS = (
    'about', 'admin', 'ai-visibility', 'audit', 'badges', 'compare',
    'comparison', 'competitors', 'contact', 'dashboard', 'docs', 'forgot-password',
    'guides', 'history', 'how-it-works', 'legal', 'local-seo', 'locations',
    'login', 'logout', 'newsletter', 'notifications', 'paypal', 'pricing',
    'privacy', 'projects', 'ranking-history', 'register', 'reset-password',
    'search', 'seo-guides', 'settings', 'sites', 'terms', 'tools',
    'verify-email',
)


def prune_remote_stale(ssh, remote_dir: str, local_dir: Path, log: DeploymentLog):
    """Remove exported directories the current build no longer produces.

    The static export is uploaded additively, so a route that was dropped
    (or a dynamic slug that was removed from the registry) would keep
    serving its old content indefinitely. Only directories under a known
    frontend route root are considered, and only ones that exist locally in
    an equivalent shape are kept.
    """
    # The set of relative directories the current build produces.
    local_dirs = set()
    for root, dirs, files in os.walk(local_dir):
        rel = Path(root).relative_to(local_dir)
        if str(rel) == '.':
            continue
        parts = rel.parts
        if any(p.startswith('__next') for p in parts):
            continue
        local_dirs.add(rel.as_posix())

    log.start_step('Prune stale directories')

    sftp = ssh.open_sftp()
    removed = []
    try:
        # Paramiko's SFTP client has no walk(), so the tree is walked here.
        # Deepest directories are handled first so a parent that only holds
        # stale children is removed once they are gone.
        candidates = []
        stack = [remote_dir]
        while stack:
            current = stack.pop()
            try:
                entries = sftp.listdir_attr(current)
            except IOError:
                continue
            for entry in entries:
                if entry.st_mode is None or not (entry.st_mode & 0o40000):
                    continue  # not a directory
                child = posixpath.join(current, entry.filename)
                candidates.append(child)
                stack.append(child)

        for root in sorted(candidates, key=lambda p: p.count('/'), reverse=True):
            rel = posixpath.relpath(root, remote_dir)
            parts = rel.split('/')
            # Next.js writes its payload files into "__next.*" directories
            # inside each route folder. Those are part of the export and
            # are uploaded like any other file, so they must never be
            # treated as stale.
            if any(part.startswith('__next') for part in parts):
                continue
            top = parts[0]
            if top not in FRONTEND_ROUTE_ROOTS:
                continue
            if rel in local_dirs:
                continue
            # Only remove a directory when the local build produces no
            # directory at this path or beneath it. A parent of a live
            # route (e.g. "sites" when "sites/1" exists) must stay.
            prefix = rel + '/'
            if any(d.startswith(prefix) for d in local_dirs):
                continue
            for entry in sftp.listdir_attr(root):
                try:
                    sftp.remove(posixpath.join(root, entry.filename))
                except IOError:
                    pass
            try:
                sftp.rmdir(root)
            except IOError:
                continue
            removed.append(rel)
    finally:
        sftp.close()

    if removed:
        log.finish_step(
            'Prune stale directories',
            'success',
            f'Pruned {len(removed)} stale directory(ies): '
            f'{", ".join(sorted(removed)[:20])}',
        )
    else:
        log.finish_step('Prune stale directories', 'success', 'No stale directories to prune')


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
    env = {
        'NEXT_TELEMETRY_DISABLED': '1',
    }

    # Install dependencies directly in temp worktree (more reliable than symlinks on Windows)
    install_result = run_local(['npm', 'install', '--prefer-offline'], cwd=frontend_temp, timeout=600)
    if install_result.returncode != 0:
        details = shorten_output((install_result.stdout or '') + '\n' + (install_result.stderr or ''))
        log.finish_step('Build frontend', 'failed', f'npm install failed:\n{details}')
        raise DeployError(f'npm install failed:\n{details}')

    commands = [
        ['node', 'scripts/generate-og-images.mjs'],
        ['node', 'scripts/generate-audit-og-images.mjs'],
        # The canonical .htaccess lives at the repository root, but Next.js
        # exports frontend/public/ over the site root on deploy. Without this
        # step the deployed .htaccess is a stale copy that silently reverts
        # every change made to the canonical one.
        ['node', 'scripts/sync-htaccess.mjs'],
        ['node', 'scripts/verify-htaccess.mjs'],
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

    # Authenticate with an SSH key when one is configured, otherwise fall back
    # to password auth. Key auth is preferred: it avoids storing a password in
    # the secrets file, and it is the method the production host accepts.
    key_path = env.get('SSH_KEY_PATH', '').strip()
    connect_kwargs = {
        'hostname': env['SSH_HOST'],
        'port': int(env['SSH_PORT']),
        'username': env['SSH_USERNAME'],
        'timeout': 30,
    }

    if key_path:
        key_file = Path(key_path).expanduser()
        if not key_file.exists():
            raise DeployError(f'SSH key not found: {key_file}')
        connect_kwargs['key_filename'] = str(key_file)
        connect_kwargs['look_for_keys'] = False
        connect_kwargs['allow_agent'] = False
    else:
        password = env.get('SSH_PASSWORD', '').strip()
        if not password:
            raise DeployError(
                'No SSH authentication method configured. Set SSH_KEY_PATH in the '
                'secrets file (recommended) or SSH_PASSWORD.'
            )
        connect_kwargs['password'] = password

    ssh.connect(**connect_kwargs)

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

        prune_remote_stale(ssh, env['SSH_DEPLOY_PATH'], frontend_out_dir, log)

        log.finish_step('Deploy to production', 'success', remote_output or f'Deployed commit {git_ref}')
    finally:
        ssh.close()


def wait_for_edge_freshness(log: DeploymentLog, rounds: int = 12, samples_per_round: int = 4,
                             delay: int = 10):
    """Wait until every CDN edge node serves the new build.

    The site sits behind Hostinger's CDN (hcdn). It caches HTML for as long
    as the origin's TTL allows, and a deploy does not invalidate it, so for a
    while after a deploy the edge keeps handing out the *previous* HTML - and
    with it the previous JavaScript, so the new build's behaviour (for
    example, a route that has just been opened up to guests) is not live yet.

    There is no purge API available to us: PURGE is refused with 405 and the
    hPanel API rejects it. The origin TTL is short (60s) specifically so this
    window is small. This step waits it out and reports it, rather than
    letting the smoke tests fail against a stale edge.

    The CDN has several edge nodes, and a single request only proves what
    *that* node is serving. So each round takes several samples and the
    round only counts as fresh when every sample agrees - otherwise one lucky
    hit would end the wait while other nodes still served the old build.
    """
    log.start_step('Wait for CDN to serve the new build')
    url = 'https://seoindicator.com/'

    for round_number in range(1, rounds + 1):
        seen = []
        for _ in range(samples_per_round):
            try:
                result = run_local(
                    ['curl', '-sSI', '--max-time', '20', url],
                    cwd=PROJECT_ROOT,
                    timeout=60,
                )
                for line in (result.stdout or '').splitlines():
                    if line.lower().startswith('last-modified:'):
                        seen.append(line.split(':', 1)[1].strip())
                        break
            except Exception as exc:  # noqa: BLE001 - a failed sample is not a verdict
                log.add_event('warning', f'edge sample failed: {exc}')

        distinct = sorted(set(seen))
        if seen and len(distinct) == 1:
            log.finish_step(
                'Wait for CDN to serve the new build',
                'success',
                f'all {len(seen)} edge samples agree on last-modified {distinct[0]}',
            )
            return

        detail = ', '.join(distinct) if distinct else 'no samples'
        log.add_event(
            'info',
            f'round {round_number}: edge still serving mixed builds ({detail})',
        )
        if round_number < rounds:
            time.sleep(delay)

    log.finish_step(
        'Wait for CDN to serve the new build',
        'success',
        f'gave up waiting after {rounds} rounds; the edge may still be serving '
        'the previous build for a short while',
    )


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
            'SSH_HOST', 'SSH_PORT', 'SSH_USERNAME', 'SSH_DEPLOY_PATH',
            'GH_PAT',
        ]
        missing = [key for key in required if not env.get(key)]
        if missing:
            raise DeployError('Missing required secrets: ' + ', '.join(missing))

        # Authentication is either an SSH key or a password. SSH_KEY_PATH is
        # preferred; SSH_PASSWORD is the fallback.
        if not env.get('SSH_KEY_PATH') and not env.get('SSH_PASSWORD'):
            raise DeployError(
                'No SSH authentication configured. Set SSH_KEY_PATH (recommended) '
                'or SSH_PASSWORD in the secrets file.'
            )

        fetch_origin(log)
        resolved_ref = resolve_ref(args.git_ref)
        log.set_commit(resolved_ref)

        temp_dir = create_temp_worktree(resolved_ref, log)
        frontend_out_dir = build_frontend(temp_dir, log)
        deploy_remote(frontend_out_dir, env, resolved_ref, log)

        # The edge serves the previous build until its cache entry expires.
        # Wait for it before running the smoke tests, otherwise they test the
        # old site and report a failure that is not a failure of this deploy.
        wait_for_edge_freshness(log)

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
