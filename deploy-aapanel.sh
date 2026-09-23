#!/usr/bin/env bash
# ==============================================================================
# WebGuard Investigasi - aaPanel Deployment & Update Script
# Repositori: https://github.com/Igustisultanh12/web-detect.git
# ==============================================================================

set -e

PROJECT_DIR="/www/wwwroot/web-detect"

echo "--------------------------------------------------------"
echo "🚀 Memulai Deployment WebGuard Investigasi di aaPanel..."
echo "--------------------------------------------------------"

# 1. Pastikan berada di direktori proyek
if [ -d "$PROJECT_DIR" ]; then
    cd "$PROJECT_DIR"
else
    echo "❌ Direktori $PROJECT_DIR tidak ditemukan!"
    exit 1
fi

# 2. Aktifkan Maintenance Mode (opsional agar user tidak melihat error saat migrasi)
if [ -f "artisan" ]; then
    echo "🔒 Mengaktifkan Mode Pemeliharaan (Maintenance)..."
    php artisan down --render="errors::503" --retry=30 || true
fi

# 3. Pull kode terbaru dari GitHub
echo "📥 Melakukan Git Pull dari branch main..."
git fetch origin main
git reset --hard origin/main
git clean -df -e .env -e storage/ -e public/storage

# 4. Instalasi & Update Dependensi Composer (PHP)
echo "📦 Menginstal dependensi PHP via Composer..."
composer install --no-dev --optimize-autoloader --no-interaction

# 5. Jalankan Migrasi Database
echo "🗄️ Menjalankan migrasi database..."
php artisan migrate --force

# 6. Pastikan Storage Symlink Terpasang
echo "🔗 Memverifikasi storage symlink..."
php artisan storage:link || true

# 7. Build Frontend Assets jika Node.js / NPM tersedia
if command -v npm &> /dev/null; then
    echo "🎨 Mengompilasi frontend assets (Vite + Vue 3)..."
    npm ci
    npm run build
else
    echo "ℹ️ Node/NPM tidak terdeteksi di CLI path, menggunakan aset build yang sudah ada di repositori."
fi

# 8. Bersihkan dan Optimalkan Cache Laravel
echo "⚡ Mengoptimalkan cache sistem Laravel..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 9. Restart Worker Queue / Horizon
echo "🔄 Merestart antrean antarmuka (Horizon / Queue)..."
php artisan horizon:terminate || php artisan queue:restart || true

# 10. Perbaiki Hak Akses Direktori (User www aaPanel)
echo "🛡️ Memperbarui perizinan hak akses folder (www:www)..."
chown -R www:www "$PROJECT_DIR"
chmod -R 775 "$PROJECT_DIR/storage"
chmod -R 775 "$PROJECT_DIR/bootstrap/cache"

# 11. Matikan Maintenance Mode
echo "🔓 Mengaktifkan kembali website..."
php artisan up

echo "--------------------------------------------------------"
echo "✅ Deployment WebGuard Investigasi Berhasil Selesai!"
echo "--------------------------------------------------------"
