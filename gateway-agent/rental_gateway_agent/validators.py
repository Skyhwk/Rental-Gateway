import re

HOSTNAME_RE = re.compile(
    r"^(?=.{1,253}$)(?!-)([a-z0-9-]{1,63}(?<!-)\.)*[a-z0-9-]{1,63}(?<!-)$"
)
VALID_MODES = {"active", "suspended", "maintenance"}


def normalize_hostname(value):
    value = (value or "").strip().lower().rstrip(".")
    if "://" in value:
        from urllib.parse import urlparse

        parsed = urlparse(value)
        value = parsed.hostname or value
    return value


def validate_apply_payload(data, allowed_hosts, port_min, port_max):
    errors = {}

    app_id = str(data.get("application_id", "")).strip()
    if not app_id or not re.match(r"^[a-zA-Z0-9_-]+$", app_id):
        errors["application_id"] = "application_id tidak valid"

    hostnames = data.get("hostnames") or []
    if not isinstance(hostnames, list) or len(hostnames) < 1:
        errors["hostnames"] = "Minimal satu hostname"
    else:
        normalized = []
        for h in hostnames:
            n = normalize_hostname(h)
            if not n or not HOSTNAME_RE.match(n):
                errors.setdefault("hostnames", "Format hostname tidak valid")
                break
            normalized.append(n)
        if "hostnames" not in errors and len(set(normalized)) != len(normalized):
            errors["hostnames"] = "Hostname duplikat"

    internal_host = data.get("internal_host", "")
    if internal_host not in allowed_hosts:
        errors["internal_host"] = "Host internal tidak diizinkan"

    try:
        port = int(data.get("internal_port"))
        if port < port_min or port > port_max:
            errors["internal_port"] = "Port di luar range"
    except (TypeError, ValueError):
        errors["internal_port"] = "Port harus integer"

    mode = data.get("access_mode")
    if mode not in VALID_MODES:
        errors["access_mode"] = "access_mode tidak valid"

    if errors:
        return None, errors

    return {
        "application_id": app_id,
        "hostnames": normalized,
        "internal_host": internal_host,
        "internal_port": port,
        "access_mode": mode,
        "websocket_enabled": bool(data.get("websocket_enabled", False)),
        "websocket_path": str(data.get("websocket_path") or "/ws"),
    }, None
