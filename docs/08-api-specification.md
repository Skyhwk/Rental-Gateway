# 08 — Spesifikasi API Internal (Laravel ↔ Gateway Agent)

**Bukan** API publik. Hanya jaringan loopback / private.

Base URL: `http://127.0.0.1:8750/v1` (konfigurable)

## Autentikasi

```
Authorization: Bearer <GATEWAY_AGENT_TOKEN>
X-Request-Id: <uuid>
X-Idempotency-Key: <uuid>   # wajib untuk POST /apply
```

## 1. POST /v1/apply

Terapkan konfigurasi satu aplikasi.

### Request body

```json
{
  "application_id": "550e8400-e29b-41d4-a716-446655440000",
  "hostnames": ["app-a.example.com", "www.app-a.example.com"],
  "internal_host": "127.0.0.1",
  "internal_port": 1610,
  "access_mode": "active",
  "websocket_enabled": false,
  "websocket_path": "/ws"
}
```

### Response 200 (applied)

```json
{
  "status": "applied",
  "config_version": "2026-10-14T12:00:00Z-abc123",
  "checksum": "sha256:...",
  "apache_configtest": "Syntax OK"
}
```

### Response 409 (lock busy)

Retry after header `Retry-After: 5`

### Response 422 (validation)

Field errors, tidak menyentuh Apache.

### Response 500 (failed after rollback)

```json
{
  "status": "failed",
  "error": "configtest failed",
  "stderr": "...",
  "rolled_back": true
}
```

## 2. POST /v1/reconcile

Baca manifest DB export atau batch payload — terapkan full sync (maintenance window). MVP optional; Phase 5.

## 3. GET /v1/health

```json
{
  "status": "ok",
  "last_apply_at": "2026-10-14T11:00:00Z",
  "lock_held": false
}
```

## 4. GET /v1/status/{application_id}

Status file on disk vs checksum terakhir.

## 5. Laravel side

- `GatewayAgentClient` interface + implementation HTTP
- `ApplyGatewayConfigJob` handles retry exponential backoff (3x)
- Update `gateway_sync_logs` + `applications.gateway_applied_status`

## 6. Webhook callback (opsional)

Agent POST ke Laravel `POST /internal/gateway/callback` dengan signature HMAC — jika Agent tidak blocking wait. MVP: synchronous response cukup.

## 7. Error codes

| HTTP | Arti |
|------|------|
| 401 | Token invalid |
| 403 | application_id tidak in manifest |
| 409 | Lock |
| 422 | Validation |
| 500 | Apply/rollback error |
