@echo off
title Smart Waste Management - Startup
echo ========================================================
echo  Memulai Semua Layanan Smart Waste Management System
echo ========================================================
echo.
echo Pastikan Laragon / MySQL sudah dalam status "Start All"!
echo.

:: 1. Menjalankan FastAPI ML Service di window baru
start "FastAPI ML Service (Port 8001)" cmd /k "cd /d %~dp0ml-service && call .venv\Scripts\activate.bat && uvicorn main:app --host 127.0.0.1 --port 8001"

:: 2. Menjalankan Laravel Web & API di window baru
start "Laravel Backend (Port 8000)" cmd /k "cd /d %~dp0web-backend && php artisan serve --host=0.0.0.0 --port=8000"

:: 3. Menjalankan Ngrok Tunnel dengan Domain Tetap di window baru
start "Ngrok Static Tunnel" cmd /k "C:\laragon\bin\ngrok\ngrok.exe http 8000 --url prideful-lecturer-snap.ngrok-free.dev"

echo.
echo ========================================================
echo [OK] 3 Jendela terminal baru telah dibuka otomatis:
echo  1. FastAPI (Layanan AI Port 8001)
echo  2. Laravel (Web & API Port 8000) - Akses lokal: http://127.0.0.1:8000
echo  3. Ngrok Tunnel - Domain Tetap: https://prideful-lecturer-snap.ngrok-free.dev
echo.
echo Domain Anda sudah PERMANEN dan tidak akan berubah lagi!
echo ========================================================
pause
