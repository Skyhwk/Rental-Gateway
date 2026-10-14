#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT"

ensure_venv() {
  if [[ ! -x venv/bin/python ]] || ! venv/bin/python -c "import flask" 2>/dev/null; then
    echo "Menyiapkan virtualenv dan dependensi..."
    rm -rf venv
    python3 -m venv venv
    ./venv/bin/pip install --upgrade pip
    ./venv/bin/pip install -r requirements.txt
  fi
}

ensure_venv

# Muat dari .env Laravel jika ada
ENV_FILE="${ENV_FILE:-../.env}"
if [[ -f "$ENV_FILE" ]]; then
  set -a
  # shellcheck disable=SC1090
  source <(grep -E '^GATEWAY_' "$ENV_FILE" | sed 's/^/export /')
  set +a
fi

export RGA_TOKEN="${RGA_TOKEN:-${GATEWAY_AGENT_TOKEN:-}}"
export RGA_CONFIG_ROOT="${RGA_CONFIG_ROOT:-${GATEWAY_CONFIG_ROOT:-$ROOT/../storage/gateway/runtime}}"
export RGA_SKIP_APACHE_RELOAD="${RGA_SKIP_APACHE_RELOAD:-true}"
export RGA_SKIP_CONFIGTEST="${RGA_SKIP_CONFIGTEST:-true}"

export PYTHONPATH="$ROOT"
exec ./venv/bin/python -m rental_gateway_agent.app
