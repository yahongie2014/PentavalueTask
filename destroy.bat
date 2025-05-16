@echo off
echo Deleting .idea folder...
rmdir /s /q .idea

echo Deleting vendor folder...
rmdir /s /q vendor

echo Deleting composer.lock...
del /f /q composer.lock

echo Deleting .env file...
del /f /q .env

echo Cleanup complete.
pause
