#!/usr/bin/env python3
"""Deploy using existing frontend/out folder (skip build)."""
import json
import os
import posixpath
import shutil
import subprocess
import sys
from datetime import datetime, timezone
from pathlib import Path

import paramiko

PROJECT_ROOT = Path(__file__).resolve().parents[1]
OPENCLAW_ROOT = PROJECT_ROOT.parent.parent
SECRETS_FILE = OPENCLAW_ROOT / 'secrets' / 'seoindicator.env'
DEPLOYMENTS_DIR = OPENCLAW_ROOT / 'logs' / 'deployments'

def now_iso() -> str:
    return datetime.now(timezone.utc).isoformat()

def read_env_file(path: Path) -> dict:
    result = {}
    for raw_line in path.read_text(encoding='utf-8').splitlines():
        line = raw_line.strip()
        if not line or line.startswith('#') or '=' not in line:
            continue
        key, value = line.split('=', 1)
        result[key.strip()] = value.strip()
    return result

def quote_single(value: str) -> str:
    return value.replace("'", "'\"'\"'")

def upload_directory(sftp, local_dir: Path, remote_dir: str):
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

def main():
    deployment_id = datetime.now().strftime('%Y%m%dT%H%M%SZ') + '-' + os.urandom(4).hex()
    
    # Read secrets
    secrets = read_env_file(SECRETS_FILE)
    
    # Create deployment log
    DEPLOYMENTS_DIR.mkdir(parents=True, exist_ok=True)
    log_path = DEPLOYMENTS_DIR / f'{deployment_id}.json'
    log_data = {
        'id': deployment_id,
        'trigger': 'manual-skip-build',
        'gitRef': 'origin/main',
        'branch': 'main',
        'status': 'running',
        'startedAt': now_iso(),
        'projectRoot': str(PROJECT_ROOT),
        'productionUrl': 'https://seoindicator.com',
        'commit': None,
        'steps': [],
        'events': [],
    }
    
    def write_log():
        log_path.write_text(json.dumps(log_data, indent=2), encoding='utf-8')
    
    write_log()
    
    # Get current commit
    result = subprocess.run(['git', 'rev-parse', 'HEAD'], cwd=PROJECT_ROOT, capture_output=True, text=True)
    commit = result.stdout.strip()[:12]
    log_data['commit'] = commit
    write_log()
    
    print(f"Deploying commit {commit} using existing out folder...")
    
    # Check out folder exists
    out_dir = PROJECT_ROOT / 'frontend' / 'out'
    if not out_dir.exists():
        log_data['status'] = 'failed'
        log_data['summary'] = 'frontend/out folder not found - run build first'
        write_log()
        print("ERROR: frontend/out not found")
        sys.exit(1)
    
    # Deploy to production
    print("Connecting to production...")
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(
        hostname=secrets['SSH_HOST'],
        port=int(secrets['SSH_PORT']),
        username=secrets['SSH_USERNAME'],
        password=secrets['SSH_PASSWORD'],
        timeout=30,
    )
    
    try:
        git_ref = commit
        
        # Run remote deployment script
        remote_script = f"""
set -e
DEPLOY_PATH='{quote_single(secrets['SSH_DEPLOY_PATH'])}'
PAT='{quote_single(secrets['GH_PAT'])}'
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
        print("Running remote deployment...")
        stdin, stdout, stderr = ssh.exec_command(remote_script, timeout=3600)
        exit_status = stdout.channel.recv_exit_status()
        remote_output = stdout.read().decode('utf-8', errors='ignore')
        remote_err = stderr.read().decode('utf-8', errors='ignore')
        
        if exit_status != 0:
            log_data['status'] = 'failed'
            log_data['summary'] = f'Remote deployment failed: {remote_err[:500]}'
            write_log()
            print(f"ERROR: {remote_err}")
            sys.exit(1)
        
        # Upload frontend
        print("Uploading frontend files...")
        sftp = ssh.open_sftp()
        try:
            upload_directory(sftp, out_dir, secrets['SSH_DEPLOY_PATH'])
        finally:
            sftp.close()
        
        log_data['status'] = 'success'
        log_data['endedAt'] = now_iso()
        log_data['summary'] = f'Deployed commit {commit} successfully'
        write_log()
        
        print(f"SUCCESS: Deployed commit {commit}")
        
    finally:
        ssh.close()

if __name__ == '__main__':
    main()
