@echo off
rem Zažene stran lokalno: PHP strežnik + testni mail strežnik, nato odpre brskalnik.
cd /d "%~dp0"

set PHPDIR=%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe

start "Testni mail strežnik" node dev\fake-smtp.js
start "" http://localhost:8000

echo Stran tece na http://localhost:8000  (admin: /admin.html, igra: /igra/)
echo Za ustavitev zapri to okno ali pritisni Ctrl+C.
"%PHPDIR%\php.exe" -d extension_dir="%PHPDIR%\ext" -d extension=pdo_sqlite -d extension=mbstring -d extension=openssl -S localhost:8000 -t htdocs dev\dev-router.php
