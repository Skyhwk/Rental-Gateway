import os
from datetime import datetime, timezone

from flask import Flask, jsonify, request

from rental_gateway_agent.apache_renderer import ApacheRenderer
from rental_gateway_agent.config import Config
from rental_gateway_agent.executor import ApacheExecutor, FileLock
from rental_gateway_agent.validators import validate_apply_payload

config = Config()
app = Flask(__name__)
renderer = ApacheRenderer(config)
executor = ApacheExecutor(config)

LAST_APPLY_AT = None


def auth_ok():
    if not config.TOKEN:
        return False
    header = request.headers.get("Authorization", "")
    if not header.startswith("Bearer "):
        return False
    return header.split(" ", 1)[1].strip() == config.TOKEN


@app.route("/v1/health", methods=["GET"])
def health():
    if config.TOKEN and not auth_ok():
        return jsonify({"status": "unauthorized"}), 401
    return jsonify(
        {
            "status": "ok",
            "last_apply_at": LAST_APPLY_AT,
            "lock_held": False,
        }
    )


@app.route("/v1/apply", methods=["POST"])
def apply():
    global LAST_APPLY_AT

    if not auth_ok():
        return jsonify({"status": "unauthorized"}), 401

    payload, errors = validate_apply_payload(
        request.get_json(silent=True) or {},
        config.ALLOWED_HOSTS,
        config.PORT_MIN,
        config.PORT_MAX,
    )
    if errors:
        return jsonify({"status": "validation_error", "errors": errors}), 422

    try:
        with FileLock(config.LOCK_PATH):
            content = renderer.render(payload)
            target = renderer.target_path(payload["application_id"])
            backup = renderer.backup_existing(target)

            try:
                checksum = renderer.write_atomic(target, content)
                ok, test_out = executor.configtest()
                if not ok:
                    renderer.rollback(target, backup)
                    return (
                        jsonify(
                            {
                                "status": "failed",
                                "error": "configtest failed",
                                "stderr": test_out,
                                "rolled_back": True,
                            }
                        ),
                        500,
                    )

                reload_ok, reload_out = executor.graceful()
                if not reload_ok:
                    renderer.rollback(target, backup)
                    return (
                        jsonify(
                            {
                                "status": "failed",
                                "error": "graceful reload failed",
                                "stderr": reload_out,
                                "rolled_back": True,
                            }
                        ),
                        500,
                    )

                version = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ") + "-" + checksum[:8]
                LAST_APPLY_AT = datetime.now(timezone.utc).isoformat()

                return jsonify(
                    {
                        "status": "applied",
                        "config_version": version,
                        "checksum": "sha256:" + checksum,
                        "apache_configtest": test_out.splitlines()[-1] if test_out else "Syntax OK",
                    }
                )
            except Exception as exc:
                renderer.rollback(target, backup)
                return jsonify({"status": "failed", "error": str(exc), "rolled_back": True}), 500

    except BlockingIOError:
        resp = jsonify({"status": "busy"})
        resp.headers["Retry-After"] = "5"
        return resp, 409


@app.route("/v1/remove", methods=["POST"])
def remove():
    global LAST_APPLY_AT

    if not auth_ok():
        return jsonify({"status": "unauthorized"}), 401

    data = request.get_json(silent=True) or {}
    application_id = str(data.get("application_id", "")).strip()
    if not application_id:
        return jsonify({"status": "validation_error", "errors": {"application_id": "required"}}), 422

    try:
        with FileLock(config.LOCK_PATH):
            target = renderer.target_path(application_id)
            backup = renderer.backup_existing(target)

            try:
                if os.path.isfile(target):
                    os.remove(target)

                ok, test_out = executor.configtest()
                if not ok:
                    renderer.rollback(target, backup)
                    return jsonify({"status": "failed", "error": "configtest failed", "stderr": test_out}), 500

                reload_ok, reload_out = executor.graceful()
                if not reload_ok:
                    renderer.rollback(target, backup)
                    return jsonify({"status": "failed", "error": "graceful reload failed", "stderr": reload_out}), 500

                LAST_APPLY_AT = datetime.now(timezone.utc).isoformat()
                return jsonify({"status": "removed", "application_id": application_id})
            except Exception as exc:
                renderer.rollback(target, backup)
                return jsonify({"status": "failed", "error": str(exc)}), 500
    except BlockingIOError:
        resp = jsonify({"status": "busy"})
        resp.headers["Retry-After"] = "5"
        return resp, 409


def main():
    os.makedirs(config.APPS_DIR, exist_ok=True)
    os.makedirs(config.BACKUPS_DIR, exist_ok=True)
    app.run(host=config.HOST, port=config.PORT, debug=False)


if __name__ == "__main__":
    main()
