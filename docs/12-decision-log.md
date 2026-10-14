# 12 — Decision Log (ADR)

Format: **ID | Tanggal | Keputusan | Konteks | Alternatif | Konsekuensi**

---

## ADR-001 — Control Plane vs Data Plane

| | |
|---|---|
| **Tanggal** | 2026-10-14 |
| **Keputusan** | Laravel tidak ada di path request client; Apache enforce status dari file config |
| **Alternatif** | Middleware di setiap app client; Laravel middleware di proxy | 
| **Konsekuensi** | Perlu Gateway Agent + sync; client code unchanged |

---

## ADR-002 — Apache2 vs Nginx

| | |
|---|---|
| **Tanggal** | 2026-10-14 |
| **Keputusan** | Apache2 sesuai infrastruktur existing |
| **Alternatif** | Nginx gateway |
| **Konsekuensi** | Konfig mod_proxy; tim pelajari mod_wstunnel |

---

## ADR-003 — Cloudflare Tunnel MVP

| | |
|---|---|
| **Tanggal** | 2026-10-14 |
| **Keputusan** | Public hostname per aplikasi via dashboard (Pendekatan A) |
| **Alternatif** | Wildcard + Apache routing only |
| **Konsekuensi** | Onboarding manual; API otomasi Phase lanjutan |

---

## ADR-004 — Gateway Agent terpisah

| | |
|---|---|
| **Tanggal** | 2026-10-14 |
| **Keputusan** | Service localhost dengan sudo terbatas (configtest + graceful) |
| **Alternatif** | Laravel exec apachectl via sudo www-data |
| **Konsepsi** | Least privilege; Laravel tidak shell |

---

## ADR-005 — Bahasa implementasi Agent

| | |
|---|---|
| **Tanggal** | 2026-10-14 |
| **Keputusan** | **Python 3** (service systemd + venv) |
| **Alternatif** | Go single binary |
| **Konsekuensi** | Dependensi venv di server; dokumentasi deploy Agent Python |

---

## ADR-006 — Laravel & PHP versi

| | |
|---|---|
| **Tanggal** | 2026-10-14 (revisi 2026-10-14) |
| **Keputusan** | **PHP 7.4.33** tetap; framework **Laravel 8.x** (rilis terakhir yang mendukung PHP 7.4) |
| **Konteks** | Keputusan stakeholder — tidak upgrade PHP 8 |
| **Konsekuensi** | Laravel 8 EOL — rencana migrasi PHP/Laravel di masa depan; paket pihak ketiga harus versi kompatibel L8 |
| **Persetujuan arsitektur** | Control/Data Plane + Gateway Agent + hostname per app (MVP) — **disetujui** |
| **Port Rental Manager** | 5152 — akan dibuka nanti oleh stakeholder |

---

## ADR-007 — RBAC package

| | |
|---|---|
| **Tanggal** | 2026-10-14 |
| **Keputusan** | Spatie Laravel Permission |
| **Alternatif** | Custom roles table only |
| **Konsekuensi** | Standard pattern; migration bawaan package |

---

## ADR-008 — Pembayaran MVP

| | |
|---|---|
| **Tanggal** | 2026-10-14 |
| **Keputusan** | Manual payment only; no payment gateway |
| **Konsekuensi** | Finance workflow + bukti upload |

---

## ADR-009 — Status terpisah

| | |
|---|---|
| **Tanggal** | 2026-10-14 |
| **Keputusan** | Pisah invoice status, subscription status, access_status, gateway_applied_status |
| **Konsekuensi** | Lebih banyak kolom; clearer business rules |

---

## ADR-010 — Audit infrastruktur awal

| | |
|---|---|
| **Tanggal** | 2026-10-14 |
| **Temuan** | Redis inactive; mod_headers off; PHP 7.4 |
| **Tindakan** | Documented in deployment guide; no prod changes executed |
