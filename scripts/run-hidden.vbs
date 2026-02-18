' Run PowerShell script completely hidden (no flash)
' Usage: wscript //nologo run-hidden.vbs "script-path.ps1" ["arg1 arg2"]

If WScript.Arguments.Count = 0 Then
    WScript.Echo "Usage: run-hidden.vbs <script.ps1> [args]"
    WScript.Quit 1
End If

Dim scriptPath, args
scriptPath = WScript.Arguments(0)

If WScript.Arguments.Count > 1 Then
    args = WScript.Arguments(1)
Else
    args = ""
End If

Dim shell, command
Set shell = CreateObject("WScript.Shell")
command = "powershell.exe -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File """ & scriptPath & """"

If args <> "" Then
    command = command & " " & args
End If

' Run completely hidden (0 = SW_HIDE)
shell.Run command, 0, False
