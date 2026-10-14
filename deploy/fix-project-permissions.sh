#!/usr/bin/env bash
# Izin bersama: root, dedi, www-data — git, kode, dan Laravel storage.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
GROUP="${RENTAL_PROJECT_GROUP:-rental-manager}"
USERS=(dedi www-data)

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Jalankan sebagai root: sudo bash deploy/fix-project-permissions.sh"
  exit 1
fi

if ! getent group "${GROUP}" >/dev/null; then
  groupadd "${GROUP}"
  echo "Grup ${GROUP} dibuat."
fi

for u in "${USERS[@]}"; do
  if id "${u}" &>/dev/null; then
    usermod -aG "${GROUP}" "${u}"
    echo "User ${u} → grup ${GROUP}"
  fi
done

# Git safe.directory (semua user sistem)
git config --system --add safe.directory "${ROOT}" 2>/dev/null || true

chown -R dedi:"${GROUP}" "${ROOT}"
find "${ROOT}" -type d -exec chmod 2775 {} \;
find "${ROOT}" -type f -exec chmod 664 {} \;

# Executable
chmod 775 "${ROOT}/artisan" 2>/dev/null || true
find "${ROOT}/deploy" -type f -name '*.sh' -exec chmod 775 {} \; 2>/dev/null || true
find "${ROOT}/gateway-agent" -type f -name '*.sh' -exec chmod 775 {} \; 2>/dev/null || true

# Laravel runtime (Apache = www-data)
chown -R www-data:"${GROUP}" "${ROOT}/storage" "${ROOT}/bootstrap/cache"
find "${ROOT}/storage" "${ROOT}/bootstrap/cache" -type d -exec chmod 2775 {} \;
find "${ROOT}/storage" "${ROOT}/bootstrap/cache" -type f -exec chmod 664 {} \; 2>/dev/null || true

echo ""
echo "Selesai. Grup ${GROUP}: dedi & www-data bisa git + edit; storage tetap www-data."
echo "dedi: cd ${ROOT} && git status"
