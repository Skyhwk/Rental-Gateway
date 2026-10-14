# Deploy panel via Apache (production)

`php artisan serve` hanya untuk development. Di server, Apache melayani folder `public/`.

## Instalasi sekali

```bash
cd /var/www/html/Laravel-Rental-Manager
sudo chmod +x deploy/install-apache-rental-manager.sh
sudo bash deploy/install-apache-rental-manager.sh
```

Default: **port 5152** → `http://127.0.0.1:5152/`

Port lain:

```bash
sudo RENTAL_MANAGER_PORT=8082 bash deploy/install-apache-rental-manager.sh
```

## `.env`

```env
APP_URL=http://127.0.0.1:5152
```

Jika diakses lewat domain/tunnel, sesuaikan `APP_URL` dengan URL yang dipakai browser.

```bash
php artisan config:clear
# production (opsional):
# php artisan config:cache
```

## Background (wajib operasional)

```bash
sudo bash deploy/install-supervisor.sh
```

- `rental-manager-schedule` — health check, billing otomatis
- `rental-manager-worker` — sync gateway (queue)

Apache **tidak** perlu di-restart setiap deploy kode; cukup `git pull` + `config:clear` / `migrate` jika perlu.

## Akses publik

Arahkan reverse proxy / Cloudflare tunnel ke `http://127.0.0.1:5152` (bukan ke `artisan serve`).

Log Apache: `/var/log/apache2/rental-manager-*.log`
