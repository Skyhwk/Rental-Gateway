#!/usr/bin/env bash
# Pasang konfig Supervisor untuk Rental Manager (schedule + queue worker + opsional agent)
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
CONF_DIR="${ROOT}/deploy/supervisor"
TARGET="/etc/supervisor/conf.d"

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Jalankan sebagai root: sudo bash deploy/install-supervisor.sh"
  exit 1
fi

mkdir -p /var/log/supervisor
cp "${CONF_DIR}/rental-manager-schedule.conf" "${TARGET}/"
cp "${CONF_DIR}/rental-manager-worker.conf" "${TARGET}/"

if [[ "${INSTALL_GATEWAY_AGENT:-0}" == "1" ]]; then
  cp "${CONF_DIR}/rental-gateway-agent.conf" "${TARGET}/"
  echo "Gateway agent config disalin (INSTALL_GATEWAY_AGENT=1)."
fi

supervisorctl reread
supervisorctl update

echo ""
echo "Status:"
supervisorctl status rental-manager-schedule rental-manager-worker 2>/dev/null || supervisorctl status

echo ""
echo "Pastikan .env: QUEUE_CONNECTION=database"
echo "Lalu: cd ${ROOT} && php artisan migrate --force"
