# Changelog

Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.0.0/).

## [Unreleased]

### Ditambahkan

- Dokumentasi awal Phase 0–1: arsitektur, ERD, gateway, keamanan, roadmap, TASKS.md, README, `.env.example`.
- Hasil audit read-only infrastruktur server (PHP 7.4, Apache 2.4.41, MariaDB 10.3, cloudflared aktif, Redis tidak aktif).
- **Phase 2 (partial):** Laravel 8.6.12, Laravel UI auth, Spatie Permission (4 role), dasbor Indonesia, `config/gateway.php`, seed RBAC, PHPUnit (dashboard access).
- ADR revisi: PHP 7.4 + Laravel 8; Gateway Agent **Python 3**; arsitektur MVP disetujui.
- Skeleton `gateway-agent/` (README rencana Phase 4).

### UI — AdminLTE 3

- Layout sidebar + navbar (AdminLTE 3.2, **Bootstrap 4.6**, Font Awesome 5, CDN).
- Halaman login gaya AdminLTE; dasbor small-box & info-box.
- Pagination Bootstrap 4.

### Phase 5 — Suspend & Activation

- Tabel `access_logs`, `AccessControlService`, form kontrol akses + alasan wajib.
- Badge sinkronisasi gateway; retry sync; status akses tidak lagi di form edit (hanya panel khusus).
- Agent `POST /v1/remove`; hapus aplikasi → hapus file Apache.
- Tests: `AccessControlTest` (+12 tests total suite).

### Phase 4 — Apache Gateway Integration

- Gateway Agent Python (Flask) `/v1/apply`, `/v1/health`
- Template Apache + static suspend/maintenance
- `GatewaySyncService`, job, log DB, tombol sinkronisasi aplikasi
- Menu Gateway admin + sample `rental-gateway.conf`

### Phase 3 — Client & Application Management

- CRUD client & aplikasi (domain, port, status akses).
- Validasi konflik hostname global & port per gateway node.
- Dasbor KPI dari data nyata; navigasi Client / Aplikasi.
- 8 PHPUnit tests lulus.

### Phase 6 — Subscription & Billing

- Migration: paket, harga, langganan, invoice, item, pembayaran.
- UI AdminLTE: paket, langganan, invoice, verifikasi pembayaran + bukti.
- `InvoiceService`, `PaymentService`; auto-aktivasi aplikasi setelah verifikasi (config).
- Export CSV invoice; KPI dasbor invoice; seeder paket contoh.
- Tests: `BillingTest`.

### Phase 7 — Monitoring & Reporting

- Health check HTTP per aplikasi (`monitoring:health-check-applications`, schedule 5 menit).
- Halaman **Monitoring**: apache2, cloudflared, gateway agent, load/RAM host.
- KPI dasbor + kolom/filter health di daftar aplikasi.
- Permission `monitoring.view`; tests `MonitoringTest`.

### Belum diimplementasikan

- Redis queue (masih `sync` / file cache).
- Phase 8 — Security & QA.
