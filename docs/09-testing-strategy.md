# 09 — Strategi Pengujian

## 1. Piramida

| Lapisan | Cakupan |
|---------|---------|
| Unit | Services: Invoice allocation, access status transitions, hostname normalize |
| Feature | HTTP admin: CRUD, policies, form validation |
| Integration | DB transactions payment verify; job dispatch mock Agent |
| Gateway | Template render + apachectl -t |
| E2E manual | Cloudflare hostname smoke (staging) |

## 2. Skenario wajib (dari spesifikasi)

| # | Skenario | Jenis test |
|---|----------|------------|
| 1 | Client active → akses app OK | E2E / Feature mock proxy |
| 2 | Suspended → tidak reach backend | Gateway integration |
| 3 | Maintenance → halaman maintenance | Gateway integration |
| 4 | Activate → akses normal | E2E |
| 5 | Suspend A tidak affect B | Gateway multi-vhost |
| 6 | Laravel down → gateway last state | Manual / chaos |
| 7 | MySQL down → gateway last state | Manual |
| 8 | Invalid config → tidak apply | Agent unit + integration |
| 9 | Reload fail → sync failed | Mock apachectl exit 1 |
| 10 | Direct port backend tidak bypass suspend publik | Firewall doc + manual |

## 3. Authorization tests

Matrix role × endpoint untuk Application, Invoice, Payment, Gateway actions.

## 4. Payment verification tests

- Partial payments update invoice status
- Duplicate reference rejected
- Verified triggers ActivateApplicationListener (mock policy)

## 5. Concurrent update tests

- Parallel `ApplyGatewayConfigJob` same app → satu winner, idempotent outcome
- Parallel port assignment → one fails unique constraint

## 6. WebSocket tests

- New WS when suspended → fail
- Document behavior for existing connections (manual checklist)

## 7. Security tests

- Agent rejects invalid port/host
- No auth → 401 on Agent
- Path traversal in hostname rejected
- CSRF on admin forms

## 8. CI pipeline (rencana)

```yaml
# Konseptual
- composer install
- php artisan test
- render gateway fixtures
- docker run apache:2.4 apachectl -t
```

## 9. Definition of Done testing

Fitur gateway/suspend: semua skenario 1–6 otomatis atau scripted checklist signed off sebelum DONE di TASKS.md.
