# Uptime Monitor (Laravel)

A self-hosted UptimeRobot-style monitor for client websites and PPC landing pages.

- Add a client website → it finds **every page** automatically (robots.txt → sitemap.xml / sitemap indexes → falls back to homepage links), plus any landing pages you add by hand.
- Checks every page on a schedule (1–60 min per site), in parallel.
- Failed pages are re-checked after 3 s before alerting (no false alarms from blips).
- **Email alerts**: one email per site listing every page that went down, and another when they recover (with downtime duration).
- Optional **keyword check** on the homepage (catches "Account suspended" / blank pages that still return 200).
- **SSL expiry** warnings (default 14 days before).
- Dashboard: status, uptime 24h / 7d / 30d, response time chart, 24h uptime bar, incident log.
- Nightly re-scan of sitemaps so new landing pages are picked up automatically.

Requirements: PHP 8.3+, Composer, SQLite (default) or MySQL, and the ability to add a cron job.

---

## 1. Install

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
touch database/database.sqlite          # or set DB_CONNECTION=mysql + DB_* in .env
php artisan migrate --force
php artisan monitor:create-user you@brande.ae --name="Your Name"   # asks for a password
```

Point your web server's document root to the `public/` folder.

## 2. Configure `.env`

```env
APP_NAME="Uptime Monitor"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://uptime.yourdomain.com     # used for links in alert emails
APP_DISPLAY_TIMEZONE=Asia/Dubai

# Microsoft 365 / Outlook
MAIL_MAILER=smtp
MAIL_HOST=smtp.office365.com
MAIL_PORT=587
MAIL_USERNAME=alerts@yourdomain.com
MAIL_PASSWORD=your-password
MAIL_FROM_ADDRESS="alerts@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

- **Microsoft 365**: "Authenticated SMTP" must be enabled for that mailbox (Microsoft 365 admin → Users → mailbox → Mail → Manage email apps).
- **Gmail / Google Workspace**: `MAIL_HOST=smtp.gmail.com`, port `587`, and an **App Password** (not your normal password).
- Any SMTP provider (Mailgun, SendGrid, Brevo, your hosting's mail server) works the same way.

Then log in → **Settings** → enter alert emails → **Send test email**.

## 3. Add the cron job (required — this is what runs the checks)

```cron
* * * * * cd /path/to/uptime-monitor && php artisan schedule:run >> /dev/null 2>&1
```

On cPanel: *Cron Jobs* → "Once per minute" → `cd /home/USER/uptime-monitor && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1`.

The schedule (in `routes/console.php`):

| Command | When | What |
|---|---|---|
| `monitor:run` | every minute | checks sites whose interval is due, sends alerts |
| `monitor:discover` | daily 03:00 UTC | re-reads sitemaps (new/removed pages) + SSL expiry |
| `monitor:prune` | weekly | deletes check history older than 90 days |

You can run any of them by hand, e.g. `php artisan monitor:run --force` or `php artisan monitor:discover --site=3`.

## 4. Hosting options

- **VPS (DigitalOcean, Hetzner, Lightsail) with Laravel Forge or Ploi** — easiest; Forge adds the scheduler for you.
- **cPanel shared hosting** — works; upload, run `composer install`, set the doc root to `public/`, add the cron above.
- Use a different server/provider from your clients' sites, so the monitor isn't down at the same time as them.

## Tips

- **Cloudflare / firewall blocking the monitor (403)?** Settings → "Treat 401/403/429 as up", or whitelist the monitor's server IP in the client's firewall.
- **Pages you don't care about** (tag archives, author pages): open the site → *Exclude*. Excluded pages stay excluded after re-scans.
- **PPC landing pages not in the sitemap**: open the site → *Add landing pages manually* (one URL per line, or paths like `/lp/offer`).
- **Per-client recipients**: Edit a site → *Extra alert emails* (e.g. the account manager or the client).
- Large sites: limit with Settings → *Max pages per site* (default 200).

## Where things live

| Path | Purpose |
|---|---|
| `app/Services/UptimeChecker.php` | the checks, incidents, alerts, SSL check |
| `app/Services/PageDiscovery.php` | sitemap / homepage page discovery |
| `app/Mail/*`, `resources/views/mail/*` | alert emails |
| `app/Http/Controllers/*`, `resources/views/*` | dashboard |
| `routes/console.php` | artisan commands + schedule |
| `database/migrations/2026_11_02_000000_create_monitor_tables.php` | tables: sites, pages, incidents, site_checks, settings |
