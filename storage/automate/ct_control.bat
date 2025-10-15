@echo off
:: ========================================
:: CyberTirah Framework Control Center
:: Unified Management Script
:: ========================================

setlocal enabledelayedexpansion

:: Set colors (for better UX)
set "GREEN=[92m"
set "RED=[91m"
set "YELLOW=[93m"
set "BLUE=[94m"
set "RESET=[0m"

:MAIN_MENU
cls
echo.
echo %BLUE%========================================%RESET%
echo %BLUE%   CyberTirah Framework Control Center%RESET%
echo %BLUE%========================================%RESET%
echo.
echo %GREEN%[1]%RESET% WAMP Server Management
echo %GREEN%[2]%RESET% Environment Setup
echo %GREEN%[3]%RESET% Database Management
echo %GREEN%[4]%RESET% Cache Management
echo %GREEN%[5]%RESET% Complete System Setup
echo %GREEN%[6]%RESET% Development Tools
echo %GREEN%[7]%RESET% Backup & Restore
echo %GREEN%[8]%RESET% System Information
echo %GREEN%[0]%RESET% Exit
echo.
echo %BLUE%========================================%RESET%
echo.
set /p choice="Select an option: "

if "%choice%"=="1" goto WAMP_MENU
if "%choice%"=="2" goto ENV_MENU
if "%choice%"=="3" goto DB_MENU
if "%choice%"=="4" goto CACHE_MENU
if "%choice%"=="5" goto COMPLETE_SETUP
if "%choice%"=="6" goto DEV_TOOLS
if "%choice%"=="7" goto BACKUP_MENU
if "%choice%"=="8" goto SYSTEM_INFO
if "%choice%"=="0" goto EXIT

echo %RED%Invalid option!%RESET%
timeout /t 2 >nul
goto MAIN_MENU

:: ========================================
:: WAMP SERVER MANAGEMENT
:: ========================================
:WAMP_MENU
cls
echo.
echo %BLUE%========================================%RESET%
echo %BLUE%   WAMP Server Management%RESET%
echo %BLUE%========================================%RESET%
echo.
echo %GREEN%[1]%RESET% Start WAMP Server
echo %GREEN%[2]%RESET% Stop WAMP Server
echo %GREEN%[3]%RESET% Restart WAMP Server
echo %GREEN%[4]%RESET% Check Server Status
echo %GREEN%[0]%RESET% Back to Main Menu
echo.
set /p wamp_choice="Select an option: "

if "%wamp_choice%"=="1" goto START_WAMP
if "%wamp_choice%"=="2" goto STOP_WAMP
if "%wamp_choice%"=="3" goto RESTART_WAMP
if "%wamp_choice%"=="4" goto CHECK_WAMP
if "%wamp_choice%"=="0" goto MAIN_MENU
goto WAMP_MENU

:START_WAMP
echo.
echo %YELLOW%Starting WAMP Server...%RESET%
echo.
net start wampapache64 2>nul
if %ERRORLEVEL% EQU 0 (
    echo %GREEN%✓ Apache started successfully%RESET%
) else (
    echo %RED%✗ Apache already running or failed to start%RESET%
)
echo.
net start wampmysqld64 2>nul
if %ERRORLEVEL% EQU 0 (
    echo %GREEN%✓ MySQL started successfully%RESET%
) else (
    echo %RED%✗ MySQL already running or failed to start%RESET%
)
echo.
goto CHECK_WAMP

:STOP_WAMP
echo.
echo %YELLOW%Stopping WAMP Server...%RESET%
echo.
net stop wampapache64 2>nul
if %ERRORLEVEL% EQU 0 (
    echo %GREEN%✓ Apache stopped successfully%RESET%
) else (
    echo %RED%✗ Apache not running or failed to stop%RESET%
)
echo.
net stop wampmysqld64 2>nul
if %ERRORLEVEL% EQU 0 (
    echo %GREEN%✓ MySQL stopped successfully%RESET%
) else (
    echo %RED%✗ MySQL not running or failed to stop%RESET%
)
echo.
pause
goto WAMP_MENU

