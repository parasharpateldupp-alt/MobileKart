@echo off
setlocal enabledelayedexpansion
title Online Mobile Purchasing and Distributing System - Server

cd /d "%~dp0"

echo ============================================================
echo   ONLINE MOBILE PURCHASING ^& DISTRIBUTING SYSTEM
echo   Local Web Server Launcher
echo ============================================================
echo.

:: 1. Search for PHP executable
set "PHP_CMD="

:: Check WinGet installed PHP
if exist "%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.2_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe" (
    set "PHP_CMD=%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.2_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe"
    goto :found_php
)
for /d %%i in ("%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP*") do (
    if exist "%%i\php.exe" (
        set "PHP_CMD=%%i\php.exe"
        goto :found_php
    )
)

:: Check PATH
where php.exe >nul 2>nul
if %errorlevel% equ 0 (
    set "PHP_CMD=php"
    goto :found_php
)

:: Check common XAMPP and WAMP locations
if exist "C:\xampp\php\php.exe" (
    set "PHP_CMD=C:\xampp\php\php.exe"
    goto :found_php
)
if exist "D:\xampp\php\php.exe" (
    set "PHP_CMD=D:\xampp\php\php.exe"
    goto :found_php
)
if exist "E:\xampp\php\php.exe" (
    set "PHP_CMD=E:\xampp\php\php.exe"
    goto :found_php
)
if exist "C:\Program Files\PHP\php.exe" (
    set "PHP_CMD=C:\Program Files\PHP\php.exe"
    goto :found_php
)

:: Search Wamp64 if present
for /d %%i in ("C:\wamp64\bin\php\php*") do (
    if exist "%%i\php.exe" (
        set "PHP_CMD=%%i\php.exe"
        goto :found_php
    )
)

:: Search Laragon if present
for /d %%i in ("C:\laragon\bin\php\php*") do (
    if exist "%%i\php.exe" (
        set "PHP_CMD=%%i\php.exe"
        goto :found_php
    )
)

:: Prompt if not found anywhere
echo [ERROR] PHP executable not found automatically.
echo.
set /p "USER_PHP=Enter full path to php.exe: "
if not "!USER_PHP!"=="" (
    if exist "!USER_PHP!" (
        set "PHP_CMD=!USER_PHP!"
        goto :found_php
    ) else (
        echo Invalid path. File does not exist.
    )
)
echo Exiting launcher.
pause
exit /b 1

:found_php
echo [OK] Using PHP binary: "!PHP_CMD!"
echo.

:: 2. Check and manage MySQL Service on port 3306
powershell -Command "$c = New-Object System.Net.Sockets.TcpClient; try { $c.Connect('127.0.0.1', 3306); $c.Close(); exit 0 } catch { exit 1 }"
if %errorlevel% equ 0 (
    echo [OK] MySQL Database service is ACTIVE on port 3306.
) else (
    echo [INFO] MySQL is not responding on port 3306. Checking local MySQL engines...
    if exist "D:\mysql\mysql-8.4.9-winx64\bin\mysqld.exe" (
        echo [INFO] Launching MySQL Server from D:\mysql in background...
        start "MySQL Server" /min "D:\mysql\mysql-8.4.9-winx64\bin\mysqld.exe" --datadir="D:\mysql\data"
        ping -n 4 127.0.0.1 >nul
        echo [OK] MySQL Server started successfully.
    ) else if exist "C:\xampp\mysql\bin\mysqld.exe" (
        echo [INFO] Launching XAMPP MySQL Server in background...
        start "MySQL Server" /min "C:\xampp\mysql\bin\mysqld.exe"
        ping -n 4 127.0.0.1 >nul
        echo [OK] MySQL Server started successfully.
    ) else (
        echo [WARNING] MySQL is NOT active on port 3306.
        echo           Please start MySQL in your XAMPP Control Panel.
    )
)

echo.
echo ------------------------------------------------------------
echo Application Access URLs:
echo   Storefront:          http://localhost:8000/
echo   Admin Panel:         http://localhost:8000/admin/dashboard.php
echo   Supplier Portal:     http://localhost:8000/supplier/dashboard.php
echo ------------------------------------------------------------
echo.
echo Starting built-in PHP development server on http://localhost:8000 ...
echo Press Ctrl+C at any time in this window to stop the server.
echo.

:: Launch default web browser after 2 seconds in the background
start "" cmd /c "ping -n 3 127.0.0.1 >nul & start http://localhost:8000/"

:: Start PHP Server serving current working directory
"!PHP_CMD!" -S localhost:8000

echo.
echo Server stopped.
pause
