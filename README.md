# Rental Gateway Management System

**Control plane** terpusat untuk mengelola client, aplikasi sewaan, langganan, invoice, pembayaran manual, monitoring kesehatan backend, dan sinkronisasi akses ke **Apache Rental Gateway** (data plane). Trafik publik pengguna akhir: **Cloudflare → cloudflared → Apache :8080 → proxy ke port internal aplikasi client** (atau halaman static saat suspend/maintenance).

Dokumen ini ditujukan untuk **developer** yang akan mengoperasikan, deploy, atau mengembangkan fitur lanjutan. Panduan operasional harian untuk admin non-teknis ada di menu **Manual Book** (`/manual-book`) di dalam aplikasi.

---

## Daftar isi

1. [Ringkasan arsitektur](#1-ringkasan-arsitektur)
2. [Stack & batasan teknis](#2-stack--batasan-teknis)
3. [Prasyarat server](#3-prasyarat-server)
4. [Persiapan aplikasi (first install)](#4-persiapan-aplikasi-first-install)
5. [Menjalankan di development](#5-menjalankan-di-development)
6. [Deploy production (Apache + Supervisor)](#6-deploy-production-apache--supervisor)
7. [Gateway Agent & Apache data plane](#7-gateway-agent--apache-data-plane)
8. [Cloudflare Tunnel (konsep)](#8-cloudflare-tunnel-konsep)
9. [Variabel lingkungan (.env)](#9-variabel-lingkungan-env)
10. [Cara penggunaan aplikasi (alur bisnis)](#10-cara-penggunaan-aplikasi-alur-bisnis)
11. [Billing otomatis & scheduler](#11-billing-otomatis--scheduler)
12. [Monitoring & kesehatan backend](#12-monitoring--kesehatan-backend)
13. [Autentikasi, RBAC & kebijakan](#13-autentikasi-rbac--kebijakan)
14. [Struktur kode & titik ekstensi](#14-struktur-kode--titik-ekstensi)
15. [Perintah Artisan & antrian](#15-perintah-artisan--antrian)
16. [Pengujian](#16-pengujian)
17. [Deploy ulang & maintenance](#17-deploy-ulang--maintenance)
18. [Troubleshooting](#18-troubleshooting)
19. [Keamanan](#19-keamanan)
20. [Dokumentasi lanjutan](#20-dokumentasi-lanjutan)

---

## 1. Ringkasan arsitektur

| Lapisan | Komponen | Port / path tipikal | Fungsi |
|--------|-----------|---------------------|--------|
| **Control plane** | Laravel Rental Manager | Apache **5152** → `public/` | Panel admin, DB, scheduler, queue |
| **Data plane** | Apache Rental Gateway | **8080** (listener) | Virtual host per hostname client |
| **Agent** | Gateway Agent (Python 3) | **8750** (HTTP internal) | Render config Apache, `configtest`, reload |
| **Client apps** | Stack masing-masing client | mis. `127.0.0.1:450`, `:1610` | Aplikasi yang disewakan |
| **Edge** | Cloudflare + cloudflared | HTTPS publik | Route hostname → gateway lokal |

Dua status penting per aplikasi:

- **`access_status`** — keputusan bisnis (active / suspended / maintenance).
- **`gateway_applied_status`** — keadaan setelah sync Apache berhasil.

Perubahan akses menulis **access log**, mengupdate DB, lalu **ApplyGatewayConfigJob** (queue) memanggil agent. Tombol **Sinkronkan** di UI aplikasi bisa sync **langsung** (tanpa antrian) untuk feedback cepat.

Diagram lengkap: [docs/02-system-architecture.md](docs/02-system-architecture.md).

---

## 2. Stack & batasan teknis

| Aspek | Versi / pilihan |
|-------|------------------|
| PHP | **7.4.x** (production saat ini) — **jangan** pakai nullsafe operator `?->` di kode aplikasi |
| Framework | **Laravel 8.6** |
| UI | **AdminLTE 3** (Bootstrap 4), locale default **id** |
| Auth | Laravel UI, **registrasi publik dimatikan** |
| RBAC | **spatie/laravel-permission** |
| Queue | **database** (tabel `jobs`) — Redis opsional masa depan |
| HTTP client | Guzzle (health check, Gateway Agent) |

Target roadmap di dokumen desain (Laravel 11 / PHP 8.2) **belum** diterapkan di repo ini; upgrade harus disertai ADR baru ([docs/12-decision-log.md](docs/12-decision-log.md)).

---

## 3. Prasyarat server

### 3.1 Control plane (panel)

- PHP **7.4** CLI + modul: `mbstring`, `xml`, `curl`, `mysql`, `zip`, `bcmath`, `json`, `tokenizer`
- **MySQL / MariaDB** — database dedicated (mis. `rental_gateway`)
- **Apache 2.4** dengan `mod_rewrite` (panel di port **5152**)
- **Composer 2.x**
- **Supervisor** — wajib production untuk `schedule:work` dan `queue:work`

### 3.2 Data plane (gateway) — jika dipakai

- Apache: `headers`, `proxy`, `proxy_http`, `rewrite` (dan `proxy_wstunnel` jika WebSocket)
- Direktori runtime config (default di `.env`: `storage/gateway/runtime` atau path sistem)
- **Python 3.8+** untuk Gateway Agent
- Token bersama antara Laravel (`GATEWAY_AGENT_TOKEN`) dan agent (`RGA_TOKEN`)

### 3.3 Yang tidak wajib di MVP

- Redis (queue/cache masih bisa file + database)
- Email production (default `MAIL_MAILER=log`)

---

## 4. Persiapan aplikasi (first install)

Asumsikan path deploy: `/var/www/html/Laravel-Rental-Manager` (sesuaikan jika berbeda).

```bash
cd /var/www/html/Laravel-Rental-Manager

# Dependensi PHP
composer install
# Production: composer install --no-dev --optimize-autoloader

# Environment
cp .env.example .env
php artisan key:generate
```

Edit `.env` minimal:

- `APP_URL` — URL yang dipakai browser (mis. `http://127.0.0.1:5152`)
- `APP_TIMEZONE=Asia/Jakarta` (billing suspend mengikuti timezone app)
- `DB_*` — koneksi MySQL
- `SUPER_ADMIN_*` — akun bootstrap (lihat [§9](#9-variabel-lingkungan-env))
- Gateway & billing sesuai kebutuhan (boleh `GATEWAY_AGENT_ENABLED=false` dulu)

```bash
# Schema + role + admin + paket langganan contoh
php artisan migrate --force
php artisan db:seed --force

# Izin tulis (jika belum di-set script Apache)
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache
```

**Setelah mengubah `.env`:** jalankan `php artisan config:clear`. Jika production memakai `config:cache`, jalankan ulang `php artisan config:cache` setelah perubahan env.

**Jangan** commit file `.env`. **Jangan** `migrate:fresh` di server yang sudah berisi data produksi.

---

## 5. Menjalankan di development

Di laptop (tanpa Apache):

```bash
cp .env.example .env
# DB sqlite atau MySQL lokal
php artisan key:generate
php artisan migrate
php artisan db:seed

php artisan serve --host=127.0.0.1 --port=5152
# Terminal terpisah:
php artisan schedule:work
php artisan queue:work database --sleep=3 --tries=3
```

Gateway Agent (opsional):

```bash
cd gateway-agent
# Set RGA_TOKEN sama dengan GATEWAY_AGENT_TOKEN di .env Laravel
./run.sh
```

Development agent biasanya `RGA_SKIP_APACHE_RELOAD=true` (lihat `gateway-agent/run.sh`).

---

## 6. Deploy production (Apache + Supervisor)

### 6.1 Panel Laravel (port 5152)

**Jangan** menjalankan `php artisan serve` di server produksi bersama Apache.

```bash
cd /var/www/html/Laravel-Rental-Manager
sudo bash deploy/install-apache-rental-manager.sh
```

Port custom:

```bash
sudo RENTAL_MANAGER_PORT=8082 bash deploy/install-apache-rental-manager.sh
```

Virtual host: [deploy/apache/rental-manager.conf](deploy/apache/rental-manager.conf) — `DocumentRoot` → `public/`.

Detail: [docs/deploy-apache.md](docs/deploy-apache.md).

### 6.2 Supervisor (schedule + queue)

```bash
sudo bash deploy/install-supervisor.sh
sudo supervisorctl status
```

| Program Supervisor | Perintah | Fungsi |
|--------------------|----------|--------|
| `rental-manager-schedule` | `php artisan schedule:work` | Health check 5 menit, billing harian |
| `rental-manager-worker` | `queue:work database ...` | `ApplyGatewayConfigJob`, dll. |
| `rental-gateway-agent` (opsional) | `gateway-agent/run.sh` | Hanya jika `INSTALL_GATEWAY_AGENT=1` |

Prasyarat: `QUEUE_CONNECTION=database` dan migrasi tabel `jobs` sudah jalan.

**Penting:** jangan pasang cron `* * * * * php artisan schedule:run` jika `rental-manager-schedule` sudah aktif — itu **duplikat**.

Detail: [docs/deploy-supervisor.md](docs/deploy-supervisor.md).

### 6.3 Akses admin dari internet

Arahkan reverse proxy / Cloudflare Tunnel ke **`http://127.0.0.1:5152`** (panel), terpisah dari gateway `:8080`.

Log Apache panel: `/var/log/apache2/rental-manager-*.log`.

---

## 7. Gateway Agent & Apache data plane

### 7.1 Alur singkat

1. Admin mengubah status aplikasi atau verifikasi pembayaran → `AccessControlService` / billing → `GatewaySyncService::queueSync()`.
2. Worker menjalankan `ApplyGatewayConfigJob` → HTTP ke agent **POST /apply** (lihat [docs/08-api-specification.md](docs/08-api-specification.md)).
3. Agent menulis file di `GATEWAY_CONFIG_ROOT`, `apache2ctl configtest`, graceful reload.

Config Laravel: [config/gateway.php](config/gateway.php). Template Apache: [gateway/apache/templates/](gateway/apache/templates/). Static suspend: [gateway/static/](gateway/static/).

### 7.2 Pasang listener gateway (port 8080)

Contoh include: [deploy/apache/rental-gateway.conf](deploy/apache/rental-gateway.conf)

```bash
sudo cp deploy/apache/rental-gateway.conf /etc/apache2/conf-available/rental-gateway.conf
sudo a2enmod headers proxy proxy_http rewrite
sudo a2enconf rental-gateway
sudo apache2ctl configtest && sudo systemctl reload apache2
```

Pastikan `IncludeOptional` mengarah ke folder `apps/*.conf` yang diisi agent (path sesuai `GATEWAY_CONFIG_ROOT`).

### 7.3 Agent sebagai systemd (disarankan production)

```bash
# .env: GATEWAY_AGENT_ENABLED=true, GATEWAY_AGENT_TOKEN=...
sudo bash gateway-agent/deploy/install-gateway-agent-service.sh
```

Tombol Start/Restart di menu **Gateway** membutuhkan unit systemd + sudoers untuk `www-data` (script install menyiapkan helper `rental-gateway-agent-ctl`).

Panduan agent: [gateway-agent/README.md](gateway-agent/README.md).

---

## 8. Cloudflare Tunnel (konsep)

- Hostname publik client → **`http://127.0.0.1:8080`** (Apache gateway), **bukan** langsung ke port internal aplikasi.
- Panel admin (:5152) bisa tunnel terpisah dengan ACL / Access policy.
- Runbook: [docs/05-cloudflare-tunnel.md](docs/05-cloudflare-tunnel.md), [docs/11-operations-runbook.md](docs/11-operations-runbook.md).

---

## 9. Variabel lingkungan (.env)

Referensi lengkap: [.env.example](.env.example). Ringkasan kelompok:

### Aplikasi

| Variabel | Keterangan |
|----------|------------|
| `APP_NAME` | Nama tampilan panel & default issuer invoice |
| `APP_URL` | Harus cocok dengan URL browser (asset, redirect) |
| `APP_TIMEZONE` | Default `Asia/Jakarta` — mempengaruhi jadwal billing |
| `APP_DEBUG` | `false` di production |

### Database & queue

| Variabel | Keterangan |
|----------|------------|
| `DB_*` | MySQL/MariaDB |
| `QUEUE_CONNECTION` | **`database`** di production (Supervisor worker) |

### Super Admin (seed)

| Variabel | Keterangan |
|----------|------------|
| `SUPER_ADMIN_NAME` / `EMAIL` / `PASSWORD` | Dipakai `AdminUserSeeder` via [config/admin.php](config/admin.php) |

Set password kuat sebelum `db:seed`. Ganti password setelah login pertama.

Registrasi `/register` **tidak** tersedia; tambah user lanjutan via tinker / fitur `users.manage` (jika diimplementasi).

### Gateway Agent

| Variabel | Keterangan |
|----------|------------|
| `GATEWAY_AGENT_ENABLED` | `true` jika panel memanggil agent |
| `GATEWAY_AGENT_BASE_URL` | Default `http://127.0.0.1:8750` |
| `GATEWAY_AGENT_TOKEN` | Wajib; sama dengan `RGA_TOKEN` agent |
| `GATEWAY_CONFIG_ROOT` | Folder generated vhost |
| `GATEWAY_LISTENER_PORT` | **8080** — untuk dokumentasi & validasi |
| `GATEWAY_PORT_MIN` / `MAX` | Validasi port aplikasi client |

### Billing & invoice PDF

| Variabel | Keterangan |
|----------|------------|
| `BILLING_AUTO_ACTIVATE_ON_PAYMENT` | Aktifkan akses setelah pembayaran diverifikasi |
| `BILLING_AUTO_SUSPEND` | Suspend otomatis setelah grace |
| `BILLING_INVOICE_GENERATE_DAYS_BEFORE` | **7** = invoice otomatis H-7 sebelum jatuh tempo |
| `BILLING_SUSPEND_AT_TIME` | Jam efektif suspend (mis. `09:00`) |
| `BILLING_SUSPEND_RUN_AT` | Jam scheduler cek suspend (mis. `09:05`) |
| `BILLING_INVOICE_PREFIX` | Prefix nomor invoice (`INV-XXXXXX`) |
| `BILLING_ISSUER_*` | Kop surat / footer PDF |
| `BILLING_PAYMENT_RECIPIENT`, `BILLING_BANK_*` | Instruksi transfer di invoice |
| `PAYMENT_PROOF_MAX_KB` | Ukuran maks upload bukti bayar |

Config PHP: [config/billing.php](config/billing.php).

### Monitoring

| Variabel | Keterangan |
|----------|------------|
| `MONITORING_HEALTH_PATH` | Path probe ke **backend client** (host:port app). Default **`/`** |
| `MONITORING_HEALTH_TIMEOUT` | Detik |
| `MONITORING_HOST_METRICS` | Kumpulkan metrik host (CPU/disk ringkas) |

**Kesalahan umum:** mem set `MONITORING_HEALTH_PATH=/v1/health` — path itu untuk **Gateway Agent :8750**, bukan untuk proses aplikasi client di port internal.

Override per aplikasi: field `deployment_meta.health_check_path` (lihat model `Application::resolvedHealthCheckPath()`).

---

## 10. Cara penggunaan aplikasi (alur bisnis)

Menu sidebar (izin Spatie menentukan visibility):

| Menu | Route prefix | Isi |
|------|--------------|-----|
| Dasbor | `/dashboard` | Ringkasan |
| Manual Book | `/manual-book` | Panduan operator (Bahasa Indonesia) |
| Client | `/clients` | Perusahaan / pelanggan |
| Aplikasi | `/applications` | Hostname, port internal, status akses, sync gateway, health |
| Langganan | `/billing/subscriptions` | Paket, nominal, jatuh tempo berikutnya |
| Invoice | `/billing/invoices` | Rekap & detail; PDF/cetak |
| Pembayaran | `/billing/payments` | Catat bayar (modal), verifikasi/tolak |
| Monitoring | `/monitoring` | Health & layanan |
| Gateway | `/gateway` | Status agent, start/restart |

### 10.1 Onboarding client baru (tipikal)

1. **Client** — buat data perusahaan.
2. **Aplikasi** — nama, client, host internal (`127.0.0.1`), port backend, domain publik (primary hostname).
3. Pastikan proses client **listen** di port tersebut dan health probe `MONITORING_HEALTH_PATH` merespons 2xx–3xx.
4. **Langganan** — pilih aplikasi + paket; nominal bisa disesuaikan; jatuh tempo mengikuti **mulai langganan + interval paket** (bulanan/tahunan).
5. **Gateway** — sync config (otomatis via queue atau tombol Sinkronkan).
6. **Cloudflare** — arahkan hostname ke gateway `:8080`.

### 10.2 Siklus tagihan & akses

1. Scheduler **00:30** — generate invoice recurring (H-X), refresh status invoice terbuka.
2. Admin kirim invoice ke client (PDF / cetak browser; email belum built-in).
3. Client transfer → admin **Catat pembayaran** (modal di `/billing/payments`) → **Verifikasi**.
4. Jika `BILLING_AUTO_ACTIVATE_ON_PAYMENT=true`, verifikasi dapat mengaktifkan langganan & **active** akses aplikasi.
5. Scheduler **09:05** — jika invoice overdue melewati **grace** (`grace_days` di langganan), sistem dapat **suspend** aplikasi (`BILLING_AUTO_SUSPEND`).

Grace **0** hari: suspend di hari jatuh tempo sekitar jam `BILLING_SUSPEND_AT_TIME` (diekseskusi batch jam `BILLING_SUSPEND_RUN_AT`).

### 10.3 Invoice manual

Dari detail langganan: buat invoice manual (mis. setup fee). Hapus invoice hanya jika kebijakan mengizinkan (`InvoicePolicy`) dan belum ada pembayaran terverifikasi.

### 10.4 Status aplikasi manual

Di detail aplikasi: ubah **active / suspended / maintenance** dengan alasan wajib → audit di access log → sync gateway.

---

## 11. Billing otomatis & scheduler

Jadwal ([app/Console/Kernel.php](app/Console/Kernel.php)):

| Jadwal | Command | Tanggung jawab |
|--------|---------|----------------|
| Setiap 5 menit | `monitoring:health-check-applications` | Probe HTTP backend |
| Setiap 5 menit | `monitoring:collect-host-metrics` | Cache metrik host |
| Harian **00:30** | `billing:process-cycles` | Invoice recurring, refresh status |
| Harian **09:05** (config) | `billing:enforce-suspensions` | Suspend setelah grace |

Logika inti: [app/Services/Billing/SubscriptionBillingService.php](app/Services/Billing/SubscriptionBillingService.php), [InvoiceService.php](app/Services/Billing/InvoiceService.php), [PaymentService.php](app/Services/Billing/PaymentService.php).

Uji manual:

```bash
php artisan billing:process-cycles
php artisan billing:enforce-suspensions
```

Pastikan `rental-manager-schedule` **RUNNING** di production; tanpa itu billing dan monitoring tidak jalan otomatis.

---

## 12. Monitoring & kesehatan backend

- Probe: `http://{hostname}:{internal_port}{path}` — hostname dari domain primary aplikasi, port dari `internal_port`.
- Hasil disimpan di kolom `health_status`, `health_checked_at`, dll. pada `applications`.
- UI: badge di daftar aplikasi, halaman **Monitoring**, tombol jalankan cek manual.

Service: [app/Services/Monitoring/ApplicationHealthCheckService.php](app/Services/Monitoring/ApplicationHealthCheckService.php).

---

## 13. Autentikasi, RBAC & kebijakan

### Role bawaan (seed)

| Role | Cakupan singkat |
|------|------------------|
| **Super Admin** | Semua permission |
| **Administrator** | Client, aplikasi, langganan, gateway, monitoring — **tanpa** finance penuh |
| **Finance** | Client/view, langganan view, invoice & pembayaran + verifikasi |
| **Viewer** | Read-only |

Permission didefinisikan di [database/seeders/RolesAndPermissionsSeeder.php](database/seeders/RolesAndPermissionsSeeder.php).

### Policies

Mapping di [app/Providers/AuthServiceProvider.php](app/Providers/AuthServiceProvider.php):

- `ClientPolicy`, `ApplicationPolicy`, `SubscriptionPolicy`, `SubscriptionPlanPolicy`, `InvoicePolicy`, `PaymentPolicy`

Controller memanggil `$this->authorize(...)`; Blade memakai `@can('viewAny', Model::class)`.

Menambah fitur baru: buat permission → assign ke role → policy method → gate di controller/view.

---

## 14. Struktur kode & titik ekstensi

```
app/
├── Console/Commands/          # billing:*, monitoring:*
├── Http/Controllers/          # Web UI (RESTful + billing prefix)
├── Http/Requests/             # Validasi form
├── Jobs/ApplyGatewayConfigJob.php
├── Models/                    # Eloquent + konstanta status
├── Policies/
└── Services/
    ├── AccessControlService.php
    ├── ApplicationService.php
    ├── Billing/               # Invoice, Payment, SubscriptionBilling
    ├── Gateway/               # Agent client, sync, infrastructure
    └── Monitoring/

config/
├── admin.php                  # Super admin seed
├── billing.php
├── gateway.php
└── monitoring.php

resources/views/
├── billing/                   # subscriptions, invoices, payments (+ modal)
├── applications/, clients/
├── gateway/, monitoring/
├── manual-book/               # Konten operator
└── layouts/                   # AdminLTE

deploy/                        # Apache & Supervisor install scripts
gateway/                       # Template Apache, static pages
gateway-agent/                 # Python FastAPI agent
docs/                          # Spesifikasi & runbook
tests/Feature/                 # Billing, monitoring, CRUD
```

### Pola yang disarankan untuk pengembangan lanjut

1. **Aturan bisnis** → service class (bukan fat controller).
2. **Efek samping ke gateway** → selalu lewat `GatewaySyncService` / `AccessControlService` agar log & queue konsisten.
3. **Billing** → extend `SubscriptionBillingService` atau command baru + entri di `Kernel::schedule`.
4. **UI** → partial Blade + AdminLTE; hindari syntax PHP 8+ di Blade.
5. **API publik / webhook** → belum ada; bisa tambah route `api.php` + Sanctum (package sudah terpasang).

Database & ERD: [docs/03-database-design.md](docs/03-database-design.md).

---

## 15. Perintah Artisan & antrian

### Command kustom

```bash
php artisan billing:process-cycles
php artisan billing:enforce-suspensions
php artisan monitoring:health-check-applications
php artisan monitoring:collect-host-metrics
```

### Queue

```bash
php artisan queue:work database --sleep=3 --tries=3 --max-time=3600
```

Job utama: `App\Jobs\ApplyGatewayConfigJob`.

Setelah `git pull`, restart worker:

```bash
sudo supervisorctl restart rental-manager-worker
```

---

## 16. Pengujian

```bash
php artisan test
# atau
./vendor/bin/phpunit
```

Feature tests: [tests/Feature/BillingTest.php](tests/Feature/BillingTest.php), [MonitoringTest.php](tests/Feature/MonitoringTest.php), CRUD client/application.

`phpunit.xml` default memakai SQLite in-memory; lingkungan CI/server harus punya driver `pdo_sqlite` **atau** override `DB_*` untuk MySQL test.

Strategi: [docs/09-testing-strategy.md](docs/09-testing-strategy.md).

---

## 17. Deploy ulang & maintenance

```bash
cd /var/www/html/Laravel-Rental-Manager
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:clear
# Opsional production:
# php artisan route:cache
# php artisan view:cache
sudo supervisorctl restart rental-manager-worker
# Apache panel: biasanya tidak perlu reload hanya untuk kode PHP
```

**Rollback migrasi** hati-hati di DB produksi — backup dulu.

Progress fitur & phase: [TASKS.md](TASKS.md).

---

## 18. Troubleshooting

| Gejala | Penyebab umum | Tindakan |
|--------|---------------|----------|
| Backend selalu **down** di UI | `MONITORING_HEALTH_PATH` salah atau app tidak listen | Set `/` atau path health app; cek `curl` ke host:port |
| Billing tidak generate invoice | Scheduler mati | `supervisorctl status rental-manager-schedule` |
| Suspend tidak jalan jam 09:xx | Timezone / schedule | Cek `APP_TIMEZONE`, `BILLING_SUSPEND_RUN_AT`, log schedule |
| Gateway **pending** selamanya | Worker mati atau agent down | Worker + `curl` agent health; log `storage/logs/laravel.log` |
| Sync gagal | `apache2ctl configtest` gagal | Log agent; perbaiki template/port/domain |
| Upload bukti gagal | Ukuran / permission storage | `PAYMENT_PROOF_MAX_KB`, permission `storage/app` |
| Parse error di Blade | Operator PHP 8 | Ganti `?->` dengan `optional()` / ternary |
| 419 / session | `APP_URL` salah atau permission session | Sesuaikan URL, chmod `storage/framework/sessions` |

Log aplikasi: `storage/logs/laravel.log`.  
Log worker: `/var/log/supervisor/rental-manager-worker.log`.

---

## 19. Keamanan

- Jangan commit `.env`, token agent, atau password seed.
- Ganti `SUPER_ADMIN_PASSWORD` sebelum seed di server shared; rotate `GATEWAY_AGENT_TOKEN` jika bocor.
- Panel & agent hanya **localhost**; expose via tunnel dengan Access / firewall.
- `LOGIN_RATE_LIMIT` ada di `.env.example` sebagai placeholder; wire-up throttle login belum wajib — tambahkan di `LoginController` jika diperlukan.
- Production: `APP_DEBUG=false`, HTTPS di edge (Cloudflare).
- Perubahan Apache/cloudflared produksi: ikuti [docs/11-operations-runbook.md](docs/11-operations-runbook.md) dan persetujuan operasional.

Desain keamanan: [docs/06-security-design.md](docs/06-security-design.md).

---

## 20. Dokumentasi lanjutan

| Dokumen | Isi |
|---------|-----|
| [docs/00-project-overview.md](docs/00-project-overview.md) | Visi produk |
| [docs/01-requirements.md](docs/01-requirements.md) | Kebutuhan fungsional |
| [docs/02-system-architecture.md](docs/02-system-architecture.md) | Arsitektur & diagram |
| [docs/03-database-design.md](docs/03-database-design.md) | Skema & ERD |
| [docs/04-apache-gateway.md](docs/04-apache-gateway.md) | Gateway Apache |
| [docs/05-cloudflare-tunnel.md](docs/05-cloudflare-tunnel.md) | Tunnel |
| [docs/06-security-design.md](docs/06-security-design.md) | Keamanan |
| [docs/07-development-roadmap.md](docs/07-development-roadmap.md) | Roadmap phase |
| [docs/08-api-specification.md](docs/08-api-specification.md) | API Gateway Agent |
| [docs/09-testing-strategy.md](docs/09-testing-strategy.md) | Testing |
| [docs/10-deployment-guide.md](docs/10-deployment-guide.md) | Checklist deploy |
| [docs/11-operations-runbook.md](docs/11-operations-runbook.md) | Operasi harian |
| [docs/12-decision-log.md](docs/12-decision-log.md) | ADR (PHP 7.4, agent Python, dll.) |
| [docs/deploy-apache.md](docs/deploy-apache.md) | Apache panel |
| [docs/deploy-supervisor.md](docs/deploy-supervisor.md) | Supervisor |
| [gateway-agent/README.md](gateway-agent/README.md) | Agent Python |
| [TASKS.md](TASKS.md) | Status pekerjaan per phase |

---

## Status proyek (singkat)

Implementasi aktif mencakup fondasi Laravel, CRUD client/aplikasi, integrasi gateway (agent + queue), billing langganan/invoice/pembayaran manual, monitoring health, Manual Book, dan deploy script Apache/Supervisor. Beberapa item roadmap (Redis session, email invoice, multi-node, upgrade PHP 8) masih terbuka — lihat **TASKS.md** dan **docs/07-development-roadmap.md**.

---

*Terakhir diselaraskan dengan codebase Laravel Rental Manager (Laravel 8, PHP 7.4, panel :5152, gateway :8080).*
