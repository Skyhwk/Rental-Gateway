#!/usr/bin/env bash
# Kunci deploy GitHub sama untuk root, dedi, www-data (salinan identik, permission konsisten).
set -euo pipefail

SHARED_DIR="/etc/rental-gateway/ssh"
KEY_NAME="id_ed25519_rental_gateway"
USERS=(root www-data dedi)

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Jalankan sebagai root: sudo bash deploy/install-shared-github-ssh.sh"
  exit 1
fi

mkdir -p "${SHARED_DIR}"
chmod 755 /etc/rental-gateway

MASTER_KEY="${SHARED_DIR}/${KEY_NAME}"
MASTER_PUB="${MASTER_KEY}.pub"
MASTER_CONFIG="${SHARED_DIR}/config.github"

if [[ ! -f "${MASTER_KEY}" ]]; then
  ssh-keygen -t ed25519 -C "rental-gateway-deploy@$(hostname -f 2>/dev/null || hostname)" -f "${MASTER_KEY}" -N ""
  echo "Kunci baru dibuat di ${SHARED_DIR}/${KEY_NAME}"
fi

chmod 600 "${MASTER_KEY}"
chmod 644 "${MASTER_PUB}"

cat > "${MASTER_CONFIG}" <<EOF
# Rental Gateway — GitHub deploy (salin ke ~/.ssh/config atau include)
Host github.com
  HostName github.com
  User git
  IdentityFile ~/.ssh/${KEY_NAME}
  IdentitiesOnly yes
  StrictHostKeyChecking accept-new
EOF
chmod 644 "${MASTER_CONFIG}"

install_for_user() {
  local user="$1"
  if ! id "${user}" &>/dev/null; then
    echo "Lewati ${user}: user belum ada di sistem."
    return 0
  fi

  local home
  home="$(getent passwd "${user}" | cut -d: -f6)"
  if [[ -z "${home}" || ! -d "${home}" ]]; then
    echo "Lewati ${user}: home tidak valid (${home})."
    return 0
  fi

  local ssh_dir="${home}/.ssh"
  mkdir -p "${ssh_dir}"
  install -m 600 -o "${user}" -g "${user}" "${MASTER_KEY}" "${ssh_dir}/${KEY_NAME}"
  install -m 644 -o "${user}" -g "${user}" "${MASTER_PUB}" "${ssh_dir}/${KEY_NAME}.pub"
  install -m 644 -o "${user}" -g "${user}" "${MASTER_CONFIG}" "${ssh_dir}/config.d-rental-gateway"
  chmod 700 "${ssh_dir}"
  chown "${user}:${user}" "${ssh_dir}"

  local main_config="${ssh_dir}/config"
  local marker="# rental-gateway github"
  if [[ ! -f "${main_config}" ]] || ! grep -qF "${marker}" "${main_config}" 2>/dev/null; then
    {
      echo ""
      echo "${marker}"
      echo "Include config.d-rental-gateway"
    } >> "${main_config}"
    chown "${user}:${user}" "${main_config}"
    chmod 600 "${main_config}"
  fi

  echo "OK: ${user} → ${ssh_dir}/${KEY_NAME} (600, owner ${user})"
}

for u in "${USERS[@]}"; do
  install_for_user "${u}"
done

echo ""
echo "=== Public key (tambahkan di GitHub → Settings → SSH keys) ==="
cat "${MASTER_PUB}"
echo ""
echo "Uji (ganti user): sudo -u www-data ssh -T git@github.com"
echo "Master key (backup): ${MASTER_KEY}"