:RESTART_WAMP
call :STOP_WAMP
timeout /t 2 >nul
call :START_WAMP
goto WAMP_MENU

:CHECK_WAMP
echo.
echo %BLUE%========================================%RESET%
echo %BLUE%   Server Status%RESET%
echo %BLUE%========================================%RESET%
echo.
echo %YELLOW%Apache processes:%RESET%
tasklist | findstr httpd
if %ERRORLEVEL% NEQ 0 echo %RED%Apache is not running%RESET%
echo.
echo %YELLOW%MySQL processes:%RESET%
tasklist | findstr mysqld
if %ERRORLEVEL% NEQ 0 echo %RED%MySQL is not running%RESET%
echo.
echo %YELLOW%Ports in use:%RESET%
netstat -ano | findstr ":80 "
netstat -ano | findstr ":3306 "
echo.
echo %GREEN%Access your site at: http://frame.ct.com%RESET%
echo.
pause
goto WAMP_MENU

:: ========================================
:: ENVIRONMENT SETUP
:: ========================================
:ENV_MENU
cls
echo.
echo %BLUE%========================================%RESET%
echo %BLUE%   Environment Setup%RESET%
echo %BLUE%========================================%RESET%
echo.
echo %GREEN%[1]%RESET% Create Local .env (HTTP)
echo %GREEN%[2]%RESET% Create Production .env (HTTPS)
echo %GREEN%[3]%RESET% Edit .env File
echo %GREEN%[0]%RESET% Back to Main Menu
echo.
set /p env_choice="Select an option: "

if "%env_choice%"=="1" goto CREATE_LOCAL_ENV
if "%env_choice%"=="2" goto CREATE_PROD_ENV
if "%env_choice%"=="3" goto EDIT_ENV
if "%env_choice%"=="0" goto MAIN_MENU
goto ENV_MENU

:CREATE_LOCAL_ENV
echo.
echo %YELLOW%Creating local development .env file...%RESET%
echo.
cd ..\..
(
echo # CyberTirah Framework - Local Development
echo APP_NAME=CT Frame
echo APP_ENV=development
echo APP_DEBUG=true
echo APP_VERSION=2.0.0
echo FORCE_HTTPS=false
echo APP_URL=http://localhost
echo APP_PORT=8080
echo.
echo # Database
echo DB_DRIVER=mysql
echo DB_HOST=localhost
echo DB_PORT=3306
echo DB_USERNAME=root
echo DB_PASSWORD=
echo DB_DATABASE=ct_frame
echo DB_CHARSET=utf8mb4
echo.
echo # Cache and Session
echo CACHE_DRIVER=file
echo SESSION_DRIVER=file
echo SESSION_LIFETIME=7200
echo.
echo # Logging
echo LOG_LEVEL=debug
) > .env
cd Storage\automate
echo %GREEN%✓ Local .env file created successfully!%RESET%
echo %YELLOW%FORCE_HTTPS is set to FALSE for HTTP development%RESET%
echo.
pause
goto ENV_MENU

:CREATE_PROD_ENV
echo.
echo %YELLOW%Creating production .env file...%RESET%
echo.
cd ..\..
(
echo # CyberTirah Framework - Production
echo APP_NAME=CT Frame
echo APP_ENV=production
echo APP_DEBUG=false
echo APP_VERSION=2.0.0
echo FORCE_HTTPS=true
echo APP_URL=https://yourdomain.com
echo.
echo # Database
echo DB_DRIVER=mysql
echo DB_HOST=localhost
echo DB_PORT=3306
echo DB_USERNAME=your_db_user
echo DB_PASSWORD=your_db_password
echo DB_DATABASE=ct_frame
echo DB_CHARSET=utf8mb4
echo.
echo # Cache and Session
echo CACHE_DRIVER=file
echo SESSION_DRIVER=file
echo SESSION_LIFETIME=3600
echo.
echo # Logging
echo LOG_LEVEL=error
) > .env
cd Storage\automate
echo %GREEN%✓ Production .env file created successfully!%RESET%
echo %RED%IMPORTANT: Update database credentials before use!%RESET%
echo.
pause
goto ENV_MENU

