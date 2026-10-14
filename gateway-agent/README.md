# Gateway Agent (Python 3)

Menerapkan konfigurasi Apache per aplikasi sesuai [docs/08-api-specification.md](../docs/08-api-specification.md).

## Instalasi cepat

```bash
cd /var/www/html/Laravel-Rental-Manager/gateway-agent
chmod +x run.sh
./run.sh
```

Default dev: **skip** `apache2ctl configtest` dan reload (`RGA_SKIP_*=true` di `run.sh`).

## Variabel lingkungan

| Env | Default |
|-----|---------|
| `RGA_TOKEN` | wajib (sama dengan `GATEWAY_AGENT_TOKEN` Laravel) |
| `RGA_CONFIG_ROOT` | `storage/gateway/runtime` |
| `RGA_TEMPLATE_DIR` | `gateway/apache/templates` |
| `RGA_LISTENER_PORT` | `8080` |
| `RGA_SKIP_APACHE_RELOAD` | `false` (production) |
| `RGA_SKIP_CONFIGTEST` | `false` (production) |

## Production (auto-start + kontrol dari Laravel)

```bash
# Token di .env: GATEWAY_AGENT_ENABLED=true, GATEWAY_AGENT_TOKEN=...
sudo bash gateway-agent/deploy/install-gateway-agent-service.sh
```

Script ini: unit systemd, **enable boot**, script `rental-gateway-agent-ctl`, sudoers untuk **www-data** (tombol Start/Restart di menu Gateway).

Manual (tanpa boot):

```bash
cd gateway-agent && ./run.sh
```

Lainnya:

1. Include `gateway/apache/rental-gateway.conf.sample` di Apache
2. `a2enmod headers proxy proxy_http rewrite`
3. Sudoers Apache (jika agent non-root): `deploy/sudoers.example`
