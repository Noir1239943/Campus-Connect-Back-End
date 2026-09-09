# CampusConnect — Backend

Laravel API backend for CampusConnect, a campus service-request tracker (transcripts, certificates, facility bookings, etc.). Served over [Laravel Octane](https://laravel.com/docs/octane) (RoadRunner) with a bearer-token API (Sanctum) consumed by a separate React frontend.

The frontend lives in its own repository: [Smart-Campus-Service-Request-Management-System](https://github.com/Noir1239943/Smart-Campus-Service-Request-Management-System).

## Prerequisites

- PHP 8.2+
- [Composer](https://getcomposer.org)
- [Node.js](https://nodejs.org) + npm (only needed to run `npx concurrently`, used by the dev script)
- Git

> **Windows note:** the `pcntl` PHP extension does not exist on Windows, so Laravel's `pail` log viewer cannot run here — it's intentionally left out of this project's dev script. Also, the `libsql`/Turso database driver crashes PHP's dev servers on Windows after a few requests, so local development uses plain SQLite instead (see setup below).

## First-time setup

```bash
git clone https://github.com/Noir1239943/Back-Up-Back-End.git
cd Back-Up-Back-End
composer install
copy .env.example .env        # (or `cp` on macOS/Linux)
php artisan key:generate
```

Then edit `.env`:

1. **Database** — switch to SQLite for local dev (the default `libsql` driver is for the deployed/Turso database and is unstable on Windows):
   ```
   DB_CONNECTION=sqlite
   ```
   The SQLite file itself isn't in the repo — create it, then migrate and seed some demo data:
   ```bash
   type nul > database\database.sqlite      # Windows (macOS/Linux: touch database/database.sqlite)
   php artisan migrate --seed
   ```
   `--seed` gives you three ready-to-use accounts (all password `password`):

   | Role | Login | Password |
   |---|---|---|
   | Student | `2023-04521` or `alex.santos@example.edu` | `password` |
   | Staff | `STAFF-0001` or `staff@example.edu` | `password` |
   | Admin | `ADMIN-0001` or `admin@example.edu` | `password` |

   Or skip `--seed` and just register your own account through the app instead — either works.

2. **Mail** (only needed to actually receive password-reset / email-verification emails) — leave `MAIL_MAILER=log` to just write emails to `storage/logs/laravel.log` instead of sending them, or configure real SMTP credentials (e.g. Gmail with an [App Password](https://myaccount.google.com/apppasswords)):
   ```
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=your-address@gmail.com
   MAIL_PASSWORD="your app password"
   MAIL_FROM_ADDRESS=your-address@gmail.com
   ```

3. Leave `APP_URL` and `FRONTEND_URL` as `http://localhost:8000` / `http://localhost:5173` for now — these only need to change for cross-device testing (see below).

## Running it locally (day-to-day)

One command starts the API server (Octane) and the queue worker together:

```bash
composer run dev
```

This runs on `http://localhost:8000`. Leave it running in its own terminal.

**Then, in a separate terminal, start the frontend** (from the *other* repo):

```bash
cd ../Smart-Campus-Service-Request-Management-System
npm install    # first time only
npm run dev
```

This runs on `http://localhost:5173` — open that in your browser. It already points at `http://localhost:8000/api` by default (see that repo's `.env`).

You now have both halves running. To stop either, `Ctrl+C` in its terminal.

### Running the tests

```bash
php artisan test
```

## Running this on another device (demos / cross-device testing)

By default, both dev servers only accept connections from *this* machine. To let a phone or another laptop on the same WiFi reach them (e.g. to test clicking an emailed verification link on that device), you need three things to match: the servers must listen on the network (not just `localhost`), both `.env` files must advertise your machine's actual network address, and Windows must not be blocking inbound connections on that network.

1. **Find this machine's LAN IP** (run in PowerShell):
   ```powershell
   ipconfig
   ```
   Look for the **IPv4 Address** under your active Wi-Fi/Ethernet adapter (e.g. `192.168.1.42`). This changes every time you join a different network — redo this step whenever you switch WiFi.

2. **Update both `.env` files** to that IP:

   In this repo's `.env`:
   ```
   APP_URL=http://<your-ip>:8000
   FRONTEND_URL=http://<your-ip>:5173
   ```

   In the frontend repo's `.env`:
   ```
   VITE_API_URL=http://<your-ip>:8000/api
   ```

3. **Restart both dev servers** so they pick up the new values — `composer run dev` already binds Octane to all interfaces (`--host=0.0.0.0`), so just stopping and re-running it and `npm run dev` again is enough.

4. **Set the WiFi network to "Private" in Windows**, not "Public" — this is the step that's easy to miss. Windows Firewall silently blocks inbound connections from other devices by default on networks marked Public (which includes most phone hotspots and some home routers, by default). Go to:

   **Settings → Network & Internet → Wi-Fi → click your network's name → Network profile type → Private**

   Without this, the other device's browser will just time out trying to reach either server, with no useful error message.

5. On the other device, connect to the **same WiFi network**, then open `http://<your-ip>:5173` in its browser.

If step 4 doesn't stick (some networks are locked to Public by Group Policy), the fallback is opening an elevated PowerShell and allowing the two ports directly:
```powershell
New-NetFirewallRule -DisplayName "Dev Vite 5173" -Direction Inbound -Protocol TCP -LocalPort 5173 -Action Allow
New-NetFirewallRule -DisplayName "Dev Octane 8000" -Direction Inbound -Protocol TCP -LocalPort 8000 -Action Allow
```

## API overview

Public: `/api/register`, `/api/login`, `/api/forgot-password`, `/api/reset-password`, `/api/email/verify/{id}/{hash}` (all rate-limited).

Authenticated (bearer token via Sanctum): `/api/me`, `/api/dashboard`, `/api/requests*`, `/api/offices`, `/api/request-types`, `/api/notifications*`, `/api/profile`, plus `/api/admin/*` (role-gated: `staff`/`admin`).

Filing a request (`POST /api/requests`) requires a verified email address.
