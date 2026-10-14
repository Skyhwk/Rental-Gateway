# TASKS — Rental Gateway Management System

**Legenda status:** `TODO` | `IN PROGRESS` | `BLOCKED` | `DONE`

Aturan: Jangan set `DONE` sebelum implementasi **dan** pengujian selesai.

---

## Phase 0 — Discovery & Infrastructure Audit

| ID | Pekerjaan | Status | Catatan |
|----|-----------|--------|---------|
| P0-01 | Audit versi PHP, Apache, DB, cloudflared (read-only) | DONE | PHP 7.4, MariaDB 10.3, cloudflared OK |
| P0-02 | Audit modul Apache (proxy, headers, wstunnel) | DONE | headers & wstunnel belum loaded |
| P0-03 | Cek status Redis & Supervisor | IN PROGRESS | Redis inactive; Supervisor TBD |
| P0-04 | Inventaris vhost Apache & tunnel routes existing | TODO | Butuh baca config + persetujuan |
| P0-05 | Inventaris port aplikasi client aktif | TODO | |
| P0-06 | Klarifikasi pertanyaan terbuka ke stakeholder | IN PROGRESS | PHP 7.4 & arsitektur OK; wildcard/auto-suspend dll. masih terbuka |
| P0-07 | Keputusan PHP: tetap 7.4 (tidak upgrade 8) | DONE | ADR-006 revisi — Laravel 8.x |

---

## Phase 1 — Architecture & Database Design

| ID | Pekerjaan | Status | Catatan |
|----|-----------|--------|---------|
| P1-01 | docs/00-project-overview.md | DONE | |
| P1-02 | docs/01-requirements.md | DONE | |
| P1-03 | docs/02-system-architecture.md + diagram Mermaid | DONE | |
| P1-04 | docs/03-database-design.md + ERD Mermaid | DONE | |
| P1-05 | docs/04-apache-gateway.md | DONE | |
| P1-06 | docs/05-cloudflare-tunnel.md | DONE | |
| P1-07 | docs/06-security-design.md | DONE | |
| P1-08 | docs/07-development-roadmap.md | DONE | |
| P1-09 | docs/08-api-specification.md | DONE | |
| P1-10 | docs/09-testing-strategy.md | DONE | |
| P1-11 | docs/10-deployment-guide.md | DONE | |
| P1-12 | docs/11-operations-runbook.md | DONE | |
| P1-13 | docs/12-decision-log.md | DONE | |
| P1-14 | README.md, CHANGELOG.md, .env.example | DONE | |
| P1-15 | Review & persetujuan stakeholder | DONE | Arsitektur OK; Agent Python; port 5152 nanti dibuka |

---

## Phase 2 — Laravel Foundation

| ID | Pekerjaan | Status | Dependensi |
|----|-----------|--------|------------|
| P2-01 | PHP 7.4 + Laravel 8.6 scaffold | DONE | |
| P2-02 | Laravel UI auth (register disabled) | DONE | |
| P2-03 | Spatie Permission + seed 4 role | DONE | |
| P2-04 | Layout dashboard (UI Indonesia) | DONE | Bootstrap CDN (npm belum terpasang) |
| P2-05 | Redis queue + cache + session | TODO | Redis inactive — sementara sync/file |
| P2-06 | Supervisor config template workers | TODO | P2-05 |
| P2-07 | PHPUnit baseline + feature dashboard | DONE | 9 tests; DB test = sqlite memory |
| P2-08 | Vhost/port 5152 + serve produksi | TODO | Menunggu Anda buka port |
| P2-09 | Dokumentasi ADR Python Agent | DONE | gateway-agent/README.md |

---

## Phase 3 — Client & Application Management

| ID | Pekerjaan | Status |
|----|-----------|--------|
| P3-01 | Migrations gateway_nodes, clients, applications, application_domains | DONE |
| P3-02 | CRUD Client + policies | DONE |
| P3-03 | CRUD Application + validasi hostname/port | DONE |
| P3-04 | UI histori per app (placeholder di halaman detail aplikasi) | DONE |
| P3-05 | Feature tests CRUD + authorization + konflik domain/port | DONE |

---

## Phase 4 — Apache Gateway Integration

| ID | Pekerjaan | Status |
|----|-----------|--------|
| P4-01 | Struktur direktori gateway (runtime, templates, static) | DONE |
| P4-02 | Template Apache Jinja2 active/suspended/maintenance | DONE |
| P4-03 | Halaman static suspend & maintenance | DONE |
| P4-04 | a2enmod headers (+ wstunnel jika perlu) — **manual ops** | TODO |
| P4-05 | Gateway Agent Python v1 (apply, lock, backup, rollback) | DONE |
| P4-06 | systemd + sudoers contoh | DONE (file deploy/) |
| P4-07 | Laravel GatewayAgentClient + Job + UI sync | DONE |
| P4-08 | Include Apache sample + halaman /gateway | DONE |
| P4-09 | `python3-venv` + jalankan agent di server | DONE | venv OK; health :8750 → 200 |

---

## Phase 5 — Suspend & Activation

| ID | Pekerjaan | Status |
|----|-----------|--------|
| P5-01 | Migrations access_logs (+ sync tables Phase 4) | DONE |
| P5-02 | AccessControlService + UI kontrol akses + alasan | DONE |
| P5-03 | Indikator synced/pending/failed | DONE |
| P5-04 | Retry sync + agent `/v1/remove` saat hapus app | DONE |
| P5-05 | Feature tests access + gateway (partial E2E Apache) | DONE (unit/feature); E2E Apache manual |

---

## Phase 6 — Subscription & Billing

| ID | Pekerjaan | Status |
|----|-----------|--------|
| P6-01 | subscription_plans + price history | DONE |
| P6-02 | subscriptions + status lifecycle | DONE |
| P6-03 | invoices + items + statuses | DONE |
| P6-04 | payments + verification + partial allocation | DONE |
| P6-05 | Verified payment → activate (config `BILLING_AUTO_ACTIVATE_ON_PAYMENT`) | DONE |
| P6-06 | Export laporan invoice | DONE |

---

## Phase 7 — Monitoring & Reporting

| ID | Pekerjaan | Status |
|----|-----------|--------|
| P7-01 | Health check backend HTTP per app (scheduled) | DONE |
| P7-02 | Status apache2, cloudflared, agent | DONE |
| P7-03 | Dashboard KPI lengkap | DONE |
| P7-04 | Aggregate CPU/RAM host (jika akurat) | DONE (load + RAM via /proc, cache 5m) |

---

## Phase 8 — Security & QA

| ID | Pekerjaan | Status |
|----|-----------|--------|
| P8-01 | Security checklist review | TODO |
| P8-02 | Concurrency tests gateway + payment | TODO |
| P8-03 | Cloudflare Access admin (optional) | TODO |
| P8-04 | Rate limiting & audit coverage | TODO |

---

## Phase 9 — Staging & Production

| ID | Pekerjaan | Status |
|----|-----------|--------|
| P9-01 | Deploy staging end-to-end | TODO |
| P9-02 | UAT dengan client dummy | TODO |
| P9-03 | Production cutover checklist | TODO |
| P9-04 | Backup & rollback drill | TODO |
| P9-05 | Dokumentasi operasi final | TODO |

---

## Ringkasan progress

| Phase | Done | Total (approx) |
|-------|------|----------------|
| 0 | 3 | 7 |
| 1 | 14 | 15 |
| 2–9 | 0 | 40+ |

**Gate saat ini:** Selesaikan **P1-15** (persetujuan Anda) sebelum Phase 2 dan perubahan infrastruktur.
