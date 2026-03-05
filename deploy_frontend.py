#!/usr/bin/env python3
"""Deploy frontend to production via SSH."""

import os
import paramiko
from pathlib import Path

# Configuration
SSH_HOST = "45.84.206.11"
SSH_PORT = 65002
SSH_USERNAME = "u949031532"
SSH_PASSWORD = "St43@sD!dg2"
DEPLOY_PATH = "/home/u949031532/domains/seoindicator.com/public_html"
LOCAL_PATH = r"C:\Users\Raitis\.openclaw\workspace\PROJECT-SEOINDICATOR\frontend\out"
GH_PAT = "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"

def main():
    print("=== Connecting to production server ===")
    
    # Create SSH client
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    
    try:
        ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USERNAME, password=SSH_PASSWORD)
        print("Connected successfully")
        
        # Run git pull on the server
        print("\n=== Pulling latest changes ===")
        commands = [
            f"cd {DEPLOY_PATH}",
            f'git remote set-url origin "https://{GH_PAT}@github.com/Raitix/seoindicator.com.git"',
            "git fetch origin",
            "git reset --hard origin/main",
        ]
        
        for cmd in commands:
            stdin, stdout, stderr = ssh.exec_command(cmd)
            exit_status = stdout.channel.recv_exit_status()
            output = stdout.read().decode('utf-8')
            error = stderr.read().decode('utf-8')
            if output:
                print(output)
            if error and exit_status != 0:
                print(f"Error: {error}")
            if exit_status != 0:
                print(f"Command failed with status {exit_status}")
        
        # Upload frontend files
        print("\n=== Uploading frontend files ===")
        sftp = ssh.open_sftp()
        
        local_path = Path(LOCAL_PATH)
        file_count = 0
        
        for local_file in local_path.rglob("*"):
            if local_file.is_file():
                relative_path = local_file.relative_to(local_path)
                remote_file = f"{DEPLOY_PATH}/{relative_path}".replace("\\", "/")
                
                # Create remote directory if needed
                remote_dir = os.path.dirname(remote_file)
                try:
                    sftp.stat(remote_dir)
                except FileNotFoundError:
                    # Create directory recursively
                    parts = remote_dir.split("/")
                    current = ""
                    for part in parts:
                        current += "/" + part
                        try:
                            sftp.stat(current)
                        except FileNotFoundError:
                            try:
                                sftp.mkdir(current)
                            except:
                                pass
                
                # Upload file
                sftp.put(str(local_file), remote_file)
                file_count += 1
                if file_count % 100 == 0:
                    print(f"Uploaded {file_count} files...")
        
        sftp.close()
        print(f"\nUploaded {file_count} files")
        
        # Set permissions
        print("\n=== Setting permissions ===")
        stdin, stdout, stderr = ssh.exec_command(f"chmod -R 755 {DEPLOY_PATH}/backend/storage 2>/dev/null || true")
        stdout.channel.recv_exit_status()
        stdin, stdout, stderr = ssh.exec_command(f"chmod -R 755 {DEPLOY_PATH}/backend/bootstrap/cache 2>/dev/null || true")
        stdout.channel.recv_exit_status()
        
        print("\n=== Deployment complete ===")
        print("URL: https://seoindicator.com")
        
    finally:
        ssh.close()

if __name__ == "__main__":
    main()
