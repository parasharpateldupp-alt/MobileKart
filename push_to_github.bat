@echo off
title Push MobileKart to GitHub
cd /d "D:\PATU"
set "PATH=%LOCALAPPDATA%\Programs\Git\cmd;%PATH%"

echo ============================================================
echo   UPLOADING MOBILEKART TO GITHUB
echo   Repository: https://github.com/parasharpateldupp-alt/MobileKart
echo ============================================================
echo.
echo All project files (134 files) are already committed locally.
echo Attempting to push to branch 'main'...
echo.
echo NOTE: If a GitHub login window opens, click "Sign in with your browser".
echo.

git push -u origin main

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ============================================================
    echo   SUCCESS! Your project has been uploaded to GitHub:
    echo   https://github.com/parasharpateldupp-alt/MobileKart
    echo ============================================================
) else (
    echo.
    echo ============================================================
    echo   Push encountered an authentication issue.
    echo   Please check your GitHub permissions or token.
    echo ============================================================
)

echo.
pause
