# Supervisor — Schedule & Queue Worker

## Program

| Program | Perintah | Fungsi |
|---------|----------|--------|
| `rental-manager-schedule` | `php artisan schedule:work` | Monitoring health (5 menit), host metrics |
| `rental-manager-worker` | `php artisan queue:work database ...` | Job gateway sync (`ApplyGatewayConfigJob`) |
| `rental-gateway-agent` (opsional) | `gateway-agent/run.sh` | Agent Python; alternatif systemd |

## Prasyarat

```bash
cd /var/www/html/Laravel-Rental-Manager
# .env
QUEUE_CONNECTION=database

php artisan migrate --force   # tabel jobs
```

## Instalasi

```bash
sudo chmod +x deploy/install-supervisor.sh
sudo bash deploy/install-supervisor.sh

# Sertakan agent di Supervisor (bukan systemd):
# sudo INSTALL_GATEWAY_AGENT=1 bash deploy/install-supervisor.sh
```

## Operasi

```bash
sudo supervisorctl status
sudo supervisorctl restart rental-manager-schedule rental-manager-worker
sudo tail -f /var/log/supervisor/rental-manager-worker.log
```

## Setelah deploy kode

```bash
sudo supervisorctl restart rental-manager-worker
```

Worker `--max-time=3600` restart otomatis tiap jam; restart manual setelah `git pull` disarankan.

## Cron

**Jangan** menambah `* * * * * php artisan schedule:run` jika `rental-manager-schedule` sudah aktif — itu duplikat.

## Tombol Sinkronkan (UI)

Sync **manual** di halaman aplikasi tetap **langsung** (`applySyncLog`), tidak lewat antrian — agar admin dapat feedback segera.

Perubahan akses / buat aplikasi / verifikasi pembayaran → job **antrian** → worker.
