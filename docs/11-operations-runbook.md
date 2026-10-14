# 11 — Runbook Operasi

## 1. Kontak & eskalasi

| Level | Tanggung jawab |
|-------|----------------|
| L1 | Administrator — status app, invoice |
| L2 | DevOps — Apache, tunnel, Agent |
| L3 | Architect — keputusan arsitektur / rollback major |

*(Isi nama kontak nyata di deployment produksi.)*

## 2. Prosedur: Suspend aplikasi client

1. Login Rental Manager → Applications → pilih app
2. Set status **Suspended**, isi alasan wajib
3. Pastikan sync status → **Applied** (refresh)
4. Verifikasi publik: halaman suspend, tidak ada response dari backend
5. Jika **Failed**: lihat §6 Retry

## 3. Prosedur: Aktivasi setelah pembayaran

1. Finance verifikasi payment → status Verified
2. Sistem (atau admin) set access **Active** sesuai kebijakan auto/manual
3. Konfirmasi gateway sync Applied
4. Smoke test URL client

## 4. Onboarding aplikasi & domain baru

1. Client record created
2. Application + primary domain + port allocated
3. Backend client running on `127.0.0.1:PORT`
4. Trigger gateway apply (active)
5. **Cloudflare:** add public hostname → gateway port
6. Client update DNS CNAME
7. Tunggu propagasi; health check green
8. Document in application deployment_meta

## 5. Maintenance mode

1. Set status Maintenance + komunikasi ke client
2. Sync gateway
3. Optional: wait WS drain (lihat [04-apache-gateway.md](04-apache-gateway.md))
4. Deploy backend client
5. Return to Active

## 6. Gateway sync failed

1. Buka Gateway Sync Logs di dashboard ( atau DB `gateway_sync_logs`)
2. Baca `error_message` / Agent log `/var/log/rental-gateway-agent/`
3. Perbaiki root cause (invalid template, port conflict, sudo)
4. Klik **Retry sync** (dispatch job, idempotency key baru)
5. Jika Apache broken: restore backup terakhir dari `rental-gateway/backups/`

## 7. Rental Manager tidak available

- **Traffic client:** tetap jalan dengan konfig terakhir
- **Admin:** restore app service (php-fpm, queue worker)
- **Tidak** restart Apache kecuali diagnosa gateway

## 8. cloudflared down

```bash
# Diagnosis read-only
systemctl status cloudflared
journalctl -u cloudflared -n 100 --no-pager
```

Restart service: **hanya dengan persetujuan**.

## 9. Backup rutin

| Asset | Frekuensi | Retention |
|-------|-----------|-----------|
| MySQL rental_gateway | daily | 30 hari |
| rental-gateway/backups | per apply | 90 hari |
| Agent logs | rotate weekly | 30 hari |

## 10. Indikator kesehatan

| Signal | Normal |
|--------|--------|
| Apache | active (running) |
| cloudflared | active |
| Agent /health | status ok |
| Queue workers | running under Supervisor |
| gateway_applied_status | match access_status |

## 11. Hal yang tidak dilakukan tanpa change ticket

- `systemctl restart apache2`
- Edit tunnel config produksi
- Upgrade PHP/MySQL major
- Hapus backup gateway
