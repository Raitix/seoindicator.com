@echo off
rem OpenClaw Gateway (v2026.2.9)
set PATH=C:\nvm4w\nodejs;C:\Python313\Scripts\;C:\Python313\;C:\Program Files (x86)\Common Files\Oracle\Java\java8path;C:\Program Files (x86)\Common Files\Oracle\Java\javapath;C:\Windows\system32;C:\Windows;C:\Windows\System32\Wbem;C:\Windows\System32\WindowsPowerShell\v1.0\;C:\Program Files (x86)\NVIDIA Corporation\PhysX\Common;C:\WINDOWS\system32;C:\WINDOWS;C:\WINDOWS\System32\Wbem;C:\WINDOWS\System32\WindowsPowerShell\v1.0\;C:\WINDOWS\System32\OpenSSH\;C:\Python27\Scripts;C:\Users\Raitis\AppData\Local\Programs\Python\Python37\Scripts;C:\Program Files\NVIDIA Corporation\NVIDIA NvDLISR;c:\Users\Raitis\AppData\Local\Programs\cursor\resources\app\bin;C:\Program Files\dotnet\;C:\Program Files\GitExtensions\;C:\Program Files\Git\cmd;C:\ProgramData\chocolatey\bin;C:\Users\Raitis\AppData\Local\nvm;C:\xampp\php;C:\ProgramData\ComposerSetup\bin;C:\Program Files\GitHub CLI\
set OPENCLAW_GATEWAY_PORT=18789
set OPENCLAW_GATEWAY_TOKEN=91fc59a09c039757a644d511b666a53a8be7a5cf7663b51b
set OPENCLAW_SYSTEMD_UNIT=openclaw-gateway.service
set OPENCLAW_SERVICE_MARKER=openclaw
set OPENCLAW_SERVICE_KIND=gateway
set OPENCLAW_SERVICE_VERSION=2026.2.9
C:\nvm4w\nodejs\node.exe C:\Users\Raitis\AppData\Local\nvm\v23.10.0\node_modules\openclaw\dist\index.js gateway --port 18789
