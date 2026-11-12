# NetBill — Installation Guide

One database per ISP. Each installation is fully independent.

## Requirements

- PHP 8.3+, Composer, MySQL 8 / MariaDB 10.6+
- MikroTik router(s) with API access (port 8728/8729)
- Public HTTPS URL reachable by routers and Safaricom (for M-Pesa callbacks)

## Setup

```bash
composer install --no-dev --optimize-autoloader   # add --dev on dev machines
cp .env.example .env
php artisan key:generate                            # REQUIRED — encrypts secrets
# edit .env: DB_*, APP_URL, APP_ENV=production, APP_DEBUG=false
php artisan migrate --seed                          # creates admin: admin@sterkedigital.com / password — CHANGE IT
php artisan storage:link
npm ci && npm run build                             # or: npm run dev for local
```

## Background processes (required)

```bash
php artisan queue:work --tries=3                    # QUEUE_CONNECTION=redis recommended
php artisan schedule:work                           # or cron: * * * * * php artisan schedule:run
```

`QUEUE_CONNECTION=sync` works for evaluation but runs STK/SMS/router jobs inside the HTTP request. For production use `redis` (or `database` + `php artisan queue:table && migrate`) and keep `queue:work` running — `sessions:sync` dispatches one job per router each minute.

On Windows/WAMP use Task Scheduler for both. On Linux use systemd units or Supervisor.

## M-Pesa (Daraja)

Set in `.env` or via admin → Settings:

```
MPESA_ENV=production|sandbox
MPESA_CONSUMER_KEY / MPESA_CONSUMER_SECRET / MPESA_SHORTCODE / MPESA_PASSKEY
MPESA_CALLBACK_URL=https://your-domain/api/mobile/m/callback
MPESA_CALLBACK_IPS=196.201.214.200,196.201.214.206   # default; "*" only for local dev
```

Safaricom whitelists your server's public IP for production Daraja — request go-live after sandbox testing.

## First router onboarding

1. Admin → Routers → Add router (name, identifier, IP, API username/password).
2. Use "Test connection", then "Setup hotspot" and "Upload hotspot files".
3. Re-upload hotspot files after changing `APP_URL` — the portal embeds it.

The hotspot API requires the per-router `hotspot_api_token` embedded in the uploaded portal files. Regenerate by rotating the token (DB) and re-uploading files.

## SMS (Vintex)

`VINTEX_API_URL`, `VINTEX_EMAIL`, `VINTEX_BEARER_TOKEN`, `VINTEX_SENDER_ID` in `.env`, or store `email|bearer_token` in Settings → SMS.

## Security checklist

- [ ] `APP_DEBUG=false`, `APP_ENV=production`
- [ ] Change seeded admin password immediately
- [ ] `APP_KEY` backed up securely — losing it makes encrypted secrets unrecoverable
- [ ] HTTPS enforced (callbacks + portal tokens travel in page source)
- [ ] Database backups scheduled (mysqldump cron)
