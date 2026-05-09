# Star Coffee — Multi-Branch F&B Platform

Phase 1 of a 3-phase build: a Laravel + Filament admin, an Inertia React PWA storefront, a tablet POS for branch staff, and a TV display for dine-in pickup numbers — all backed by a single Laravel API and broadcast over Reverb WebSockets.

> **Phase 1 (Web App + PWA): code-complete.**
> **Phase 2 (Native iOS/Android):** future, will reuse the same API.

---

## What's in the box

### Customer (PWA)
- Splash → branch picker → menu with real-time stock
- Persisted cart bound to a branch (switching branches prompts to clear)
- Modifier sheet with single/multi selection + price preview
- Cart → Checkout → live order status (Reverb)
- Pay with **Wallet** (instant) or **Billplz** (FPX / e-wallet)
- Loyalty: tier-multiplied points, redemption at checkout, tier progress
- Vouchers: percentage / fixed / per-user caps / branch scope
- Wallet: balance card, top-up via Billplz, signed transaction log
- Referral: unique codes, share via Web Share API / WhatsApp link, points to both parties on first order
- Web Push: order-ready and order-cancelled notifications
- PWA: installable on iOS/Android home screen, offline shell via service worker
- PDPA: data export (JSON), account delete (anonymise + soft delete)

### Branch POS (tablet)
- PIN login per branch (bcrypt-hashed PIN)
- Live order queue with sound chime on new orders
- One-tap status advance (Pending → Preparing → Ready → Completed)
- Stock toggle (broadcasts `BranchStockChanged` to the storefront)
- Walk-in order entry (touch product grid + cart sidebar + cash/card/DuitNow tender)

### TV Display
- Public token-protected URL per branch
- Two-panel "Now Preparing" / "Ready" layout, slide+flash animation
- Heartbeat endpoint for monitoring

### Admin (Filament `/admin`)
- Branches, staff, RBAC (Filament Shield)
- Catalog: categories, products, modifiers, branch availability + price overrides, branch stock with movement audit
- Orders (branch-scoped for branch managers), quick advance/cancel actions
- Vouchers, membership tiers
- Settings: Billplz credentials (encrypted at rest)
- Dashboard: today/week/month sales, top products, latest orders

---

## Stack

| Layer | Choice |
|---|---|
| Backend | Laravel 12 · PHP 8.4 |
| Admin panel | Filament 3 + Filament Shield 3 |
| Customer web | Inertia 3 + React 19 + TypeScript (strict) + Tailwind 4 |
| Build | Vite 7 + vite-plugin-pwa 1.3 (`injectManifest`) |
| State (web) | TanStack Query 5 + Zustand 5 (persisted) |
| Real-time | Laravel Reverb 1.10 + Echo + Pusher.js |
| Queue | Redis + Horizon 5 |
| Auth | Sanctum 4 (API tokens) + session (web) |
| RBAC | Spatie Permission 6 |
| Audit | Spatie ActivityLog 5 |
| Files | Spatie MediaLibrary 11 |
| Web Push | minishlink/web-push 10 (VAPID) |
| Payments | Billplz v3 (sandbox + live) — pluggable `PaymentGateway` interface, `StubGateway` for dev |
| API docs | Knuckles/Scribe 5 (`/docs`) |
| Tests | Pest 3 (130 passing) |
| Static analysis | Larastan 3 (level 5, clean) |
| DB | MariaDB / MySQL 8 |

---

## Getting started

