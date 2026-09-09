@echo off
setlocal enabledelayedexpansion
title Online Mobile Purchasing and Distributing System - Automated Verification Suite

cd /d "%~dp0"

:: 1. Search for PHP executable
set "PHP_CMD="

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

where php.exe >nul 2>nul
if %errorlevel% equ 0 (
    set "PHP_CMD=php"
    goto :found_php
)

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

for /d %%i in ("C:\wamp64\bin\php\php*") do (
    if exist "%%i\php.exe" (
        set "PHP_CMD=%%i\php.exe"
        goto :found_php
    )
)

echo [ERROR] PHP executable not found.
pause
exit /b 1

:found_php
echo Running Automated Verification Suite with: "!PHP_CMD!"
echo.

"!PHP_CMD!" tests\test_flows.php

echo.
pause
