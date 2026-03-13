#!/usr/bin/env python3
"""Verify GSC tables exist on production database."""
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
    
    # Check if gsc_connections and gsc_data tables exist
    commands = [
        "cd {0}/backend && php artisan tinker --execute=\"echo 'gsc_connections: ' . (Schema::hasTable('gsc_connections') ? 'EXISTS' : 'MISSING') . PHP_EOL;\"".format(secrets['SSH_DEPLOY_PATH']),
        "cd {0}/backend && php artisan tinker --execute=\"echo 'gsc_data: ' . (Schema::hasTable('gsc_data') ? 'EXISTS' : 'MISSING') . PHP_EOL;\"".format(secrets['SSH_DEPLOY_PATH']),
        "cd {0}/backend && php artisan tinker --execute=\"echo 'keywords.gsc_query_match: ' . (Schema::hasColumn('keywords', 'gsc_query_match') ? 'EXISTS' : 'MISSING') . PHP_EOL;\"".format(secrets['SSH_DEPLOY_PATH']),
    ]
    
    for cmd in commands:
        stdin, stdout, stderr = ssh.exec_command(cmd)
        print(stdout.read().decode('utf-8'))
        err = stderr.read().decode('utf-8')
        if err:
            print("Stderr:", err[:200])
    
    ssh.close()

if __name__ == '__main__':
    main()