### Prerequisites
- PHP 8.4, Composer 2
- Node 20+, pnpm or npm
- MariaDB / MySQL on `127.0.0.1` (default port `3306`; this repo's `.env` uses `3307` for XAMPP)
- Redis 7+
- (Recommended) [Laravel Herd](https://herd.laravel.com) on macOS — handles PHP, Nginx, and `.test` domains

### Install

```bash
git clone <repo-url> star-coffee
cd star-coffee

composer install
npm install            # or pnpm install

cp .env.example .env
php artisan key:generate
php artisan storage:link
```

Edit `.env` — at minimum:

```dotenv
APP_NAME="Star Coffee"
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=star_coffee
DB_USERNAME=root
DB_PASSWORD=

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

BROADCAST_CONNECTION=reverb
QUEUE_CONNECTION=redis

# Reverb (WebSockets)
REVERB_APP_ID=star
REVERB_APP_KEY=star_key
REVERB_APP_SECRET=star_secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

# Web Push (generate with: php artisan webpush:vapid)
VAPID_SUBJECT="mailto:admin@starcoffee.test"
VAPID_PUBLIC_KEY=...
VAPID_PRIVATE_KEY=...
VITE_VAPID_PUBLIC_KEY="${VAPID_PUBLIC_KEY}"

# Billplz — set in Admin → Settings → Payments (encrypted) instead of .env
SERVICES_PAYMENT_DRIVER=stub   # 'stub' for dev, 'billplz' for staging/prod
```

### Database

```bash
php artisan migrate --seed
```

Seeders create:
- 8 RBAC roles + 48 permissions
- 1 super-admin Filament user (see `DatabaseSeeder.php` for credentials)
- A starter branch and demo catalog

### Generate VAPID keys (one-off)

```bash
php artisan webpush:vapid     # writes VAPID_* into .env
```

### Run

In separate terminals:

```bash
php artisan serve --port=8000        # web app + API
php artisan reverb:start             # WebSockets (port 8080)
php artisan queue:work               # or: php artisan horizon
npm run dev                          # Vite + HMR
```

Open:
- **Customer PWA:** http://127.0.0.1:8000
- **Admin:** http://127.0.0.1:8000/admin
- **POS:** http://127.0.0.1:8000/pos/login
- **TV display:** http://127.0.0.1:8000/branch/{id}/display?token={token}
- **API docs:** http://127.0.0.1:8000/docs

---

## Roles

Seeded by `database/seeders/RolesAndPermissionsSeeder.php`:

| Role | Scope |
|---|---|
| `super_admin` | Full system access (Filament Shield bypass) |
| `hq_admin` | HQ-level access except billing |
| `mkt_manager` | Promotions, vouchers, loyalty, segments |
| `branch_manager` | Manages a single assigned branch only |
| `cashier` | POS access + own-branch orders |
| `barista` | Order queue access only |
| `customer` | Default end-user role |

Branch isolation for `branch_manager` is enforced in `BranchPolicy::scopeAllows()` and Filament resource queries.

---

## Architecture highlights

### Order flow

```
Customer cart  ─▶  POST /api/orders  ─▶  OrderService::place
                                            │
                                            ├─ DB::transaction
                                            │   ├─ lockForUpdate branch row
                                            │   ├─ validate stock
                                            │   ├─ apply voucher + loyalty redemption
                                            │   ├─ recompute SST on discounted subtotal
                                            │   ├─ create order + items + modifiers
                                            │   └─ if payment_method=wallet: debit + mark paid
                                            ▼
                                Billplz bill (gateway path)  OR  immediate paid (wallet path)
                                            │
                            Webhook ─▶ BillplzWebhookController
                            (resolves WalletTopup OR Order by reference)
                                            │
                                            ▼
                              OrderService::transition
                              fires events on:
                                orders.{id}                  (customer Echo)
                                branch.{id}.orders           (POS queue)
                                branch.{id}.display          (TV display)
                              and side effects:
                                LoyaltyService::earnFromOrder       (on Completed)
                                ReferralService::maybeAward...      (on Completed)
                                PushService::sendToUser             (on Ready / Cancelled)
                                Wallet refund                       (on Cancelled if wallet-paid)
```

### Wallet
- `wallets` (PK `user_id`, `balance`, `lifetime_topup`, `lifetime_spent`)
- `wallet_transactions` (signed `amount` + `balance_after` snapshot + morph `reference`)
- `wallet_topups` (Billplz reference, status enum)
- All mutations go through `WalletService` under `lockForUpdate()` inside `DB::transaction`
- `applyTopupPaid()` is idempotent — safe against duplicate webhooks

### Real-time channels
- `orders.{id}` — order status to a single customer
- `branch.{id}.orders` — full order queue for POS
- `branch.{id}.display` — `OrderQueuedForDineIn` / `OrderReadyForDineIn` for TV
- `branch.{id}.stock` — `BranchStockChanged` for storefront menu invalidation

---

## Payments

Driver is selected in **Admin → Settings → Payments**, persisted to the encrypted `settings` table, and resolved by `PaymentServiceProvider`.

- **`stub`** (dev): immediately marks orders paid via `/orders/{id}/simulate-paid?reference=…`
- **`billplz`** (staging/prod): real Billplz v3 with HMAC-SHA256 webhook signature verification
  - Server-to-server callback: `POST /api/billplz/webhook`
  - Customer return: `GET /payments/billplz/return/{order}`

Both drivers implement the same `PaymentGateway` interface, so swapping is one config change.

---

## Testing

```bash
./vendor/bin/pest                # 130 tests, ~22s
./vendor/bin/pest --filter=Wallet
./vendor/bin/phpstan analyse     # level 5
npm run lint
npm run type-check
npm run build
```

CI runs all four on every push (`.github/workflows/`).

---

## Deployment

Production runbook: [`docs/Runbook.md`](docs/Runbook.md) — covers deploy, rollback, hotfix, monitoring, on-call.

Sprint plan (W-0 → W-8): [`docs/Planning-Web.md`](docs/Planning-Web.md).
Master requirements: [`docs/Requirement.md`](docs/Requirement.md).

---

## Project layout

```
app/
  Filament/                Admin resources, pages, widgets
  Http/Controllers/
    Api/                   JSON endpoints (Sanctum + session)
    Web/                   Inertia page controllers
  Models/                  Eloquent models
  Policies/                Authorization (incl. branch isolation)
  Services/
    Orders/                OrderService — atomic placement + state machine
    Payments/              PaymentGateway interface + Billplz/Stub drivers
    Wallet/                WalletService — credit/debit/refund/topup
    Push/                  Web Push delivery
    Loyalty/, Referrals/   Side-effect services fired on order events
resources/js/
  pages/
    storefront/            Customer PWA pages
    pos/                   Tablet POS pages
    display/               TV display
  layouts/                 storefront-layout, pos-layout, app-layout, auth-layout
  stores/                  Zustand (cart, branch)
  hooks/                   useBranchMenu, useStockSubscription, etc.
  sw.ts                    Service worker (precache + push handler)
routes/
  web.php · api.php · channels.php
docs/
  Requirement.md  Planning-Web.md  Planning-Mobile.md  Runbook.md
```

---

## License

Proprietary — © Star Coffee. All rights reserved.
