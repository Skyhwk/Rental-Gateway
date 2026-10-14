# 03 — Desain Database

## 1. ERD (konseptual)

```mermaid
erDiagram
    users ||--o{ model_has_roles : has
    roles ||--o{ model_has_roles : assigned
    roles ||--o{ role_has_permissions : has
    permissions ||--o{ role_has_permissions : granted

    users ||--o{ activity_logs : performs
    users ||--o{ payments : verifies

    clients ||--o{ applications : owns
    clients ||--o{ subscriptions : has
    clients }o--|| client_statuses : current

    applications ||--o{ application_domains : has
    applications ||--o{ access_logs : history
    applications ||--o{ gateway_sync_logs : sync
    applications ||--o{ gateway_config_versions : version
    applications ||--o{ subscriptions : optional
    applications }o--|| subscription_plans : plan

    subscription_plans ||--o{ subscription_plan_prices : price_history
    subscriptions ||--o{ subscription_price_snapshots : snapshot
    subscriptions ||--o{ invoices : generates

    invoices ||--o{ invoice_items : contains
    invoices ||--o{ payments : paid_by
    payments ||--o{ payment_allocations : allocates

    gateway_nodes ||--o{ applications : hosts

    users {
        bigint id PK
        string name
        string email UK
        string password
        timestamps deleted_at
    }

    clients {
        bigint id PK
        string company_name
        string pic_name
        string email
        string phone
        text address
        string status
        text internal_notes
        timestamps deleted_at
    }

    applications {
        bigint id PK
        bigint client_id FK
        bigint gateway_node_id FK
        string name
        string slug UK
        string internal_host
        unsigned_int internal_port UK
        string access_status
        string gateway_applied_status
        string health_status
        json deployment_meta
        text technical_notes
        timestamps deleted_at
    }

    application_domains {
        bigint id PK
        bigint application_id FK
        string hostname UK
        boolean is_primary
        timestamps
    }

    subscriptions {
        bigint id PK
        bigint client_id FK
        bigint application_id FK
        bigint subscription_plan_id FK
        date starts_at
        date ends_at
        date billing_anchor
        unsigned_int grace_days
        string status
        timestamps deleted_at
    }

    invoices {
        bigint id PK
        bigint subscription_id FK
        string invoice_number UK
        date period_start
        date period_end
        decimal amount
        decimal amount_paid
        date due_date
        string status
        timestamps deleted_at
    }

    payments {
        bigint id PK
        bigint invoice_id FK
        decimal amount
        date paid_at
        string method
        string reference_number UK
        string proof_path
        string verification_status
        bigint verified_by FK
        timestamps
    }

    access_logs {
        bigint id PK
        bigint application_id FK
        bigint user_id FK
        string from_status
        string to_status
        string reason
        string source
        timestamps
    }

    gateway_sync_logs {
        bigint id PK
        bigint application_id FK
        string idempotency_key UK
        string desired_status
        string sync_status
        text error_message
        bigint config_version_id FK
        timestamps
    }

    gateway_config_versions {
        bigint id PK
        bigint application_id FK
        string checksum
        text config_snapshot_path
        timestamps
    }

    activity_logs {
        bigint id PK
        bigint user_id FK
        string subject_type
        bigint subject_id
        string event
        json properties
        timestamps
    }
```

## 2. Tabel inti (ringkasan kolom)

### RBAC (Spatie Laravel Permission atau setara)

- `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`

### `clients`

| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | BIGINT PK | |
| company_name | VARCHAR(255) | |
| pic_name | VARCHAR(255) | |
| email | VARCHAR(255) | INDEX |
| phone | VARCHAR(50) | |
| address | TEXT | nullable |
| status | ENUM active,inactive,archived | |
| internal_notes | TEXT | nullable |
| deleted_at | TIMESTAMP | soft delete |

### `applications`

| Kolom | Tipe | Catatan |
|-------|------|---------|
| internal_host | VARCHAR(255) | default 127.0.0.1, whitelist |
| internal_port | INT UNSIGNED | UNIQUE per gateway_node_id |
| access_status | ENUM active,suspended,maintenance | desired |
| gateway_applied_status | ENUM + null | applied |
| health_status | ENUM unknown,up,down | dari monitor |
| deployment_meta | JSON | nullable |

**Unique:** `(gateway_node_id, internal_port)`, hostname via `application_domains.hostname`.

### `application_domains`

| Kolom | Tipe | Catatan |
|-------|------|---------|
| hostname | VARCHAR(255) | UNIQUE global (lowercase, punycode) |
| is_primary | BOOLEAN | satu primary per app |

### `subscription_plans` & histori harga

- `subscription_plans`: name, billing_interval (month/year), active flag
- `subscription_plan_prices`: plan_id, amount, effective_from, effective_to

### `subscriptions`

Status terpisah: `pending`, `active`, `past_due`, `cancelled`, `expired` — **bukan** sama dengan `access_status`.

### `invoices`

Status: `draft`, `unpaid`, `partial`, `paid`, `overdue`, `void`

### `payments`

- `reference_number` UNIQUE (nullable jika auto-generated internal ref)
- `verification_status`: pending, verified, rejected
- Idempotency: UNIQUE `(invoice_id, reference_number)` where not null

### `payment_allocations` (partial payment)

| payment_id | invoice_id | amount |

### `access_logs`

Immutable audit trail status akses.

### `gateway_sync_logs`

| sync_status | pending, applied, failed |
| idempotency_key | UUID dari job |

### `gateway_config_versions`

Simpan path backup atau hash isi file generated.

### `gateway_nodes` (future-ready)

| name | hostname | agent_url | token_hash |

## 3. Indeks & constraint

- INDEX `applications(client_id, access_status)`
- INDEX `invoices(status, due_date)`
- INDEX `gateway_sync_logs(application_id, created_at DESC)`
- FK ON DELETE RESTRICT untuk client → applications
- Transaksi DB untuk: verifikasi pembayaran + update invoice + optional activate

## 4. Race condition & idempotency

| Proses | Mekanisme |
|--------|-----------|
| Apply gateway | `idempotency_key` unique; job discard duplicate |
| Verify payment | `SELECT ... FOR UPDATE` pada invoice row |
| Port allocation | unique constraint + retry UI |
| Concurrent suspend/activate | optimistic lock `applications.version` column (optional) |

## 5. Relasi Eloquent (rencana)

- `Client hasMany Application, Subscription`
- `Application belongsTo Client; hasMany ApplicationDomain, AccessLog, GatewaySyncLog`
- `Subscription belongsTo Client, Application, SubscriptionPlan; hasMany Invoice`
- `Invoice hasMany Payment, PaymentAllocation`
- `Payment belongsTo Invoice; belongsTo User verifiedBy`

## 6. Seed data minimal

- Roles & permissions
- Super admin user (dari env saat deploy)
- Subscription plan contoh (non-prod)

## 7. Migrasi — urutan disarankan

1. users + RBAC
2. gateway_nodes (default row)
3. clients
4. applications + application_domains
5. subscription_plans + prices
6. subscriptions
7. invoices + items
8. payments + allocations
9. access_logs
10. gateway_config_versions + gateway_sync_logs
11. activity_logs
