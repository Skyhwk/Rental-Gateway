#!/usr/bin/env bash
# Jalankan php artisan sebagai www-data agar cache/config tidak root:root.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
ARTISAN="${ROOT}/artisan"
OWNER="${RENTAL_PROJECT_OWNER:-www-data}"

if [[ ! -f "${ARTISAN}" ]]; then
  echo "Tidak ditemukan: ${ARTISAN}"
  exit 1
fi

if [[ "$(id -un)" == "${OWNER}" ]]; then
  exec php "${ARTISAN}" "$@"
fi

if [[ "$(id -u)" -eq 0 ]]; then
  exec sudo -u "${OWNER}" php "${ARTISAN}" "$@"
fi

if sudo -n -u "${OWNER}" true 2>/dev/null; then
  exec sudo -u "${OWNER}" php "${ARTISAN}" "$@"
fi

echo "Jalankan: sudo bash deploy/artisan-www-data.sh $*"
echo "  (atau sudo -u ${OWNER} php artisan $*)"
exit 1
