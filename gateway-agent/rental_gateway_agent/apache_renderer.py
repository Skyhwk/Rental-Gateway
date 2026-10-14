import hashlib
import os
import shutil
from datetime import datetime, timezone

from jinja2 import Environment, FileSystemLoader, select_autoescape


class ApacheRenderer:
    def __init__(self, config):
        self.config = config
        self.env = Environment(
            loader=FileSystemLoader(config.TEMPLATE_DIR),
            autoescape=select_autoescape(default_for_string=False, default=False),
            trim_blocks=True,
            lstrip_blocks=True,
        )

    def render(self, payload):
        hostnames = payload["hostnames"]
        context = {
            "application_id": payload["application_id"],
            "access_mode": payload["access_mode"],
            "listener_port": self.config.LISTENER_PORT,
            "primary_hostname": hostnames[0],
            "alias_hostnames": hostnames[1:],
            "internal_host": payload["internal_host"],
            "internal_port": payload["internal_port"],
            "websocket_enabled": payload["websocket_enabled"],
            "websocket_path": payload["websocket_path"],
            "static_suspend_dir": self.config.STATIC_SUSPEND_DIR,
            "static_maintenance_dir": self.config.STATIC_MAINTENANCE_DIR,
        }
        template = self.env.get_template("app.conf.j2")
        return template.render(**context)

    def target_path(self, application_id):
        return os.path.join(self.config.APPS_DIR, "app-{}.conf".format(application_id))

    def backup_existing(self, target_path):
        if not os.path.isfile(target_path):
            return None
        ts = datetime.now(timezone.utc).strftime("%Y%m%dT%H%M%SZ")
        backup_dir = os.path.join(self.config.BACKUPS_DIR, ts)
        os.makedirs(backup_dir, exist_ok=True)
        dest = os.path.join(backup_dir, os.path.basename(target_path))
        shutil.copy2(target_path, dest)
        return dest

    def write_atomic(self, target_path, content):
        os.makedirs(os.path.dirname(target_path), exist_ok=True)
        tmp_path = target_path + ".tmp"
        with open(tmp_path, "w", encoding="utf-8") as fh:
            fh.write(content)
        os.replace(tmp_path, target_path)
        checksum = hashlib.sha256(content.encode("utf-8")).hexdigest()
        return checksum

    def rollback(self, target_path, backup_path):
        if backup_path and os.path.isfile(backup_path):
            shutil.copy2(backup_path, target_path)
        elif os.path.isfile(target_path):
            os.remove(target_path)
