#!/usr/bin/env python3
"""Check GSC migration status on production."""
import sys
sys.path.insert(0, r'C:\Users\Raitis\.openclaw\workspace\PROJECT-SEOINDICATOR\scripts')

from pathlib import Path
import paramiko

SECRETS_FILE = Path(r'C:\Users\Raitis\.openclaw\secrets\seoindicator.env')

def read_env_file(path: Path) -> dict:
    result = {}
    for raw_line in path.read_text(encoding='utf-8').splitlines():
        line = raw_line.strip()
        if not line or line.startswith('#') or '=' not in line:
            continue
        key, value = line.split('=', 1)
        result[key.strip()] = value.strip()
    return result

def main():
    secrets = read_env_file(SECRETS_FILE)
    
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    
    ssh.connect(
        hostname=secrets['SSH_HOST'],
        port=int(secrets['SSH_PORT']),
        username=secrets['SSH_USERNAME'],
        password=secrets['SSH_PASSWORD'],
    )
    
    # Check migration status for GSC tables
    stdin, stdout, stderr = ssh.exec_command(
        f"cd {secrets['SSH_DEPLOY_PATH']}/backend && php artisan migrate:status 2>&1 | grep -E '(gsc|query_match)'"
    )
    
    print("GSC Migration Status:")
    print(stdout.read().decode('utf-8'))
    
    err = stderr.read().decode('utf-8')
    if err:
        print("Errors:", err)
    
    ssh.close()

if __name__ == '__main__':
    main()
