# 01 — Kebutuhan Sistem

## 1. Kebutuhan fungsional

### 1.1 Authentication & Authorization

| ID | Kebutuhan | Prioritas |
|----|-----------|-----------|
| FR-AUTH-01 | Login administrator dengan session aman | MVP |
| FR-AUTH-02 | RBAC: Super Admin, Administrator, Finance, Viewer | MVP |
| FR-AUTH-03 | Manajemen permission granular (Policies) | MVP |
| FR-AUTH-04 | Audit aktivitas penting (login, perubahan status, pembayaran) | MVP |

### 1.2 Client Management

| ID | Kebutuhan | Prioritas |
|----|-----------|-----------|
| FR-CLI-01 | CRUD client (perusahaan, PIC, kontak, alamat, status, catatan) | MVP |
| FR-CLI-02 | Histori aplikasi yang disewa per client | MVP |
| FR-CLI-03 | Soft delete client (opsional, dengan aturan integritas) | MVP |

### 1.3 Application Management

| ID | Kebutuhan | Prioritas |
|----|-----------|-----------|
| FR-APP-01 | CRUD aplikasi (nama, client, domain utama/tambahan, host/port internal) | MVP |
| FR-APP-02 | Validasi unik domain & port (per lingkungan gateway) | MVP |
| FR-APP-03 | Status akses: active, suspended, maintenance | MVP |
| FR-APP-04 | Status kesehatan backend (health check terjadwal) | Phase 7 |
| FR-APP-05 | Histori perubahan konfigurasi & status | MVP |

### 1.4 Subscription & Billing

| ID | Kebutuhan | Prioritas |
|----|-----------|-----------|
| FR-SUB-01 | Paket sewa, harga, periode bulan/tahun | Phase 6 |
| FR-SUB-02 | Subscription per aplikasi/client, jatuh tempo, toleransi | Phase 6 |
| FR-INV-01 | Invoice unik, periode, status (unpaid, partial, paid, overdue, void) | Phase 6 |
| FR-PAY-01 | Pembayaran manual, bukti, verifikasi, partial payment, anti-duplikasi | Phase 6 |
| FR-PAY-02 | Verifikasi pembayaran dapat memicu aktivasi sesuai kebijakan | Phase 6 |

### 1.5 Access Control & Gateway

| ID | Kebutuhan | Prioritas |
|----|-----------|-----------|
| FR-ACC-01 | Activate, suspend, maintenance, manual override + alasan | MVP |
| FR-ACC-02 | Sinkronisasi ke gateway dengan status: pending, applied, failed | MVP |
| FR-ACC-03 | Retry & rekonsiliasi | MVP |
| FR-GW-01 | Konfigurasi domain/port dinamis via dashboard | MVP |
| FR-GW-02 | Halaman suspend & maintenance dari gateway (bukan backend client) | MVP |

### 1.6 Monitoring & Dashboard

| ID | Kebutuhan | Prioritas |
|----|-----------|-----------|
| FR-MON-01 | Status Apache, cloudflared, sinkronisasi gateway | Phase 7 |
| FR-MON-02 | Health check port backend per aplikasi | Phase 7 |
| FR-DASH-01 | KPI: client, aplikasi, status, invoice, pendapatan, aktivitas | Phase 3–7 (bertahap) |

## 2. Kebutuhan non-fungsional

| ID | Kebutuhan | Target |
|----|-----------|--------|
| NFR-PERF-01 | Gateway tidak memanggil Laravel/MySQL per HTTP request client | Wajib |
| NFR-AVAIL-01 | Gateway melayani dengan konfig terakhir jika Rental Manager down | Wajib |
| NFR-SCALE-01 | Mendukung 100+ aplikasi tanpa redesign arsitektur | Desain |
| NFR-SEC-01 | OWASP baseline, least privilege, audit | Wajib |
| NFR-OPS-01 | Backup konfig gateway sebelum setiap perubahan | Wajib |
| NFR-I18N-01 | UI bahasa Indonesia, kode bahasa Inggris | Wajib |

## 3. Batasan & asumsi

### Asumsi (perlu konfirmasi)

| # | Asumsi | Dampak jika salah |
|---|--------|-------------------|
| A1 | Satu node gateway (= satu server fisik) untuk MVP | Perlu desain multi-node lebih awal |
| A2 | Backend aplikasi client bind `127.0.0.1:<port>` | Perlu firewall jika bind `0.0.0.0` |
| A3 | Admin Rental Manager diakses via port terpisah (contoh 5152) + proteksi tambahan | Risiko exposure dashboard |
| A4 | Cloudflare Tunnel sudah mengarah ke listener Apache gateway | Perlu onboarding tunnel |
| A5 | Pembayaran 100% manual di MVP | Tidak ada auto-reconcile bank |

### Risiko

| Risiko | Probabilitas | Mitigasi |
|--------|--------------|----------|
| PHP 7.4 di server tidak kompatibel Laravel modern | Tinggi (terverifikasi) | Upgrade PHP 8.2+ parallel atau container |
| MariaDB 10.3 usang | Sedang | Upgrade atau DB terpisah untuk app baru |
| Reload Apache putus WS lama secara bertahap | Sedang | Dokumentasi + kebijakan maintenance |
| Akses langsung ke port backend bypass gateway | Sedang | Firewall localhost-only + dokumentasi |
| Konfig Apache invalid | Rendah | Agent: configtest + rollback |
| Race condition sinkronisasi gateway | Sedang | Lock + idempotency key |

## 4. Hal yang belum jelas (klarifikasi disarankan)

1. **Port Rental Manager (5152)** — Apache vhost, `php artisan serve`, atau PHP-FPM terpisah?
2. **Wildcard DNS** — apakah ada domain induk (mis. `*.sewa.example.com`) di Cloudflare untuk MVP?
3. **Custom domain client** — siapa yang mengelola DNS (client vs penyedia)?
4. **Kebijakan auto-suspend** — hanya manual vs otomatis saat overdue + hari toleransi?
5. **Storage bukti pembayaran** — lokal disk vs object storage?
6. **Backup database & retention** — RPO/RTO target?
7. **Apakah upgrade PHP/MySQL di server produksi diizinkan** dalam Phase 9?

## 5. Acceptance criteria global MVP gateway

1. Client **active** → request hostname diteruskan ke port backend.
2. Client **suspended** → tidak ada proxy ke backend; halaman suspend; header no-cache.
3. Client **maintenance** → halaman maintenance terpisah.
4. Suspend A tidak memengaruhi B.
5. Rental Manager mati → gateway tetap enforce status terakhir.
6. Config invalid → tidak reload; status sync **failed**; rollback.
7. Request publik tidak boleh mengandalkan query DB di Apache.
