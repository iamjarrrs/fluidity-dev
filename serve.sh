#!/bin/bash
# Fluidity Dev Server - dengan upload limit 10MB
# Gunakan script ini untuk menjalankan server agar upload file berjalan normal

cd "$(dirname "$0")"
echo "🚀 Memulai Fluidity Dev Server..."
echo "   Upload Max: 10M | Post Max: 20M"
echo "   URL: http://127.0.0.1:8000"
echo ""

PHP_INI_SCAN_DIR=":$(pwd)/php-config" php artisan serve
