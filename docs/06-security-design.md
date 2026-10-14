# 06 — Desain Keamanan

## 1. Threat model (ringkas)

| Ancaman | Target | Kontrol |
|---------|--------|---------|
| Command injection via Agent | Server | Template fixed; no shell from user input |
| Path traversal | Config files | Canonical paths; chroot-like directory |
| SSRF dari Rental Manager | Internal network | Agent localhost only; block metadata URLs |
| Privilege escalation | Laravel → root | Dedicated user; sudoers minimal |
| Session hijack | Admin | HTTPS, secure cookies, rotation |
| IDOR | Data client | Policies per model |
| Payment fraud | Finance | Verification workflow; audit |
| Bypass suspend | Direct port | Firewall + bind localhost |
| Credential leak | Repo | .env gitignore; secrets manager |

## 2. Gateway Agent — model aman

### Identitas & transport

- Service bind **`127.0.0.1:8750`** (port internal, bukan publik)
- Autentikasi: **Bearer token** rotatable (hash di env Agent) + optional **mTLS** fase lanjutan
- Laravel hanya dari localhost / worker same-host

### Hak proses

- User sistem: `rental-gateway-agent` (dedicated)
- Grup: `www-data` read-only untuk validasi path (opsional)

### Sudo / capabilities (daftar operasi izin)

Via `/etc/sudoers.d/rental-gateway-agent`:

```
rental-gateway-agent ALL=(root) NOPASSWD: /usr/sbin/apache2ctl configtest
rental-gateway-agent ALL=(root) NOPASSWD: /usr/sbin/apache2ctl graceful
```

**Tidak** diizinkan: `systemctl restart apache2`, edit `/etc/apache2/sites-enabled/*` di luar `rental-gateway/`.

### Locking

- File lock `flock /var/run/rental-gateway-agent.apply.lock`
- Satu apply global atau per-node; timeout 120s

### Apply atomik

1. Render ke `apps/app-{id}.conf.tmp`
2. `rename()` ke final
3. `configtest`
4. `graceful` OR rollback rename dari backup

### Validasi input (Agent)

| Field | Validasi |
|-------|----------|
| application_id | UUID atau bigint dari allowlist file manifest |
| hostnames | regex DNS, lowercase, max length |
| port | integer range env |
| mode | enum active\|suspended\|maintenance |
| internal_host | whitelist 127.0.0.1 |

### Logging

- Structured JSON log ke `/var/log/rental-gateway-agent/`
- Jangan log token penuh

## 3. Laravel application

- CSRF semua form state-changing
- Rate limit login (5/menit/IP)
- Form Request validation
- Policies: ApplicationPolicy, InvoicePolicy, PaymentPolicy
- Mass assignment guarded
- Upload bukti: mime whitelist (pdf,jpg,png), scan size, storage private disk
- Queue jobs untuk gateway (no sync shell dari web request)

## 4. Database & Redis

- User DB least privilege (hanya schema rental_gateway)
- Redis requirepass di prod
- Jangan expose 3306/6379 ke publik

## 5. Apache & filesystem

- `rental-gateway/` owned root:rental-gateway-agent, mode 750
- Laravel user **tidak** write ke direktori tersebut
- Backup retention N hari (policy ops)

## 6. Admin access

Rekomendasi berlapis:

1. Cloudflare Access pada hostname admin **atau**
2. IP allowlist + 2FA (Laravel Fortify) **atau**
3. Akses hanya via VPN

MVP minimum: Fortify + strong password policy + RBAC + audit log.

## 7. Audit

`activity_logs` + `access_logs` untuk:

- Login/logout gagal
- Perubahan status aplikasi
- Verifikasi pembayaran
- Gateway sync hasil

## 8. Dependency & supply chain

- Composer audit / npm audit di CI
- Pin versi cloudflared dari repo resmi

## 9. Incident response (ringkas)

1. Isolate: suspend app terdampak via dashboard
2. Rotate Agent token + session admin
3. Restore config dari `backups/`
4. Post-mortem update [12-decision-log.md](12-decision-log.md)
