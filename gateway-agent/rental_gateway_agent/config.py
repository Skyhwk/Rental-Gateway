import os


def env(key, default=None):
    return os.environ.get(key, default)


def env_first(*keys, default=None):
    for key in keys:
        value = os.environ.get(key)
        if value is not None and str(value).strip() != "":
            return value
    return default


class Config:
    HOST = env("RGA_HOST", "127.0.0.1")
    PORT = int(env("RGA_PORT", "8750"))
    # systemd EnvironmentFile=.env hanya set GATEWAY_* — run.sh set RGA_*.
    TOKEN = env_first("RGA_TOKEN", "GATEWAY_AGENT_TOKEN", default="")

    CONFIG_ROOT = env_first(
        "RGA_CONFIG_ROOT",
        "GATEWAY_CONFIG_ROOT",
        default="/var/www/html/Laravel-Rental-Manager/storage/gateway/runtime",
    )
    APPS_DIR = os.path.join(CONFIG_ROOT, "apps")
    BACKUPS_DIR = os.path.join(CONFIG_ROOT, "backups")

    TEMPLATE_DIR = env(
        "RGA_TEMPLATE_DIR",
        "/var/www/html/Laravel-Rental-Manager/gateway/apache/templates",
    )
    STATIC_SUSPEND_DIR = env(
        "RGA_STATIC_SUSPEND_DIR",
        "/var/www/html/Laravel-Rental-Manager/gateway/static/suspend",
    )
    STATIC_MAINTENANCE_DIR = env(
        "RGA_STATIC_MAINTENANCE_DIR",
        "/var/www/html/Laravel-Rental-Manager/gateway/static/maintenance",
    )

    LISTENER_PORT = int(env_first("RGA_LISTENER_PORT", "GATEWAY_LISTENER_PORT", default="8080"))
    _allowed = env_first("RGA_ALLOWED_HOSTS", "GATEWAY_ALLOWED_HOST", default="127.0.0.1,localhost")
    ALLOWED_HOSTS = set(filter(None, _allowed.split(",")))
    PORT_MIN = int(env_first("RGA_PORT_MIN", "GATEWAY_PORT_MIN", default="1"))
    PORT_MAX = int(env_first("RGA_PORT_MAX", "GATEWAY_PORT_MAX", default="65535"))

    APACHE_CTL = env("RGA_APACHE_CTL", "/usr/sbin/apache2ctl")
    SKIP_APACHE_RELOAD = env("RGA_SKIP_APACHE_RELOAD", "false").lower() in ("1", "true", "yes")
    SKIP_CONFIGTEST = env("RGA_SKIP_CONFIGTEST", "false").lower() in ("1", "true", "yes")

    LOCK_PATH = env("RGA_LOCK_PATH", "/var/run/rental-gateway-agent.lock")
