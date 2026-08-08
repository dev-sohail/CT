@echo off
title TirahAi Launcher
echo Starting TirahAi...
echo.
python main.py
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo Application exited with error code %ERRORLEVEL%
    pause
)