:EDIT_ENV
cd ..\..
if exist .env (
    notepad .env
) else (
    echo %RED%✗ .env file not found! Create one first.%RESET%
    pause
)
cd Storage\automate
goto ENV_MENU

:: ========================================
:: DATABASE MANAGEMENT
:: ========================================
:DB_MENU
cls
echo.
echo %BLUE%========================================%RESET%
echo %BLUE%   Database Management%RESET%
echo %BLUE%========================================%RESET%
echo.
echo %GREEN%[1]%RESET% Import Database Schema
echo %GREEN%[2]%RESET% Setup Login System
echo %GREEN%[3]%RESET% Backup Database
echo %GREEN%[4]%RESET% Import Custom SQL
echo %GREEN%[0]%RESET% Back to Main Menu
echo.
set /p db_choice="Select an option: "

if "%db_choice%"=="1" goto IMPORT_SCHEMA
if "%db_choice%"=="2" goto SETUP_LOGIN
if "%db_choice%"=="3" goto BACKUP_DB
if "%db_choice%"=="4" goto IMPORT_CUSTOM
if "%db_choice%"=="0" goto MAIN_MENU
goto DB_MENU

:IMPORT_SCHEMA
echo.
echo %YELLOW%Database Schema Import%RESET%
echo.
set /p DB_HOST="MySQL Host (default: localhost): "
if "%DB_HOST%"=="" set DB_HOST=localhost
set /p DB_USER="MySQL Username (default: root): "
if "%DB_USER%"=="" set DB_USER=root
set /p DB_PASS="MySQL Password: "
set /p DB_NAME="Database Name: "

if "%DB_NAME%"=="" (
    echo %RED%Database name is required!%RESET%
    pause
    goto DB_MENU
)

cd ..\..
mysql -h%DB_HOST% -u%DB_USER% -p%DB_PASS% %DB_NAME% < database_schema.sql 2>nul
if %ERRORLEVEL% EQU 0 (
    echo %GREEN%✓ Schema imported successfully!%RESET%
) else (
    echo %RED%✗ Failed to import schema%RESET%
)
cd Storage\automate
echo.
pause
goto DB_MENU

:SETUP_LOGIN
echo.
echo %YELLOW%Login System Setup%RESET%
echo.
set /p DB_HOST="MySQL Host (default: localhost): "
if "%DB_HOST%"=="" set DB_HOST=localhost
set /p DB_USER="MySQL Username (default: root): "
if "%DB_USER%"=="" set DB_USER=root
set /p DB_PASS="MySQL Password: "
set /p DB_NAME="Database Name (default: ct_frame): "
if "%DB_NAME%"=="" set DB_NAME=ct_frame

cd ..\..
mysql -h%DB_HOST% -u%DB_USER% -p%DB_PASS% %DB_NAME% < database_schema.sql 2>nul
if %ERRORLEVEL% EQU 0 (
    echo %GREEN%✓ Login system setup complete!%RESET%
    echo.
    echo Test Accounts Created:
    echo   Admin: admin / admin123
    echo   Student: john.student / admin123
    echo   Teacher: jane.teacher / admin123
    echo   Parent: bob.parent / admin123
    echo   Staff: alice.staff / admin123
) else (
    echo %RED%✗ Setup failed%RESET%
)
cd Storage\automate
echo.
pause
goto DB_MENU

:BACKUP_DB
echo.
echo %YELLOW%Database Backup%RESET%
echo.
set /p DB_HOST="MySQL Host (default: localhost): "
if "%DB_HOST%"=="" set DB_HOST=localhost
set /p DB_USER="MySQL Username (default: root): "
if "%DB_USER%"=="" set DB_USER=root
set /p DB_PASS="MySQL Password: "
set /p DB_NAME="Database Name: "

