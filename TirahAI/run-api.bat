@echo off
title TirahAi API
echo Starting TirahAi API server...
echo.
python entrypoint.py
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo API server exited with error code %ERRORLEVEL%
    pause
)
