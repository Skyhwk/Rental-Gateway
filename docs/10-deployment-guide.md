# 10 — Panduan Deployment

**Peringatan:** Panduan ini tidak dieksekusi otomatis. Setiap langkah yang menyentuh Apache, cloudflared, firewall, atau upgrade sistem **memerlukan persetujuan eksplisit**.

## 1. Checklist kompatibilitas (baseline audit 2026-10-14)

| Komponen | Terdeteksi | Target MVP | Status |
|----------|------------|------------|--------|
| OS | Ubuntu 20.04.6 | 20.04 / 22.04 LTS | OK dengan catatan EOL |
| PHP CLI | 7.4.33 | 7.4 + Laravel 8 | **OK** (stack disetujui; Laravel 8 EOL — catat risiko) |
| Apache | 2.4.41 | 2.4.x | OK |
| mod_proxy / proxy_http | enabled | required | OK |
| mod_headers | not loaded | required | **GAP** |
| mod_proxy_wstunnel | not loaded | if WS needed | **GAP** |
| MariaDB | 10.3.39 | ≥ 10.6 or MySQL 8 | **GAP** (minor) |
| Redis | inactive | active | **GAP** |
| cloudflared | 2026.3.0 active | recent | OK |
| Supervisor | not verified | required for queue | **TBD** |
| Composer | not verified in audit | 2.x | **TBD** |

## 2. Urutan deploy rekomendasi

### 2.1 Persiapan runtime (staging dulu)

1. Pastikan PHP 7.4 FPM + extensions (mbstring, xml, curl, mysql, redis, zip, bcmath) — **tanpa** upgrade ke PHP 8 kecuali keputusan baru
2. Install & enable Redis; set requirepass
3. Install Supervisor
4. Buat database `rental_gateway` + user dedicated

### 2.2 Aplikasi Laravel

1. Clone/deploy ke path mis. `/var/www/rental-manager`
2. `composer install --no-dev` (production)
3. Copy `.env`, `php artisan key:generate`
4. `php artisan migrate --force`
5. `php artisan db:seed --class=RolesSeeder`
6. Configure PHP-FPM pool + Apache vhost port **5152** (atau Nginx→FPM)

### 2.3 Gateway (staging path)

1. Buat `/etc/apache2/rental-gateway/` + permissions
2. Deploy static suspend/maintenance ke `/var/www/rental-gateway/static/`
3. Include `rental-gateway.conf` di Apache **staging listener**
4. Install Gateway Agent binary + systemd unit
5. Generate token; set di Laravel `.env` dan Agent env

### 2.4 Cloudflare Tunnel

1. Tambah route hostname → `http://127.0.0.1:8080` (gateway)
2. **Jangan** expose port backend client
3. Uji smoke HTTPS

### 2.5 Firewall

```bash
# Konseptual — jangan jalankan tanpa review
# Allow 22, deny public to client ports 1610-6999
```

## 3. Environment variables

Lihat [.env.example](../.env.example).

## 4. Zero-downtime considerations

- Gunakan `apache2ctl graceful` via Agent
- Backup config sebelum setiap apply
- Maintenance window untuk `a2enmod` pertama kali

## 5. Rollback aplikasi Laravel

- `php artisan migrate:rollback` (hati-hati data)
- Redeploy release previous (symlink capistrano-style)

## 6. Rollback gateway

- Agent restore dari `backups/{timestamp}/`
- Manual: `cp backup/* apps/` + configtest + graceful

## 7. Post-deploy verification

- [ ] Admin login
- [ ] Create test app
- [ ] Apply active → curl Host header
- [ ] Suspend → 503 + no backend hit (access log backend)
- [ ] cloudflared status active