if "%DB_NAME%"=="" (
    echo %RED%Database name is required!%RESET%
    pause
    goto DB_MENU
)

set BACKUP_FILE=..\db\backup_%DB_NAME%_%date:~-4%%date:~-7,2%%date:~-10,2%_%time:~0,2%%time:~3,2%%time:~6,2%.sql
set BACKUP_FILE=%BACKUP_FILE: =0%

if not exist ..\db mkdir ..\db
cd ..
mysqldump -h%DB_HOST% -u%DB_USER% -p%DB_PASS% %DB_NAME% > %BACKUP_FILE% 2>nul
if %ERRORLEVEL% EQU 0 (
    echo %GREEN%✓ Database backed up to: %BACKUP_FILE%%RESET%
) else (
    echo %RED%✗ Backup failed%RESET%
)
cd automate
echo.
pause
goto DB_MENU

:IMPORT_CUSTOM
echo.
set /p SQL_FILE="Enter SQL file path: "
if not exist "%SQL_FILE%" (
    echo %RED%✗ File not found!%RESET%
    pause
    goto DB_MENU
)

set /p DB_HOST="MySQL Host (default: localhost): "
if "%DB_HOST%"=="" set DB_HOST=localhost
set /p DB_USER="MySQL Username (default: root): "
if "%DB_USER%"=="" set DB_USER=root
set /p DB_PASS="MySQL Password: "
set /p DB_NAME="Database Name: "

mysql -h%DB_HOST% -u%DB_USER% -p%DB_PASS% %DB_NAME% < "%SQL_FILE%" 2>nul
if %ERRORLEVEL% EQU 0 (
    echo %GREEN%✓ SQL imported successfully!%RESET%
) else (
    echo %RED%✗ Import failed%RESET%
)
echo.
pause
goto DB_MENU

:: ========================================
:: CACHE MANAGEMENT
:: ========================================
:CACHE_MENU
cls
echo.
echo %BLUE%========================================%RESET%
echo %BLUE%   Cache Management%RESET%
echo %BLUE%========================================%RESET%
echo.
echo %GREEN%[1]%RESET% Clear All Cache
echo %GREEN%[2]%RESET% Clear Route Cache
echo %GREEN%[3]%RESET% Clear View Cache
echo %GREEN%[4]%RESET% Clear Session Cache
echo %GREEN%[5]%RESET% Clear Log Files
echo %GREEN%[0]%RESET% Back to Main Menu
echo.
set /p cache_choice="Select an option: "

if "%cache_choice%"=="1" goto CLEAR_ALL_CACHE
if "%cache_choice%"=="2" goto CLEAR_ROUTE_CACHE
if "%cache_choice%"=="3" goto CLEAR_VIEW_CACHE
if "%cache_choice%"=="4" goto CLEAR_SESSION_CACHE
if "%cache_choice%"=="5" goto CLEAR_LOGS
if "%cache_choice%"=="0" goto MAIN_MENU
goto CACHE_MENU

:CLEAR_ALL_CACHE
echo.
echo %YELLOW%Clearing all cache...%RESET%
set CLEARED=0
cd ..
if exist cache\*.php (
    del /Q cache\*.php
    set /a CLEARED+=1
    echo %GREEN%✓ PHP cache cleared%RESET%
)
if exist cache\*.json (
    del /Q cache\*.json
    set /a CLEARED+=1
    echo %GREEN%✓ JSON cache cleared%RESET%
)
if exist temp\* (
    del /Q temp\*
    set /a CLEARED+=1
    echo %GREEN%✓ Temp files cleared%RESET%
)
cd automate
echo.
echo %GREEN%✓ Cleared !CLEARED! cache types%RESET%
echo.
pause
goto CACHE_MENU

