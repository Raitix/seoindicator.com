#!/usr/bin/env python3
"""
Production database verification for Content Decay tables.
Verifies that all 4 tables were created in production.
"""
import sys
from pathlib import Path

# Add project root to path
PROJECT_ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(PROJECT_ROOT / 'scripts'))

import paramiko

# Load secrets
OPENCLAW_ROOT = Path(__file__).resolve().parents[3]
SECRETS_FILE = OPENCLAW_ROOT / 'secrets' / 'seoindicator.env'

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
    
    ssh_host = secrets['SSH_HOST']
    ssh_port = int(secrets['SSH_PORT'])
    ssh_user = secrets['SSH_USERNAME']
    ssh_password = secrets['SSH_PASSWORD']
    
    # Database credentials
    db_host = secrets['DB_PROD_HOST']
    db_name = secrets['DB_PROD_DATABASE']
    db_user = secrets['DB_PROD_USERNAME']
    db_pass = secrets['DB_PROD_PASSWORD']
    
    # Tables to verify
    expected_tables = [
        'content_decay_projects',
        'content_decay_candidates', 
        'content_decay_signals',
        'content_refresh_tasks'
    ]
    
    print(f"Connecting to {ssh_host}:{ssh_port}...")
    
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    
    try:
        ssh.connect(ssh_host, port=ssh_port, username=ssh_user, password=ssh_password, timeout=30)
        
        # Query to check if tables exist
        tables_list = "','".join(expected_tables)
        query = f"SELECT table_name FROM information_schema.tables WHERE table_schema = '{db_name}' AND table_name IN ('{tables_list}')"
        
        mysql_cmd = f"mysql -h {db_host} -u {db_user} -p'{db_pass}' {db_name} -e \"{query}\""
        
        stdin, stdout, stderr = ssh.exec_command(mysql_cmd, timeout=60)
        
        output = stdout.read().decode('utf-8')
        error = stderr.read().decode('utf-8')
        
        if error and 'Warning' not in error:
            print(f"Error: {error}")
            return 1
        
        # Parse output
        found_tables = []
        for line in output.strip().split('\n')[1:]:  # Skip header
            if line.strip():
                found_tables.append(line.strip())
        
        print(f"\n=== Content Decay Tables Verification ===")
        print(f"Expected: {len(expected_tables)} tables")
        print(f"Found: {len(found_tables)} tables")
        
        all_found = True
        for table in expected_tables:
            if table in found_tables:
                print(f"  ✓ {table}")
            else:
                print(f"  ✗ {table} - MISSING")
                all_found = False
        
        if all_found:
            print(f"\n✓ All Content Decay tables exist in production!")
            return 0
        else:
            print(f"\n✗ Some tables are missing!")
            return 1
            
    except Exception as e:
        print(f"Error: {e}")
        return 1
    finally:
        ssh.close()

if __name__ == '__main__':
    sys.exit(main())
