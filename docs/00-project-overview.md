# 00 — Ringkasan Proyek

## Nama

**Rental Gateway Management System** (RGMS) — aplikasi Laravel **Rental Manager** sebagai pusat administrasi.

## Masalah bisnis

Penyedia layanan menyewakan banyak aplikasi web kepada client berbeda pada **satu server fisik**. Setiap instance berjalan di **port internal** berbeda dan diakses publik melalui **Cloudflare Tunnel** + **Apache2** sebagai gateway terpusat. Tanpa sistem terpusat, pengelolaan domain, port, kontrak, tagihan, suspend/activate, dan audit menjadi manual, rawan error, dan tidak scalable.

## Solusi

| Lapisan | Komponen | Tanggung jawab |
|---------|----------|----------------|
| Control Plane | Laravel Rental Manager | Client, aplikasi, subscription, invoice, pembayaran, status akses, audit, monitoring agregat, pemicu sinkronisasi gateway |
| Data Plane | Apache2 + konfig lokal | Routing hostname → backend port, enforce suspend/maintenance **tanpa** query DB per request |
| Edge | Cloudflare + cloudflared | HTTPS publik, tunnel ke Apache gateway |
| Otomatisasi aman | Gateway Agent | Terapkan konfig Apache dari template, backup, `configtest`, graceful reload, rollback |

## Prinsip kunci

1. **Aplikasi client tidak diubah** — hanya infra routing & status gateway.
2. **Gateway autonom saat Control Plane down** — status terakhir yang sudah diterapkan tetap berlaku.
3. **Pemisahan status** — status pembayaran, subscription, dan akses aplikasi tidak disatukan dalam satu kolom.
4. **Least privilege** — Laravel tidak memegang sudo bebas; Gateway Agent dengan hak terbatas.
5. **Modular monolith** — satu codebase Laravel terstruktur, bukan microservices prematur.

## Ruang lingkup MVP (awal)

- Auth + RBAC admin
- CRUD client & aplikasi (domain, port, validasi konflik)
- Suspend / active / maintenance + halaman statis gateway
- Gateway Agent + struktur konfig Apache modular
- Cloudflare Tunnel → Apache (hostname per app, **bukan** asumsi wildcard global)
- Subscription dasar, invoice, **pembayaran manual** + verifikasi
- Dashboard ringkas + log sinkronisasi gateway
- **Tidak** payment gateway otomatis, **tidak** Cloudflare API wajib di MVP

## Di luar MVP (fase lanjutan)

- Integrasi Cloudflare API untuk hostname tunnel
- Metrik resource per proses/container
- Multi-node gateway (beberapa server fisik)
- Cloudflare Access untuk admin (direncanakan di Phase 8)

## Stakeholder & pengguna

| Peran | Kegiatan utama |
|-------|----------------|
| Super Admin | Konfig sistem, user, override gateway |
| Administrator | Client, aplikasi, status akses |
| Finance | Invoice, pembayaran, verifikasi |
| Viewer | Read-only dashboard & laporan |

## Definisi sukses

- Puluhan–ratusan aplikasi dapat didaftarkan tanpa ubah arsitektur.
- Suspend client A tidak memengaruhi client B.
- Invalid Apache config tidak pernah masuk produksi (rollback).
- Dokumentasi operasional lengkap dan TASKS.md mencerminkan kenyataan implementasi.

## Referensi dokumen

Kebutuhan detail: [01-requirements.md](01-requirements.md)  
Arsitektur: [02-system-architecture.md](02-system-architecture.md)  
Roadmap: [07-development-roadmap.md](../docs/07-development-roadmap.md)
