@echo off
echo ====================================
echo CTEdu Mobile APK Build Script
echo ====================================
echo.

echo [1/3] Getting Flutter dependencies...
call flutter pub get
if errorlevel 1 (
echo ERROR: Failed to get dependencies!
pause
exit /b 1
)
echo SUCCESS: Dependencies retrieved successfully
echo.

echo [2/3] Cleaning previous build...
call flutter clean
echo SUCCESS: Cleanup completed
echo.

echo [3/3] Building release APK...
call flutter build apk --release
if errorlevel 1 (
echo ERROR: APK build failed!
pause
exit /b 1
)
echo SUCCESS: APK build completed!
echo.

echo ====================================
echo APK File Location:
echo build\app\outputs\flutter-apk\app-release.apk
echo ====================================
echo.

explorer build\app\outputs\flutter-apk
pause
