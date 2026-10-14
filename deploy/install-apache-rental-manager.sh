#!/usr/bin/env bash
# Apache VirtualHost untuk Laravel (tanpa php artisan serve)
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PORT="${RENTAL_MANAGER_PORT:-5152}"
APACHE_SITE="rental-manager.conf"
SOURCE="${ROOT}/deploy/apache/${APACHE_SITE}"
TARGET="/etc/apache2/sites-available/${APACHE_SITE}"

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Jalankan sebagai root: sudo bash deploy/install-apache-rental-manager.sh"
  exit 1
fi

if ! command -v apache2ctl >/dev/null 2>&1; then
  echo "Apache2 tidak ditemukan."
  exit 1
fi

a2enmod rewrite >/dev/null 2>&1 || true

cp "${SOURCE}" "${TARGET}"
# Listen ${PORT} ada di rental-manager.conf (sama pola rental-gateway.conf)
a2ensite "${APACHE_SITE}" >/dev/null

# Izin tulis Laravel (session, log, cache)
chown -R www-data:www-data "${ROOT}/storage" "${ROOT}/bootstrap/cache"
chmod -R ug+rwx "${ROOT}/storage" "${ROOT}/bootstrap/cache"

apache2ctl configtest
systemctl reload apache2

echo ""
echo "Panel Rental Manager aktif di Apache port ${PORT}"
echo "  URL lokal: http://127.0.0.1:${PORT}/"
echo ""
echo "Set .env: APP_URL=http://127.0.0.1:${PORT}"
echo "Lalu: cd ${ROOT} && php artisan config:clear"
echo ""
echo "Supervisor (queue + schedule): sudo bash deploy/install-supervisor.sh"
echo "Jangan jalankan 'php artisan serve' bersamaan — tidak diperlukan."
