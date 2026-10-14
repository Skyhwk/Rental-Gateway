#!/usr/bin/env bash
# Owner proyek: www-data — Apache, artisan optimize, cache. dedi/root lewat grup + ACL.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
GROUP="${RENTAL_PROJECT_GROUP:-rental-manager}"
OWNER="${RENTAL_PROJECT_OWNER:-www-data}"
MEMBERS=(dedi www-data)

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Jalankan sebagai root: sudo bash deploy/fix-project-permissions.sh"
  exit 1
fi

if ! id "${OWNER}" &>/dev/null; then
  echo "User ${OWNER} tidak ditemukan."
  exit 1
fi

if ! getent group "${GROUP}" >/dev/null; then
  groupadd "${GROUP}"
  echo "Grup ${GROUP} dibuat."
fi

for u in "${MEMBERS[@]}"; do
  if id "${u}" &>/dev/null; then
    usermod -aG "${GROUP}" "${u}"
    echo "User ${u} → grup ${GROUP}"
  fi
done

git config --system --add safe.directory "${ROOT}" 2>/dev/null || true

echo "Set owner ${OWNER}:${GROUP} pada ${ROOT} ..."
chown -R "${OWNER}:${GROUP}" "${ROOT}"
find "${ROOT}" -type d -exec chmod 2775 {} \;
find "${ROOT}" -type f -exec chmod 664 {} \;

chmod 775 "${ROOT}/artisan" 2>/dev/null || true
find "${ROOT}/deploy" -type f -name '*.sh' -exec chmod 775 {} \; 2>/dev/null || true
find "${ROOT}/gateway-agent" -type f -name '*.sh' -exec chmod 775 {} \; 2>/dev/null || true

# Direktori runtime Laravel (config/route/view cache, session, upload)
RUNTIME_DIRS=(
  "${ROOT}/bootstrap/cache"
  "${ROOT}/storage"
  "${ROOT}/storage/app"
  "${ROOT}/storage/app/public"
  "${ROOT}/storage/framework"
  "${ROOT}/storage/framework/cache"
  "${ROOT}/storage/framework/cache/data"
  "${ROOT}/storage/framework/sessions"
  "${ROOT}/storage/framework/views"
  "${ROOT}/storage/logs"
  "${ROOT}/storage/gateway"
  "${ROOT}/storage/gateway/runtime"
)

apply_runtime_acl() {
  local dir="$1"
  [[ -d "${dir}" ]] || return 0
  if ! command -v setfacl >/dev/null 2>&1; then
    return 0
  fi
  setfacl -R -m "u:${OWNER}:rwx" -m "g:${GROUP}:rwx" "${dir}" 2>/dev/null || true
  setfacl -R -d -m "u:${OWNER}:rwx" -m "g:${GROUP}:rwx" -m "o::rx" "${dir}" 2>/dev/null || true
}

for d in "${RUNTIME_DIRS[@]}"; do
  mkdir -p "${d}"
  chown "${OWNER}:${GROUP}" "${d}"
  chmod 2775 "${d}"
  apply_runtime_acl "${d}"
done

# .git tetap bisa dedi/root (grup rental-manager, setgid parent)
if [[ -d "${ROOT}/.git" ]]; then
  chown -R "${OWNER}:${GROUP}" "${ROOT}/.git"
  find "${ROOT}/.git" -type d -exec chmod 2775 {} \;
  find "${ROOT}/.git" -type f -exec chmod 664 {} \;
  apply_runtime_acl "${ROOT}/.git"
fi

echo ""
echo "Selesai. Owner: ${OWNER}:${GROUP} (setgid 2775, grup ${GROUP})."
echo "Artisan dari root/dedi: sudo bash deploy/artisan-www-data.sh optimize"
echo "  atau: sudo -u www-data php artisan optimize"
