#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
UNIT_NAME="${GATEWAY_AGENT_SYSTEMD_UNIT:-rental-gateway-agent}"

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Jalankan: sudo bash gateway-agent/deploy/install-gateway-agent-service.sh"
  exit 1
fi

install -m 644 "${ROOT}/gateway-agent/deploy/rental-gateway-agent.service" "/etc/systemd/system/${UNIT_NAME}.service"
install -m 755 "${ROOT}/gateway-agent/deploy/rental-gateway-agent-ctl" /usr/local/sbin/rental-gateway-agent-ctl
install -m 440 "${ROOT}/gateway-agent/deploy/sudoers-www-data-gateway-ctl" /etc/sudoers.d/rental-gateway-agent-ctl
visudo -c -f /etc/sudoers.d/rental-gateway-agent-ctl

systemctl daemon-reload
systemctl enable "${UNIT_NAME}"
systemctl restart "${UNIT_NAME}"

echo "Unit ${UNIT_NAME} enabled + started."
systemctl status "${UNIT_NAME}" --no-pager || true
