# 02 — Arsitektur Sistem

## 1. Pemisahan Control Plane vs Data Plane

```mermaid
flowchart TB
    subgraph Internet
        U[Pengguna / Client Browser]
    end

    subgraph Cloudflare
        CF[Cloudflare Edge HTTPS]
    end

    subgraph ServerFisik["Server Fisik (Ubuntu)"]
        CD[cloudflared]
        subgraph DataPlane["Data Plane"]
            AP[Apache2 Rental Gateway]
            STATIC[Halaman Suspend / Maintenance]
            APP1[Client App :1610]
            APP2[Client App :1611]
            APPN[Client App :16xx]
        end
        subgraph ControlPlane["Control Plane"]
            RM[Laravel Rental Manager :5152]
            MY[(MySQL)]
            RD[(Redis)]
            WR[Queue Workers Supervisor]
        end
        GA[Gateway Agent Service]
    end

    U --> CF --> CD --> AP
    AP -->|active| APP1
    AP -->|active| APP2
    AP -->|active| APPN
    AP -->|suspended / maintenance| STATIC

    RM --> MY
    RM --> RD
    WR --> RD
    RM -->|HTTPS internal + token| GA
    GA -->|tulis config + apachectl| AP
```

## 2. Alur perubahan status akses

```mermaid
sequenceDiagram
    participant Admin as Admin UI
    participant RM as Laravel Rental Manager
    participant DB as MySQL
    participant Q as Redis Queue
    participant GA as Gateway Agent
    participant AP as Apache2

    Admin->>RM: Suspend aplikasi + alasan
    RM->>DB: TX: access_logs, applications.desired_status
    RM->>DB: gateway_sync_logs pending
    RM->>Q: ApplyGatewayConfigJob
    Q->>GA: POST /apply (idempotency-key)
    GA->>GA: Lock, backup, render template
    GA->>AP: apachectl configtest
    alt valid
        GA->>AP: graceful reload
        GA->>RM: applied + version id
        RM->>DB: applied_status, gateway_config_versions
    else invalid
        GA->>GA: rollback files
        GA->>RM: failed + error
        RM->>DB: sync failed, alert
    end
```

## 3. Komponen utama

| Komponen | Teknologi | Catatan |
|----------|-----------|---------|
| Rental Manager | Laravel 11.x (target), PHP 8.2+ | Modular monolith |
| Gateway | Apache 2.4 | Virtual host per hostname |
| Agent | Go atau Python (systemd) | Bukan di dalam request path publik |
| Queue | Redis + Supervisor | Apply gateway async |
| Edge | cloudflared | Hostname → `http://127.0.0.1:<gateway-port>` |

## 4. Model status (dua lapisan)

| Lapisan | Field konsep | Makna |
|---------|--------------|-------|
| Desired | `applications.access_status` | Keputusan bisnis admin/sistem |
| Applied | `applications.gateway_applied_status` | Keadaan di Apache setelah sync sukses |
| Sync | `gateway_sync_logs.status` | pending / applied / failed |

Kebijakan subscription (`subscription_status`, `invoice_status`) **tidak** langsung menjadi file Apache; service layer memetakan ke `access_status` sesuai aturan.

## 5. Struktur konfigurasi Apache (konseptual)

```
/etc/apache2/rental-gateway/
├── apps/                    # Include per aplikasi (generated)
│   └── app-{uuid}.conf
├── status/                  # Snippet mode: proxy vs static
├── static/
│   ├── suspend/
│   └── maintenance/
├── templates/               # Hanya dibaca Agent (immutable deploy)
├── backups/                 # Timestamp per apply
├── manifest.json            # Versi terakhir terapply (optional)
└── rental-gateway.conf      # Include utama (manual once)
```

Apache `IncludeOptional /etc/apache2/rental-gateway/apps/*.conf`

## 6. Routing hostname

- Satu `ServerName` / `ServerAlias` per kombinasi hostname unik.
- `ProxyPass / http://127.0.0.1:PORT/` hanya jika mode **active**.
- Mode **suspended**: `DocumentRoot` atau `Alias` ke static + `Require all granted` + header no-cache.
- WebSocket: `mod_proxy_wstunnel` + `ProxyPass /ws ws://127.0.0.1:PORT/ws` (path disesuaikan per app) — **hanya saat active**.

## 7. Skalabilitas ke multi-node (future)

Tanpa redesign total:

- `gateway_nodes` table + `applications.gateway_node_id`
- Agent per node dengan token unik
- Rental Manager dispatch job ke node yang benar
- Manifest & DNS/tunnel per node

MVP: satu node, `gateway_node_id` nullable/default.

## 8. Diagram deployment logis

```mermaid
flowchart LR
    subgraph Ports
        P5152[5152 Rental Manager]
        P8080[8080 Apache Gateway Listener]
        P1610[1610 App A]
        P1611[1611 App B]
    end
    CF2[cloudflared] --> P8080
    Admin[Admin Browser] --> CF2
    Admin --> P5152
    P8080 --> P1610
    P8080 --> P1611
```

**Rekomendasi:** Tunnel hanya ke port gateway Apache (mis. 8080), **bukan** ke port 5152 kecuali dengan Access policy ketat.

## 9. Integrasi modul Laravel (ringkas)

```
app/
├── Domain/
│   ├── Clients/
│   ├── Applications/
│   ├── Subscriptions/
│   ├── Billing/
│   └── Gateway/
├── Http/Controllers/
├── Policies/
├── Services/          # AccessControlService, GatewaySyncService, InvoiceService
├── Jobs/              # ApplyGatewayConfigJob, ReconcileGatewayJob
└── Events/Listeners/  # PaymentVerified → maybe ActivateApplication
```

Lihat [08-api-specification.md](08-api-specification.md) untuk kontrak Agent.
