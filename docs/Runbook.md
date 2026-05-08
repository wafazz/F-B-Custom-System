# Star Coffee — Operations Runbook

Internal reference for deploying, hot-fixing, and recovering the Star Coffee Phase 1 web platform.

---

## Stack snapshot

| Layer | Tech |
|---|---|
| App | Laravel 12.58 + PHP 8.4 |
| Frontend | Inertia 3 + React 19 + TypeScript + Tailwind 4 |
| Admin | Filament 3.3 at `/admin` |
| DB | MariaDB (MySQL-compatible) on port 3307 (local) |
| Cache / queue | Redis + Horizon |
| Real-time | Laravel Reverb 1.10 (WebSocket) |
| API auth | Sanctum |
| Push | minishlink/web-push (VAPID) |
| Build | Vite 7 + vite-plugin-pwa (`injectManifest`) |
| Errors | Laravel Pulse |
| Tests | Pest 3 + Larastan 3 (level 5) |

---

## Local dev quickstart

```bash
composer install
pnpm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed

# Three concurrent processes
php artisan serve --port=8000      # Laravel
pnpm run dev                       # Vite (binds 127.0.0.1:5173)
php artisan reverb:start           # WebSocket :8080
php artisan horizon                # Queue (optional in dev)
```

Default admin: `admin@starcoffee.test` / `password` at `/admin/login`.

---

## Production deploy

> Hosting decision pending (W-DEC-1). The flow below assumes a Linux VPS with Nginx + PHP-FPM + Supervisor.

1. `git pull` on the deploy branch (typically `main`).
2. `composer install --no-dev --optimize-autoloader`
3. `php artisan migrate --force`
4. `pnpm install --prod=false && pnpm run build`
5. `php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache`
6. `php artisan filament:cache-components`
7. Restart workers: `sudo supervisorctl restart all` (covers Horizon + Reverb).
8. Smoke test:
   - `curl https://app.example.com/up` → 200
   - `curl https://app.example.com/admin/login` → 200
   - `curl https://app.example.com/api/branches/1/menu` → JSON

### Required env vars (production)

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://...
DB_CONNECTION=mysql
DB_HOST=...
REDIS_HOST=...
BROADCAST_CONNECTION=reverb
REVERB_APP_KEY=...
REVERB_APP_SECRET=...
REVERB_HOST=...
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VAPID_SUBJECT=mailto:ops@yourdomain
VAPID_PUBLIC_KEY=...
VAPID_PRIVATE_KEY=...
VITE_VAPID_PUBLIC_KEY="${VAPID_PUBLIC_KEY}"
PAYMENT_DRIVER=stub  # switch to billplz when keys land
BILLPLZ_API_KEY=...
BILLPLZ_COLLECTION_ID=...
BILLPLZ_X_SIGNATURE=...
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
CACHE_STORE=redis
```

---

## Hotfix flow

1. Branch from `main` → `hotfix/<issue>`.
2. Write a failing Pest test that reproduces.
3. Fix the code.
4. Run `composer ci` (Larastan + Pest) and `pnpm run lint && pnpm run type-check && pnpm run build`.
5. Merge → deploy.
6. Verify smoke tests + admin Pulse for 30 min.

If the bug is data-related, add a one-shot artisan command (`app/Console/Commands/Fix...`) and run it once in production rather than editing rows manually.

---

## Rollback

Rollback by deploying the previous git tag and re-running migrations only if no new schema was added (i.e., usually skip `migrate`). Workers must be restarted after rollback.

```bash
git checkout v<previous-tag>
composer install --no-dev --optimize-autoloader
pnpm install && pnpm run build
php artisan optimize:clear && php artisan optimize
sudo supervisorctl restart all
```

If a migration is destructive and rollback requires reversing it, **first restore the DB from backup**, then deploy the previous code.

---

## Common ops

### Reset a customer PIN (POS)
Filament → Branches → edit → Staff Assignments → Reset PIN.

### Mark an item out-of-stock at a single branch
- Admin: Filament → Products → Stock per Branch → "Mark Out-of-Stock".
- Branch staff: `/pos/stock` → tap the green badge.
Both broadcast `BranchStockChanged` immediately.

### Refund an order
Filament → Orders → row action → Cancel (with reason). Stock is restored automatically; loyalty earn/redeem is reversed if order was Completed → Refunded.

### Generate / rotate a TV display token
Filament → TV Display Tokens → Regenerate. The previous URL is revoked. Copy the new URL into the kiosk browser.

### Force log a user out
Truncate their Sanctum tokens via tinker:
```php
App\Models\User::find($id)->tokens()->delete();
```
Web sessions: rotate `APP_KEY` only as a last resort (logs everyone out).

---

## Monitoring

- **Pulse:** `/pulse` — live queries, slow requests, exception rate, queue depth.
- **Horizon:** `/horizon` — job throughput, failed jobs (look for `SendWhatsAppJob` once W-7.3 ships).
- **Reverb:** `php artisan reverb:debug` — connection stats.
- **Application logs:** `storage/logs/laravel.log` (rotates daily).

Set up Sentry by setting `SENTRY_LARAVEL_DSN` in `.env`.

---

## On-call playbook

| Symptom | First check | Fix |
|---|---|---|
| Storefront blank, no console errors | `inertia-laravel` ↔ `@inertiajs/react` major-version match | Bump server adapter to v3+ (memory note `inertia-version-pairing`) |
| Real-time stock not updating | Reverb running? `lsof -i :8080` | `php artisan reverb:start` or supervisor restart |
| Push notifications not arriving | VAPID keys set? subscription rows present? | Check `services.webpush.public_key` env, `push_subscriptions` table |
| TV Display 403 | Token revoked or wrong branch | Regenerate token in Filament |
| POS PIN rejected | `branch_staff.is_active` = true? PIN was hashed? | Re-issue PIN via Filament |
| Orders stuck in Pending | Horizon running? Stub gateway responding? | `php artisan horizon`; verify `/orders/{id}/simulate-paid` works |

---

## Data exports & deletion (PDPA)

- **Right-to-access:** customer hits `GET /account/data-export` (auth required) → JSON download.
- **Right-to-erasure:** customer hits `DELETE /account` → soft-deletes user, drops push subscriptions, scrubs `customer_snapshot` on past orders. Order rows preserved for accounting compliance.

For staff-issued exports/deletions, log who initiated via Spatie ActivityLog.