:CLEAR_ROUTE_CACHE
echo.
echo %YELLOW%Clearing route cache...%RESET%
cd ..
if exist cache\routes.php (
    del /Q cache\routes.php
    echo %GREEN%✓ Route cache cleared%RESET%
) else (
    echo %YELLOW%No route cache found%RESET%
)
cd automate
echo.
pause
goto CACHE_MENU

:CLEAR_VIEW_CACHE
echo.
echo %YELLOW%Clearing view cache...%RESET%
cd ..
if exist cache\views\* (
    del /Q cache\views\*
    echo %GREEN%✓ View cache cleared%RESET%
) else (
    echo %YELLOW%No view cache found%RESET%
)
cd automate
echo.
pause
goto CACHE_MENU

:CLEAR_SESSION_CACHE
echo.
echo %YELLOW%Clearing session cache...%RESET%
cd ..
if exist cache\sessions\* (
    del /Q cache\sessions\*
    echo %GREEN%✓ Session cache cleared%RESET%
) else (
    echo %YELLOW%No session cache found%RESET%
)
cd automate
echo.
pause
goto CACHE_MENU

:CLEAR_LOGS
echo.
echo %YELLOW%Clearing log files...%RESET%
cd ..
if exist logs\*.log (
    del /Q logs\*.log
    echo %GREEN%✓ Log files cleared%RESET%
) else (
    echo %YELLOW%No log files found%RESET%
)
cd automate
echo.
pause
goto CACHE_MENU

:: ========================================
:: COMPLETE SYSTEM SETUP
:: ========================================
:COMPLETE_SETUP
cls
echo.
echo %BLUE%========================================%RESET%
echo %BLUE%   Complete System Setup%RESET%
echo %BLUE%========================================%RESET%
echo.
echo This will:
echo   1. Create local .env file
echo   2. Import database schema
echo   3. Clear all cache
echo   4. Create necessary directories
echo.
set /p confirm="Continue? (Y/N): "
if /i not "%confirm%"=="Y" goto MAIN_MENU

echo.
echo %YELLOW%Step 1: Creating .env file...%RESET%
call :CREATE_LOCAL_ENV

echo.
echo %YELLOW%Step 2: Database Setup...%RESET%
set /p DB_HOST="MySQL Host (default: localhost): "
if "%DB_HOST%"=="" set DB_HOST=localhost
set /p DB_USER="MySQL Username (default: root): "
if "%DB_USER%"=="" set DB_USER=root
set /p DB_PASS="MySQL Password: "
set /p DB_NAME="Database Name: "

cd ..\..
mysql -h%DB_HOST% -u%DB_USER% -p%DB_PASS% %DB_NAME% < database_schema.sql 2>nul
if %ERRORLEVEL% EQU 0 (
    echo %GREEN%✓ Database schema imported%RESET%
) else (
    echo %RED%✗ Database import failed%RESET%
)

echo.
echo %YELLOW%Step 3: Clearing cache...%RESET%
cd Storage
del /Q cache\*.php 2>nul
del /Q cache\*.json 2>nul
echo %GREEN%✓ Cache cleared%RESET%

echo.
echo %YELLOW%Step 4: Creating directories...%RESET%
if not exist logs mkdir logs
if not exist logs\ai_requests.json echo [] > logs\ai_requests.json
if not exist temp mkdir temp
if not exist uploads mkdir uploads
echo %GREEN%✓ Directories created%RESET%

cd automate
echo.
echo %BLUE%========================================%RESET%
echo %GREEN%✓ Setup Complete!%RESET%
echo %BLUE%========================================%RESET%
echo.
echo Default Admin Login:
echo   URL: http://frame.ct.com/login
echo   Username: admin
echo   Password: admin123
echo.
echo Test Accounts (password: admin123):
echo   - Student: john.student
echo   - Teacher: jane.teacher
echo   - Parent: bob.parent
echo   - Staff: alice.staff
echo.
pause
goto MAIN_MENU

:: ========================================
:: DEVELOPMENT TOOLS
:: ========================================
:DEV_TOOLS
cls
echo.
echo %BLUE%========================================%RESET%
echo %BLUE%   Development Tools%RESET%
echo %BLUE%========================================%RESET%
echo.
echo %GREEN%[1]%RESET% Create Class Structure
echo %GREEN%[2]%RESET% Generate Module
echo %GREEN%[3]%RESET% Run Tests
echo %GREEN%[4]%RESET% Code Statistics
echo %GREEN%[0]%RESET% Back to Main Menu
echo.
set /p dev_choice="Select an option: "

if "%dev_choice%"=="1" goto CREATE_CLASSES
if "%dev_choice%"=="2" goto GEN_MODULE
if "%dev_choice%"=="3" goto RUN_TESTS
if "%dev_choice%"=="4" goto CODE_STATS
if "%dev_choice%"=="0" goto MAIN_MENU
goto DEV_TOOLS

:CREATE_CLASSES
echo.
echo %YELLOW%This feature creates basic class structure%RESET%
echo %YELLOW%Use the admin panel Module Generator for better results%RESET%
echo.
echo Visit: http://frame.ct.com/admin/module-generator
echo.
pause
goto DEV_TOOLS

:GEN_MODULE
echo.
echo %YELLOW%Module Generator%RESET%
echo.
echo Use the web interface for module generation:
echo   URL: http://frame.ct.com/admin/module-generator
echo.
pause
goto DEV_TOOLS

:RUN_TESTS
echo.
echo %YELLOW%Running framework tests...%RESET%
cd ..\..
if exist test_framework.php (
    php test_framework.php
) else (
    echo %RED%✗ Test file not found%RESET%
)
cd Storage\automate
echo.
pause
goto DEV_TOOLS

:CODE_STATS
echo.
echo %BLUE%========================================%RESET%
echo %BLUE%   Code Statistics%RESET%
echo %BLUE%========================================%RESET%
echo.
cd ..\..
echo %YELLOW%Counting files...%RESET%
echo.
set /a PHP_COUNT=0
set /a CT_COUNT=0
set /a JSON_COUNT=0

for /r %%f in (*.php) do set /a PHP_COUNT+=1
for /r %%f in (*.ct) do set /a CT_COUNT+=1
for /r %%f in (*.json) do set /a JSON_COUNT+=1

echo   PHP Files: %PHP_COUNT%
echo   View Files (.ct): %CT_COUNT%
echo   JSON Files: %JSON_COUNT%
echo.
cd Storage\automate
pause
goto DEV_TOOLS

:: ========================================
:: BACKUP & RESTORE
:: ========================================
:BACKUP_MENU
cls
echo.
echo %BLUE%========================================%RESET%
echo %BLUE%   Backup & Restore%RESET%
echo %BLUE%========================================%RESET%
echo.
echo %GREEN%[1]%RESET% Backup Database
echo %GREEN%[2]%RESET% Backup Uploads
echo %GREEN%[3]%RESET% Backup Configuration
echo %GREEN%[4]%RESET% Full System Backup
echo %GREEN%[5]%RESET% List Backups
echo %GREEN%[0]%RESET% Back to Main Menu
echo.
set /p backup_choice="Select an option: "

if "%backup_choice%"=="1" goto BACKUP_DB
if "%backup_choice%"=="2" goto BACKUP_UPLOADS
if "%backup_choice%"=="3" goto BACKUP_CONFIG
if "%backup_choice%"=="4" goto FULL_BACKUP
if "%backup_choice%"=="5" goto LIST_BACKUPS
if "%backup_choice%"=="0" goto MAIN_MENU
goto BACKUP_MENU

:BACKUP_UPLOADS
echo.
echo %YELLOW%Backing up uploads...%RESET%
cd ..\..\Backups
if not exist uploads mkdir uploads
set UPLOAD_BACKUP=uploads\uploads_%date:~-4%%date:~-7,2%%date:~-10,2%_%time:~0,2%%time:~3,2%%time:~6,2%.zip
set UPLOAD_BACKUP=%UPLOAD_BACKUP: =0%
cd ..
tar -czf "Backups\%UPLOAD_BACKUP%" Storage\uploads 2>nul
if %ERRORLEVEL% EQU 0 (
    echo %GREEN%✓ Uploads backed up to: Backups\%UPLOAD_BACKUP%%RESET%
) else (
    echo %RED%✗ Backup failed%RESET%
)
cd Storage\automate
echo.
pause
goto BACKUP_MENU

:BACKUP_CONFIG
echo.
echo %YELLOW%Backing up configuration...%RESET%
cd ..\..\Backups
if not exist configs mkdir configs
set CONFIG_BACKUP=configs\config_%date:~-4%%date:~-7,2%%date:~-10,2%_%time:~0,2%%time:~3,2%%time:~6,2%.zip
set CONFIG_BACKUP=%CONFIG_BACKUP: =0%
cd ..
if exist .env (
    copy .env "Backups\configs\.env.backup" >nul
    echo %GREEN%✓ .env backed up%RESET%
)
cd Storage\automate
echo.
pause
goto BACKUP_MENU

:FULL_BACKUP
echo.
echo %YELLOW%Creating full system backup...%RESET%
echo This may take a while...
echo.
call :BACKUP_DB
call :BACKUP_UPLOADS
call :BACKUP_CONFIG
echo.
echo %GREEN%✓ Full system backup complete!%RESET%
echo.
pause
goto BACKUP_MENU

:LIST_BACKUPS
echo.
echo %BLUE%========================================%RESET%
echo %BLUE%   Available Backups%RESET%
echo %BLUE%========================================%RESET%
echo.
cd ..\..\Backups
echo %YELLOW%Database Backups:%RESET%
if exist db\*.sql (dir /b db\*.sql) else (echo   None)
echo.
echo %YELLOW%Upload Backups:%RESET%
if exist uploads\*.zip (dir /b uploads\*.zip) else (echo   None)
echo.
echo %YELLOW%Config Backups:%RESET%
if exist configs\*.* (dir /b configs\*.*) else (echo   None)
cd ..\Storage\automate
echo.
pause
goto BACKUP_MENU

:: ========================================
:: SYSTEM INFORMATION
:: ========================================
:SYSTEM_INFO
cls
echo.
echo %BLUE%========================================%RESET%
echo %BLUE%   System Information%RESET%
echo %BLUE%========================================%RESET%
echo.
echo %YELLOW%Framework:%RESET% CyberTirah v2.0
echo %YELLOW%OS:%RESET% %OS%
echo.
echo %YELLOW%PHP Version:%RESET%
php -v 2>nul | findstr /C:"PHP"
echo.
echo %YELLOW%MySQL Version:%RESET%
mysql --version 2>nul
echo.
echo %YELLOW%Installed Modules:%RESET%
cd ..\..
set /a MODULE_COUNT=0
for /d %%d in (Body\admin\*) do set /a MODULE_COUNT+=1
echo   Admin Modules: %MODULE_COUNT%
set /a MODULE_COUNT=0
for /d %%d in (Body\public\*) do set /a MODULE_COUNT+=1
echo   Public Modules: %MODULE_COUNT%
cd Storage\automate
echo.
echo %YELLOW%Disk Space:%RESET%
cd ..\..
for /f "tokens=3" %%a in ('dir /-c ^| findstr "bytes free"') do echo   Free: %%a bytes
cd Storage\automate
echo.
pause
goto MAIN_MENU

:: ========================================
:: EXIT
:: ========================================
:EXIT
cls
echo.
echo %BLUE%========================================%RESET%
echo   Thank you for using CyberTirah Framework
echo %BLUE%========================================%RESET%
echo.
timeout /t 2 >nul
exit /b 0

