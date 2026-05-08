# Star Coffee — Planning (Phase 1: Web App + PWA)

**Project:** Star Coffee — Multi-branch F&B Platform
**Phase:** 1 of 3 — Web App + PWA (Customer Web + Admin Portal + Branch POS)
**Stack:** Laravel 12 + Filament 3 + Inertia 3 + React 19 + TypeScript + Tailwind 4 + shadcn/ui
**Target Duration:** ~10 weeks
**Started:** 2026-05-08
**Task ID Prefix:** `W-` (Web)

---

## Status Legend
- `[ ]` — Pending / Not started
- `[~]` — In progress
- `[✔]` — Completed

## Traceability
Each task has a unique ID `W-{sprint}.{section}.{task}` for cross-referencing in commits, PRs, session memory, and bug reports.
Example commit: `feat(W-1.1.3): add branch operating hours JSON column`

---

## Sprint Overview

| Sprint | ID | Week | Focus | Status |
|---|---|---|---|---|
| Foundation | W-0 | 1 | Setup, packages, auth, CI/CD | [✔] |
| Branches & Staff | W-1 | 2 | Branch + staff CRUD + RBAC | [✔] |
| Menu & Catalog | W-2 | 3 | Categories, products, modifiers | [✔] |
| Customer Web Frontend | W-3 | 4 | Auth UI, home, browse, branch select | [✔] |
| Cart, Checkout, Orders | W-4 | 5 | Cart, Billplz, order tracking | [✔] |
| Branch POS + TV Display | W-5 | 6 | POS, order queue, walk-in, TV dine-in display | [✔] |
| Loyalty/Voucher/Dashboard | W-6 | 7 | Loyalty, vouchers, tiers, analytics | [✔] |
| PWA & Polish | W-7 | 8 | PWA, web push, referral | [✔] |
| Pilot & Launch | W-8 | 9-10 | QA, pilot branch, production launch | [✔ MVP code-side] |

---

## Sprint W-0 — Foundation (Week 1) [✔]

### W-0.1 Environment & Setup [✔]
- [✔] **W-0.1.1** PHP 8.4.10 (Herd) — exceeds 8.3 minimum
- [✔] **W-0.1.2** Node 20.19.4 LTS + npm 10.8.2
- [✔] **W-0.1.3** MariaDB running (MySQL-compatible drop-in)
- [✔] **W-0.1.4** Redis 8.4.0 running
- [✔] **W-0.1.5** Composer 2.8.10
- [✔] **W-0.1.6** pnpm installed

### W-0.2 Project Bootstrap [✔]
- [✔] **W-0.2.1** Laravel 12.58 created via `composer create-project laravel/laravel:^12.0`
- [✔] **W-0.2.2** Git repo initialized
- [✔] **W-0.2.3** `.env` configured (MySQL on port 3307, Redis, Reverb broadcast, Asia/Kuala_Lumpur timezone)
- [✔] **W-0.2.4** Timezone set in `.env` (APP_TIMEZONE=Asia/Kuala_Lumpur)
- [✔] **W-0.2.5** `.env.example` retained
- [✔] **W-0.2.6** First commit `chore(W-0.2): bootstrap Laravel 12 project`

### W-0.3 Core Packages Install [✔]
- [✔] **W-0.3.1** `laravel/sanctum` 4.3.2 — API auth (installed via `install:api`)
- [✔] **W-0.3.2** `laravel/horizon` 5.46 — queue dashboard
- [✔] **W-0.3.3** `laravel/reverb` 1.10 — WebSocket installed
- [✔] **W-0.3.4** `laravel/pulse` 1.7 — performance monitoring
- [✔] **W-0.3.5** `spatie/laravel-permission` 7.4 — RBAC migrated
- [✔] **W-0.3.6** `spatie/laravel-activitylog` 5.0 — audit logs migrated
- [✔] **W-0.3.7** `spatie/laravel-medialibrary` 11.22 — file uploads migrated
- [✔] **W-0.3.8** `filament/filament` 3.3.50 — admin panel installed at `/admin`
- [✔] **W-0.3.9** `inertiajs/inertia-laravel` 2.0.24 + `@inertiajs/react` 3.1
- [✔] **W-0.3.10** `tightenco/ziggy` 2.6.2 — route names in JS

### W-0.4 Frontend Setup [✔]
- [✔] **W-0.4.1** React 19 + TypeScript via Inertia (Laravel 12 default; planned 18 but 19 ships with kit)
- [✔] **W-0.4.2** Vite 7 + Tailwind 4 (Laravel 12 default; planned 3.4 but 4 is shipped, fully compatible)
- [✔] **W-0.4.3** shadcn/ui set up (Button, Input, Label, Card) + Radix primitives + lucide-react. Theme via Tailwind 4 CSS vars (Star Coffee amber/brown brand).
- [✔] **W-0.4.4** React Hook Form 7.75 + Zod 4.4 + `@hookform/resolvers`
- [✔] **W-0.4.5** TanStack Query 5.100 + Zustand 5.0
- [✔] **W-0.4.6** vite-plugin-pwa 1.3 + workbox-window — PWA manifest + SW generated on build
- [✔] **W-0.4.7** TypeScript strict mode in `tsconfig.json` (with `@/*` path alias)
- [✔] **W-0.4.8** ESLint 10 (typescript-eslint, react, hooks, refresh) + Prettier with Tailwind plugin

**Verified:** Build clean, type-check clean, lint clean, welcome + login + register pages render Inertia + React + Tailwind successfully.

### W-0.5 Project Structure & Conventions [✔]
- [✔] **W-0.5.1** Folder structure: `resources/js/{pages,components/ui,layouts,lib,hooks,types}`
- [✔] **W-0.5.2** Base layouts created: `app-layout.tsx`, `auth-layout.tsx`
- [✔] **W-0.5.3** Error pages — Inertia `errors/error.tsx` mapped via `bootstrap/app.php` exception handler (403/404/500/503)
- [✔] **W-0.5.4** Maintenance mode handled via Laravel default
- [✔] **W-0.5.5** Logging channels — Laravel default stack
- Inertia shared data: `auth.user`, `flash`, `name`, `ziggy` routes
- Global TypeScript types: `User`, `PageProps`, `Flash` + module augmentation for `usePage()`

### W-0.6 Auth Scaffolding [✔ MVP]
- [✔] **W-0.6.1** User table extended: phone, phone_verified_at, dob, gender, photo, referral_code (auto-generated 8 chars unique), referred_by, preferred_branch_id, marketing/whatsapp/push consent, locale, soft deletes
- [✔] **W-0.6.2** Login (email + phone) — controller + Inertia page + tests
- [✔] **W-0.6.3** Registration with referral code support — controller + Inertia page + tests
- [ ] **W-0.6.4** Phone OTP flow (deferred to W-3.1 sprint)
- [ ] **W-0.6.5** Forgot password (deferred to W-3.1 sprint)
- [ ] **W-0.6.6** 2FA for admin (deferred — Filament 3 has built-in support)
- Logout controller + route ready
- Filament admin user created: `admin@starcoffee.test` / `password`

### W-0.7 CI/CD & Quality [✔]
- [ ] **W-0.7.1** GitHub repo (pending W-DEC-2)
- [✔] **W-0.7.2** GitHub Actions workflow `.github/workflows/ci.yml` — backend (PHP 8.3 + 8.4 matrix) + frontend (lint, type-check, format-check, build)
- [✔] **W-0.7.3** Pest 3.8 configured. **7 tests passing** (login, register, referral, login-by-email/phone, wrong password)
- [✔] **W-0.7.4** Larastan 3.9 at level 5. **0 errors** on app/config/database/routes
- [ ] **W-0.7.5** Husky pre-commit hook (deferred — pnpm scripts cover most needs)

### W-0.8 Deployment Prep
- [ ] **W-0.8.1** Provision VPS (decision pending)
- [ ] **W-0.8.2** Setup Cloudflare DNS
- [ ] **W-0.8.3** Configure staging subdomain
- [ ] **W-0.8.4** SSL via Let's Encrypt or Cloudflare
- [ ] **W-0.8.5** Setup deploy script (Envoyer / Forge / manual)

---

## Sprint W-1 — Branches & Staff (Week 2) [✔]

### W-1.1 Branch Module (Admin) [✔]
- [✔] **W-1.1.1** Migration: `branches` (name, code, address, lat/lng, phone, email, postal/city/state, operating_hours JSON, sst, status, accepts_orders, sort_order, soft deletes)
- [✔] **W-1.1.2** Branch model + factory + seeder (3 KL branches seeded)
- [✔] **W-1.1.3** Operating hours per day — JSON column with `defaultOperatingHours()` helper + `isOpenNow()` checker
- [✔] **W-1.1.4** Filament resource: list, create, edit, soft delete + restore
- [✔] **W-1.1.5** Branch status toggle (`accepts_orders` quick action) + status enum (active/closed/maintenance)
- [ ] **W-1.1.6** Map pin picker (Google Maps API) — deferred (needs API key, W-DEC pending)
- [✔] **W-1.1.7** Branch image upload (cover + logo) via Filament FileUpload

### W-1.2 Staff Module (Admin) [✔]
- [✔] **W-1.2.1** Migration: `branch_staff` pivot (user_id, branch_id, pin hashed, employment_type, hired_at, ended_at, is_active, is_primary)
- [✔] **W-1.2.2** Filament management via two RelationManagers — `BranchResource→StaffRelationManager` + `UserResource→BranchesRelationManager`
- [✔] **W-1.2.3** PIN reset flow — generates 6-digit PIN, hashes, shows once via persistent notification
- [✔] **W-1.2.4** Suspend/Reinstate toggle (per-branch via `is_active` pivot) + Ban/Unban for User account (soft delete)
- [✔] **W-1.2.5** Multi-branch assignment via pivot (unique user_id+branch_id pair enforced)
- [✔] **W-1.2.6** Soft delete preserves history (User has SoftDeletes; pivot rows preserved)

### W-1.3 RBAC [✔]
- [✔] **W-1.3.1** Spatie Permission seeder with 8 roles + 96 resource permissions (12 actions × 8 resources after W-2)
- [✔] **W-1.3.2** Roles: super_admin, hq_admin, ops_manager, mkt_manager, branch_manager, cashier, barista, customer
- [✔] **W-1.3.3** Filament Shield 3.9 plugin enabled — permission UI at `/admin/shield/roles`
- [✔] **W-1.3.4** Policy classes: BranchPolicy, BranchStaffPolicy, UserPolicy, RolePolicy (auto-discovered)
- [✔] **W-1.3.5** Branch-scoped isolation — `BranchPolicy::scopeAllows()` + `BranchResource::getEloquentQuery()` filter for branch_manager role

**Sprint W-1 Verified:** 25 tests passing, PHPStan level 5 clean, Pint formatted.

---

## Sprint W-2 — Menu & Catalog (Week 3) [✔]

### W-2.1 Category & Product Models [✔]
- [✔] **W-2.1.1** Migration: `categories` (parent_id, name, slug, image, icon, sort_order, status, soft deletes)
- [✔] **W-2.1.2** Migration: `products` (category_id, name, slug, description, sku unique, base_price, sst_applicable, image, gallery JSON, calories, prep_time_minutes, status enum, is_featured, soft deletes)
- [✔] **W-2.1.3** Migrations: `modifier_groups` + `modifier_options` + `product_modifier_group` pivot
- [✔] **W-2.1.4** Migration: `branch_product` pivot (is_available + price_override)
- [✔] **W-2.1.5** Models with relations: Category, Product (+ availableAtBranch + priceForBranch), ModifierGroup/Option, BranchStock + StockMovement
- [✔] **W-2.1.6** `MenuSeeder` — 5 categories, 4 modifier groups, 13 options, 14 products, 42 branch-product + 42 stock rows

### W-2.2 Filament Resources [✔]
- [✔] **W-2.2.1** CategoryResource — image, parent picker, slug auto-fill
- [✔] **W-2.2.2** ProductResource — image + gallery (multi-upload reorderable), modifier picker, status enum, featured quick action
- [✔] **W-2.2.3** ModifierGroupResource + OptionsRelationManager (reorderable)
- [✔] **W-2.2.4** Branch availability toggle per product — `BranchAvailabilityRelationManager` (with "Attach to all branches" bulk)
- [✔] **W-2.2.5** Branch-specific price override in same RelationManager
- [ ] **W-2.2.6** Bulk import products (CSV) — deferred
- [ ] **W-2.2.7** Bulk price update — deferred to W-6 dashboard sprint









- [✔] **W-2.3.1** Migration: `branch_stock` (quantity, low_threshold, is_available, track_quantity, last_restocked_at) + `stock_movements` audit table
- [✔] **W-2.3.2** Stock management UI in Filament — `StockRelationManager` on ProductResource
- [✔] **W-2.3.3** Mark out-of-stock toggle — quick action that broadcasts BranchStockChanged
- [ ] **W-2.3.4** Low stock alert email/notification — deferred (depends on W-DEC-6 email provider)
- [✔] **W-2.3.5** Branch-scoped menu API endpoint — `GET /api/branches/{branch}/menu`
- [✔] **W-2.3.6** Eloquent scope `Product::availableAtBranch($branchId)`
- [✔] **W-2.3.7** Real-time stock event `BranchStockChanged` on `branch.{id}.stock` channel
- [ ] **W-2.3.8** Stock decrement on order confirmation — `applyMovement()` ready; OrderService wiring deferred to W-4
- [ ] **W-2.3.9** Stock restore on order cancel — deferred to W-4
- [✔] **W-2.3.10** Stock audit log — `stock_movements` table + Spatie ActivityLog

**Sprint W-2 Verified:** 40 tests passing (15 new MenuTest + 25 prior), PHPStan level 5 clean, Pint formatted. Permissions matrix expanded to 96 (8 resources × 12 actions).

---

## Sprint W-3 — Customer Web Frontend (Week 4) [✔]

### W-3.1 Customer Auth + Onboarding Flow (Inertia + React) [✔ MVP]

**Canonical flow:** Splash → Session Check → Branch Select → Storefront

- [✔] **W-3.1.1** Splash page (`storefront/splash.tsx`) with brand animation + 1.2s auto-redirect
- [✔] **W-3.1.2** Splash uses Inertia shared `auth` + persisted Zustand `branchStore` to decide next route
- [✔] **W-3.1.3** Login page (email/phone tabs) — exists from W-0.6
- [✔] **W-3.1.4** Register page with referral code field — exists from W-0.6
- [✔] **W-3.1.5/6** Cross-links between login/register — exists from W-0.6
- [ ] **W-3.1.7** OTP verification — deferred to W-3.x patch (needs SMS provider)
- [ ] **W-3.1.8** Forgot password — deferred (needs email provider W-DEC-6)
- [ ] **W-3.1.9** Profile setup page — deferred to W-6 (loyalty/profile sprint)
- [✔] **W-3.1.10/11** Auto-redirect logic: splash → branches if no branch selected → storefront once selected
- [✔] **W-3.1.12** Guest browse mode — storefront/menu routes are public
- [✔] **W-3.1.13** App layout (`storefront-layout.tsx`) — header w/ logo, branch picker, cart badge, login link
- [✔] **W-3.1.14** Mobile bottom nav — Home, Order, Loyalty, Profile (active state by URL)

### W-3.2 Home Page [deferred to W-6/W-7]
- [ ] **W-3.2.1-6** Banner carousel, featured grid, order-again, loyalty preview, vouchers shortcut — deferred. Splash currently routes straight to storefront.

### W-3.3 Menu Browse (Branch-Filtered) [✔]
- [✔] **W-3.3.1** Storefront menu page (`storefront/menu.tsx`) — category pills + product list, branch-scoped
- [✔] **W-3.3.2** Product detail surfaced via bottom-sheet (no separate page; modifier sheet shows all info)
- [✔] **W-3.3.3** ModifierSheet component — single (pick one) + multiple (min/max) with default selection + price preview
- [✔] **W-3.3.4** Add-to-cart writes to Zustand `cartStore` (persisted, branch-bound)
- [ ] **W-3.3.5** Search with filters — deferred (small menu fits in scroll for MVP)
- [✔] **W-3.3.6** Out-of-stock indicator — `useStockSubscription` hook listens to Reverb `branch.{id}.stock` channel for `stock.changed`
- [ ] **W-3.3.7** Favorites — deferred to W-6
- [✔] **W-3.3.8** Branch-specific pricing — `MenuPayload.products[].price` already comes from `priceForBranch()`
- [✔] **W-3.3.9** Empty states — closed branch, loading skeleton, error state
- [✔] **W-3.3.10** Branch switcher clears cart — `cartStore.rebindBranch()` returns true if cart was wiped, UI alerts user

### W-3.4 Branch Selection [✔ MVP]
- [✔] **W-3.4.1** Branch list page (`storefront/branch-select.tsx`) — list-only (map deferred, needs Google Maps API key — W-DEC pending)
- [ ] **W-3.4.2** Geolocation auto-detect — deferred
- [ ] **W-3.4.3** Distance + ETA — deferred (needs map API)
- [✔] **W-3.4.4** Operating hours validation — `is_open_now` computed from `Branch::isOpenNow()`, badge in UI
- [✔] **W-3.4.5** Branch switcher in storefront header — active branch chip linking back to selection

**Sprint W-3 Verified:** 44 tests passing (4 new StorefrontTest), TypeScript strict clean, ESLint clean, Prettier clean, Vite build OK, PHPStan level 5 clean.

**Frontend infra delivered:** Echo + Pusher (Reverb), TanStack Query provider, Zustand `branchStore` + `cartStore` (persisted), shadcn `Sheet` + `Badge` primitives, `StorefrontLayout` (sticky header + bottom nav + cart badge), `ProductCard`, `ModifierSheet`, `useBranchMenu` + `useStockSubscription` hooks.

---

## Sprint W-4 — Cart, Checkout & Orders (Week 5) [✔]

### W-4.1 Cart (Branch-Bound) [✔]
- [✔] **W-4.1.1** Cart state in Zustand `cartStore` (persisted, branch-bound) — already shipped in W-3
- [✔] **W-4.1.2** Line item edit (increment/decrement) + remove on `storefront/cart.tsx`
- [ ] **W-4.1.3** Voucher input — deferred to W-6 (vouchers sprint)
- [ ] **W-4.1.4** Loyalty redemption slider — deferred to W-6
- [✔] **W-4.1.5** Order notes textarea
- [ ] **W-4.1.6** Pickup time selector (scheduled) — defaults to ASAP; scheduled deferred to W-7 polish
- [✔] **W-4.1.7** Cart totals — subtotal + SST + grand total
- [✔] **W-4.1.8** Cart validation on checkout — `OrderService::place` re-validates branch + stock + availability under transaction
- [✔] **W-4.1.9** Stock conflict — `Insufficient stock` / `out of stock` errors surface to checkout UI
- [✔] **W-4.1.10** Branch switch warning — `cartStore.rebindBranch()` clears + alerts (shipped in W-3)

### W-4.2 Order Backend [✔]
- [✔] **W-4.2.1** Migrations: `orders` (number, branch, user, type, table, status enum, payment status enum, totals, timestamps for each state), `order_items`, `order_item_modifiers`
- [✔] **W-4.2.2** `OrderService::place(OrderPayload)` — atomic calc (line × modifiers × SST), stock validation, single transaction with `lockForUpdate()`
- [✔] **W-4.2.3** State machine via `OrderStatus` enum + `allowedTransitions()` + `OrderService::transition()` (Pending → Preparing → Ready → Completed; Cancel from any non-terminal; Refunded only from Completed)
- [✔] **W-4.2.4** `OrderStatusChanged` event broadcasts on `orders.{id}` + `branch.{id}.orders` channels with `order.status.changed`
- [✔] **W-4.2.5** Branch-prefixed order number `{CODE}-{YYMMDD}-{####}` via `Order::generateNumber()`
- [✔] **W-2.3.8/9 wired** — `applyMovement('sale', -qty)` on placement; `'adjustment', +qty` on cancellation

### W-4.3 Checkout & Payment [✔ MVP]
- [✔] **W-4.3.1** Checkout page (`storefront/checkout.tsx`) — totals breakdown
- [✔] **W-4.3.2** Order type selector — Pickup / Dine-in radio cards
- [✔] **W-4.3.3** Dine-in: table number input
- [ ] **W-4.3.4** Pickup time scheduling — deferred (ASAP only for MVP)
- [✔] **W-4.3.5** Payment gateway abstraction — `PaymentGateway` contract + `StubGateway` for dev; Billplz adapter slot ready
- [✔] **W-4.3.6** `POST /api/orders` → places order + creates payment bill
- [ ] **W-4.3.7** Real Billplz webhook signature verify — adapter contract has `verifyWebhook()`; concrete impl deferred until credentials land
- [ ] **W-4.3.8/9** DuitNow QR + eWallet redirect — Billplz handles these once integrated
- [✔] **W-4.3.10** Order confirmation page (`storefront/order.tsx`) with order number + status timeline + dine-in table / pickup info
- [ ] **W-4.3.11** Receipt email — deferred (needs W-DEC-6 email provider)
- [✔] **W-4.3.12** Order broadcast — `OrderStatusChanged` already broadcasts on `branch.{id}.orders`; POS queue UI in W-5, TV display in W-5.7
- [✔] **Dev simulate-paid** — `/orders/{order}/simulate-paid?reference=…` marks paid + auto-advances to Preparing (Billplz-replaceable)

### W-4.4 Order Tracking [✔]
- [✔] **W-4.4.1** Live order detail page with status pills + payment badge + items + totals
- [✔] **W-4.4.2** Echo subscription on `orders.{id}` channel for `.order.status.changed`
- [✔] **W-4.4.3** Order history page (`storefront/orders.tsx`) — `/orders` route auth-gated, latest 30
- [ ] **W-4.4.4** Reorder button — deferred to W-7 polish
- [ ] **W-4.4.5** Customer-facing cancel within X minutes — deferred (admin can cancel from Filament for now)

**Sprint W-4 Verified:** 59 tests passing (15 new OrderTest), PHPStan level 5 clean, ESLint+TypeScript+Prettier+Vite build all clean. Permissions matrix at 108 (9 resources × 12 actions) — `order` resource added, cashier/barista now get `view_*_order + update_order`.

---

## Sprint W-5 — Branch POS + TV Display (Week 6) [✔]

### W-5.1 POS Auth & Layout [✔]
- [✔] **W-5.1.1** Staff PIN login screen — branch picker + 4-6 digit PIN against `branch_staff` hashed pin
- [✔] **W-5.1.2** Tablet-optimized POS layout (landscape, dark slate, large touch targets) via `pos-layout.tsx`
- [✔] **W-5.1.3** Active staff name in header + Logout action
- [ ] **W-5.1.4** Shift session tracking — deferred (post-pilot enhancement)

### W-5.2 Order Queue [✔]
- [✔] **W-5.2.1** Live incoming orders panel — Echo subscription on `branch.{id}.orders` for `.order.status.changed`
- [✔] **W-5.2.2** Sound + visual alert on new order (audio chime + animated bump on key)
- [✔] **W-5.2.3** Status update buttons — Accept→Preparing, Mark Ready, Complete (single advance button per row)
- [✔] **W-5.2.4** Cancel order action with confirmation
- [ ] **W-5.2.5** Estimated prep time entry — deferred (auto from product.prep_time_minutes for now)

### W-5.3 Walk-in POS [✔ MVP]
- [✔] **W-5.3.1** Quick product grid (touch-optimized) — category pills + product tiles
- [✔] **W-5.3.2** Modifier selection — auto-picks defaults / required minimums on tap (no separate sheet for speed)
- [✔] **W-5.3.3** Cart sidebar with line edit/remove + totals
- [✔] **W-5.3.4** Cash payment flow — places order, marks paid, advances to Preparing
- [✔] **W-5.3.5** Card / DuitNow payment buttons (records method; gateway integration in W-7 polish)
- [ ] **W-5.3.6** DuitNow QR display — needs Billplz integration (W-7)
- [ ] **W-5.3.7** Print receipt — needs thermal printer driver (post-pilot)
- [ ] **W-5.3.8** Discount apply — deferred to W-6 voucher sprint
- [ ] **W-5.3.9** Refund / void flow — admin-side cancel exists; customer-facing refund deferred

### W-5.4 Customer Lookup [deferred to W-6]
- [ ] **W-5.4.1-4** Phone/membership search, profile lookup, in-store loyalty/voucher apply — depends on loyalty system from W-6.

### W-5.5 Branch Stock Self-Management [✔]
- [✔] **W-5.5.1** Stock screen at `/pos/stock` — own branch only (session-scoped)
- [✔] **W-5.5.2/3** One-tap available/out-of-stock toggle
- [ ] **W-5.5.4** Quantity adjustment with reason — admin Filament UI exists; POS-side adjust UI deferred
- [✔] **W-5.5.5** Audit via `stock_movements` table + Spatie ActivityLog (from W-2)
- [✔] **W-5.5.6** Low quantity visual highlight in stock table
- [✔] **W-5.5.7** Toggle broadcasts `BranchStockChanged` → customer storefront updates instantly
- [ ] **W-5.5.8** End-of-day summary report — deferred to W-6 dashboards

### W-5.6 Order-Ready Notification Fork [✔ partial]
- [✔] **W-5.6.1** Fork in `OrderService::transition` — reads `order_type`
- [ ] **W-5.6.2** Pickup path — Web Push + WhatsApp deferred to W-7.2/7.3 (notification sprint)
- [✔] **W-5.6.3** Dine-in path — `OrderQueuedForDineIn` (on Preparing) + `OrderReadyForDineIn` (on Ready) broadcast on `branch.{id}.display` with audio chime + slide animation on TV board
- [ ] **W-5.6.4** Notification log table — deferred to W-7
- [ ] **W-5.6.5** Mixed-mode fallback — deferred to W-7

### W-5.7 TV Display Screen [✔]

#### W-5.7.A Backend [✔]
- [✔] **W-5.7.1** Migration: `branch_display_tokens` (branch_id, name, token, is_active, last_seen_at, settings JSON)
- [✔] **W-5.7.2** `BranchDisplayTokenResource` — generate/regenerate/disable/copy URL actions
- [✔] **W-5.7.3** Public route `GET /branch/{branch}/display?token=…`
- [✔] **W-5.7.4** Token validation: must match branch + `is_active=true`; updates `last_seen_at`
- [✔] **W-5.7.5** Echo channel `branch.{id}.display`
- [✔] **W-5.7.6** `OrderQueuedForDineIn` + `OrderReadyForDineIn` events firing from state machine (auto-clear deferred)
- [ ] **W-5.7.7** Auto-clear cron — deferred (manual reset works; cron in W-7 polish)
- [✔] **W-5.7.8** Heartbeat endpoint `POST /branch/{branch}/display/heartbeat` updates `last_seen_at`

#### W-5.7.B Frontend [✔]
- [✔] **W-5.7.9** Full-screen kiosk layout, landscape-friendly
- [✔] **W-5.7.10** Hidden cursor / no scroll layout
- [✔] **W-5.7.11** Two-panel split — Now Preparing (left) + Ready (right, large bold)
- [✔] **W-5.7.12** Slide + flash animation when number moves to Ready
- [✔] **W-5.7.13** Audio chime via `<audio>` (Web Audio simple data URI placeholder)
- [✔] **W-5.7.14** Branch logo + name header
- [✔] **W-5.7.15** Live clock + date footer
- [ ] **W-5.7.16** Idle banner rotation — deferred to W-7 polish
- [✔] **W-5.7.17** Connection status indicator (green/red wifi icon)
- [ ] **W-5.7.18/22** Auto-reconnect / polling fallback — deferred (Echo handles basic reconnect)
- [ ] **W-5.7.19/20/21** Dark mode toggle / number masking / EN-BM — deferred (settings JSON column in place for later)

#### W-5.7.C Admin Panel [✔ MVP]
- [✔] **W-5.7.23** Per-branch token CRUD (Filament): generate, regenerate, copy URL, enable/disable
- [✔] **W-5.7.24** Last-seen-at column shows online/offline status
- [ ] **W-5.7.25/26** Preview iframe + reset display action — deferred

#### W-5.7.D Setup Documentation [deferred]
- [ ] **W-5.7.27-29** Setup + hardware + troubleshooting docs — write at handoff (post-W-8).

**Sprint W-5 Verified:** 74 tests passing (15 new PosTest), PHPStan level 5 clean, ESLint+TypeScript+Prettier+Vite build all clean.

### W-5.7 TV Display Screen (Dine-in Order Number Board)

**Surface:** Public branch-mounted display (TV / monitor / large iPad)
**Tech:** Browser kiosk loading `/branch/{branchId}/display` — full-screen, no auth, branch-scoped token

#### W-5.7.A Backend
- [ ] **W-5.7.1** Migration: `branch_display_tokens` (branch_id, token, expires_at, name)
- [ ] **W-5.7.2** Filament resource: generate/revoke display tokens per branch
- [ ] **W-5.7.3** Public route: `GET /branch/{branchId}/display?token={token}`
- [ ] **W-5.7.4** Token validation middleware (no user auth, but token must match branch)
- [ ] **W-5.7.5** Reverb private channel: `branch.{branchId}.display`
- [ ] **W-5.7.6** Broadcast events:
  - `OrderQueuedForDineIn` (now preparing)
  - `OrderReadyForDineIn` (ready panel)
  - `OrderClearedFromDisplay` (auto-clear after timeout)
- [ ] **W-5.7.7** Auto-clear job (cron every minute) — remove old "Ready" entries past timeout
- [ ] **W-5.7.8** Health ping endpoint (display sends heartbeat → admin sees online status)

#### W-5.7.B Frontend (React Kiosk Layout)
- [ ] **W-5.7.9** Full-screen layout (1920x1080 + 4K-friendly, landscape)
- [ ] **W-5.7.10** Hide cursor + disable scroll + disable context menu
- [ ] **W-5.7.11** Two-panel split:
  - **Now Preparing** (left, 30% width) — list of in-progress order numbers
  - **Ready for Pickup** (right, 70% width) — large bold numbers, highlighted color
- [ ] **W-5.7.12** Order number animation when moving from Preparing → Ready (slide + flash)
- [ ] **W-5.7.13** Audio chime on new "Ready" entry (Web Audio API)
- [ ] **W-5.7.14** Branch logo + name header
- [ ] **W-5.7.15** Live clock + date footer
- [ ] **W-5.7.16** Idle area / brand video / promo banner rotation (between order spikes)
- [ ] **W-5.7.17** Connection status indicator (top-right corner, green/red dot)
- [ ] **W-5.7.18** Auto-reconnect on WebSocket drop (exponential backoff)
- [ ] **W-5.7.19** Dark mode support (admin-toggled per branch)
- [ ] **W-5.7.20** Number masking option (e.g., "**42" or pickup code only)
- [ ] **W-5.7.21** Multi-language support (EN / BM)
- [ ] **W-5.7.22** Self-recovery — if Reverb fails, polling fallback every 10s

#### W-5.7.C Admin Panel — TV Display Settings
- [ ] **W-5.7.23** Per-branch display config (Filament):
  - Display token (generate / regenerate / copy URL)
  - Display name (e.g., "Counter 1", "Pickup Area")
  - Background color / theme (light / dark / brand)
  - Logo override (per branch)
  - Auto-clear timeout (minutes, default 10)
  - Sound on/off + volume slider
  - Number display format (full / masked / pickup code)
  - Idle banner uploads (images / videos)
  - Banner rotation interval
- [ ] **W-5.7.24** Display health monitor — list all branch displays + last heartbeat + online/offline
- [ ] **W-5.7.25** Preview mode — admin views display URL in iframe before publishing
- [ ] **W-5.7.26** Reset display (clear all numbers) — manual action for end-of-day

#### W-5.7.D Setup Documentation
- [ ] **W-5.7.27** Setup guide: pairing TV with display URL (Chromecast / Android TV / mini PC)
- [ ] **W-5.7.28** Recommended hardware list with prices
- [ ] **W-5.7.29** Troubleshooting guide (no audio, screen sleep, network)

---

## Sprint W-6 — Loyalty, Vouchers, Membership, Dashboard (Week 7) [✔]

### W-6.1 Loyalty Engine [✔]
- [✔] **W-6.1.1** Migration: `point_transactions` (single source of truth — running balance via `balance_after` column on each row)
- [✔] **W-6.1.2** Earn rule: 1 point per RM subtotal × tier multiplier; fires on Order → Completed transition (configurable via `LoyaltyService::POINTS_PER_RINGGIT`)
- [✔] **W-6.1.3** Redemption — `OrderPayload.loyaltyRedeemPoints` → `LoyaltyService::redeem` records `-points` row, OrderService discounts subtotal (100 pts = RM 1)
- [ ] **W-6.1.4** Points expiry job (12-month rolling) — deferred (cron in W-7 polish)
- [ ] **W-6.1.5** Birthday bonus job — deferred (needs notification stack from W-7)
- [ ] **W-6.1.6** Manual admin adjustment UI — deferred (record type already supports `adjustment`)
- [✔] **W-6.1.7** Customer points history page (`/loyalty`) — balance + tier + history list

### W-6.2 Voucher System [✔]
- [✔] **W-6.2.1** Migrations: `vouchers` + `voucher_redemptions`
- [✔] **W-6.2.2** `VoucherResource` Filament builder — code, percentage/fixed, min subtotal, max discount cap, branch scope, validity window, status
- [ ] **W-6.2.3** Bulk code generation — deferred (single-code per voucher works for MVP)
- [✔] **W-6.2.4** Apply at checkout — `VoucherService::find` + `discountFor` validate code, branch scope, min subtotal, per-user/total caps
- [ ] **W-6.2.5** Customer voucher wallet UI — deferred (W-7 polish)
- [ ] **W-6.2.6** Distribution to segments — deferred (segments need W-7 customer marketing tools)

### W-6.3 Membership Tiers [✔]
- [✔] **W-6.3.1** Migrations: `membership_tiers` (slug, min_lifetime_spend, earn_multiplier, color) + `customer_tier` (one row per user, lifetime_spend, achieved_at)
- [✔] **W-6.3.2** `MembershipTierResource` Filament UI
- [✔] **W-6.3.3** Auto-upgrade — `LoyaltyService::applyTierUpgrade` runs on Order → Completed; bumps tier when lifetime spend crosses threshold
- [✔] **W-6.3.4** Tier display on `/loyalty` page (current tier + multiplier + color)
- [✔] **W-6.3.5** Earn multiplier feeds into `LoyaltyService::earnFromOrder` (Bronze 1×, Silver 1.25×, Gold 1.5×, Platinum 2×)
- [✔] **W-6.3.6** Tier progress bar UI on `/loyalty` page (RM-to-next-tier indicator)
- [✔] **Seeder:** `LoyaltySeeder` creates Bronze/Silver/Gold/Platinum tiers (0/200/500/1500 RM thresholds)

### W-6.4 Promotion Engine [deferred to W-7]
- [ ] **W-6.4.1-5** Auto-apply best promo, eligibility engine, banner CMS — deferred. Voucher system covers code-based promos for MVP.

### W-6.5 Admin Dashboard [✔ MVP]
- [✔] **W-6.5.1** `SalesOverviewWidget` — today/week/month revenue + order count stats
- [✔] **W-6.5.3** `TopProductsWidget` — last-30-day units sold + revenue per product (top 10)
- [ ] **W-6.5.2/4/5** Revenue trend chart, order-type pie, hourly heatmap — deferred to W-7 polish
- [ ] **W-6.5.6/7/8** Branch+date filters, CSV export, drill-down — deferred

### W-6.6 Branch Detail Dashboard [deferred]
- [ ] **W-6.6.1-5** Per-branch widgets — deferred (branch_manager scope on OrderResource already filters; dedicated dashboard in W-7).

**Sprint W-6 Verified:** 88 tests passing (14 new LoyaltyVoucherTest), PHPStan level 5 clean, ESLint+TypeScript+Prettier+Vite build clean. Permissions matrix at 132 (11 resources × 12 actions) — `voucher` + `membership::tier` resources added.

---

## Sprint W-7 — PWA, Notifications, Referral, Polish (Week 8) [✔]

### W-7.1 PWA Configuration [✔]
- [✔] **W-7.1.1** Manifest with maskable icons (192/512), apple-touch-icon, favicon, theme/background `#000000` to match logo
- [✔] **W-7.1.2** Custom service worker via `vite-plugin-pwa` injectManifest strategy at `resources/js/sw.ts`; precaching + push event + notification click → focus or open-window
- [ ] **W-7.1.3** Offline menu cache (IndexedDB) — deferred (basic precaching covers app shell; menu offline is W-8 polish)
- [✔] **W-7.1.4** `InstallPrompt` component — captures `beforeinstallprompt`, dismissable with localStorage memory
- [✔] **W-7.1.5** A2HS supported via standard manifest + install prompt
- [ ] **W-7.1.6** "New version available" toast — vite-plugin-pwa handles auto-update on revisit (visible toast deferred)
- [ ] **W-7.1.7** iOS-specific splash screens — deferred (post-pilot polish)

### W-7.2 Web Push Notifications [✔]
- [✔] **W-7.2.1** VAPID keypair generated; stored in `.env` (`VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT`); public key exposed via `GET /api/push/vapid-key`
- [✔] **W-7.2.2** `push_subscriptions` table (user_id, endpoint unique, public_key, auth_token, content_encoding, last_used_at); `POST/DELETE /api/push/subscribe` (auth required, idempotent updateOrCreate)
- [✔] **W-7.2.3** `PushService` using `minishlink/web-push` v10 — `sendToUser(userId, payload)` queues + flushes; subscriptions returning 410/404 are auto-pruned
- [✔] **W-7.2.4** Order status push wired in `OrderService::transition` — pickup-Ready and any Cancelled fire push
- [ ] **W-7.2.5** Promo broadcast composer (Filament) — deferred
- [ ] **W-7.2.6** Voucher expiry reminder push — deferred (cron + scheduling)
- [ ] **W-7.2.7** Birthday greeting push — deferred
- [✔] **W-7.2.8** Permission UX — `PushToggle` button on `/loyalty` page (user opts in after they've made an account, not on first visit)
- [✔] **W-7.2.9** Tap-to-open deep link — SW `notificationclick` opens `payload.url`, focuses existing tab if URL matches
- [ ] **W-7.2.10** Action buttons — deferred
- [✔] **W-7.2.11** Dead-subscription cleanup — `PushService` checks `isSubscriptionExpired()` and deletes the row

### W-7.3 OnSend WhatsApp Multi-Channel Fallback [deferred]
- [ ] **W-7.3.1-43** Entire OnSend integration deferred until W-DEC-9 (numbers ready), W-DEC-10 (subscription tier), W-DEC-11 (warmup window) are decided. Architecture documented; Web Push (Layer 1) is functional standalone.

### W-7.3 Multi-Channel Fallback (Web Push + OnSend WhatsApp)

**Provider Locked:** **OnSend** (onsend.io) — unofficial WhatsApp API gateway
**API Docs:** https://onsend.io/api/docs

**Strategy:**
- **Layer 1 (Primary):** Web Push Notification — free, instant, official Web standard
- **Layer 2 (Fallback):** OnSend WhatsApp API — supplemental for users without push (especially iOS Safari without PWA installed)

⚠️ **Risk Acknowledgement:** OnSend is NOT an official WhatsApp Business API. WhatsApp may suspend the connected number for high-volume / spam-like activity. Mitigations are baked into the plan below (W-7.3.E).

#### W-7.3.A Notification Routing Engine
- [ ] **W-7.3.1** Channel preference per user (push / WhatsApp toggles in profile)
- [ ] **W-7.3.2** Notification routing service — picks channel based on:
  - User has active push subscription? → Web Push (Layer 1)
  - No push / push delivery failed? → OnSend WhatsApp (Layer 2)
  - User opted out of WhatsApp? → Push only (or email for critical)
- [ ] **W-7.3.3** Notification log per user (channel, delivery status, timestamp, OnSend message_id)
- [ ] **W-7.3.4** Critical events use both channels in parallel (push + WhatsApp):
  - Order ready for pickup
  - Order cancelled
  - Refund processed
  - Payment failed
- [ ] **W-7.3.5** Marketing events stay push-only (avoid triggering WhatsApp anti-spam)

#### W-7.3.B OnSend WhatsApp API Integration
- [ ] **W-7.3.6** OnSend account registration + active subscription
- [ ] **W-7.3.7** Pair primary WhatsApp number to OnSend (scan QR via OnSend mobile app)
- [ ] **W-7.3.8** Pair secondary backup number(s) to OnSend (failover redundancy)
- [ ] **W-7.3.9** Generate OnSend API token from dashboard
- [ ] **W-7.3.10** Webhook endpoint for OnSend delivery callbacks (if supported)
- [ ] **W-7.3.11** Laravel service class: `OnSendWhatsAppClient` (HTTP wrapper)
  - Methods: `sendMessage(to, body)`, `sendImage(to, url, caption)`, `getDeviceStatus()`
  - Reads API token + device ID from settings (encrypted)
- [ ] **W-7.3.12** Queue-based send via Horizon (`SendWhatsAppJob`)
- [ ] **W-7.3.13** Retry logic with exponential backoff on transient failures
- [ ] **W-7.3.14** Configurable inter-message delay (W-7.3 anti-ban — OnSend recommends 3-10s between sends)
- [ ] **W-7.3.15** Device status health check (cron every 5 min) — alert admin if device disconnects
- [ ] **W-7.3.16** Auto-failover to backup number if primary disconnects/banned

#### W-7.3.C Message Templates (Free-Form, OnSend Compatible)

OnSend uses free-form text (no Meta template approval needed). Templates stored as Laravel-side string templates with variable substitution.

- [ ] **W-7.3.17** Template: `order_received`
  ```
  Hi {name}! Order #{order_no} received at Star Coffee {branch}.
  Estimated ready: {eta} mins. Track: {url}
  ```
- [ ] **W-7.3.18** Template: `order_ready`
  ```
  Hi {name}, your order #{order_no} is READY for pickup at Star Coffee {branch}!
  Show this code at counter: {pickup_code}
  ```
- [ ] **W-7.3.19** Template: `order_cancelled`
  ```
  Order #{order_no} has been cancelled. Reason: {reason}.
  Refund of RM{amount} will be processed within 3-5 working days.
  ```
- [ ] **W-7.3.20** Template: `payment_failed`
  ```
  Payment for order #{order_no} failed. Retry: {url}
  Need help? Reply to this message.
  ```
- [ ] **W-7.3.21** Template: `voucher_received`
  ```
  🎉 You got a voucher! {voucher_name}.
  Code: {code} | Valid until {expiry}
  ```
- [ ] **W-7.3.22** Template: `birthday_greeting`
  ```
  Happy birthday {name}! 🎂 Star Coffee gifts you a free drink voucher.
  Code: {code} | Valid for 7 days
  ```
- [ ] **W-7.3.23** Template variable substitution helper (Blade-style)

#### W-7.3.D Admin Panel — OnSend Settings (Filament)
- [ ] **W-7.3.24** Settings page: OnSend WhatsApp Configuration (Filament resource)
  - **Provider:** OnSend (locked)
  - **API Base URL** (default: https://onsend.io/api)
  - **API Token** (encrypted via Laravel encrypter)
  - **Primary Device ID** (from OnSend dashboard)
  - **Backup Device IDs** (comma-separated, for failover)
  - **Sender Display Name** (read-only from OnSend)
  - **Inter-message Delay (seconds)** — anti-ban (default: 5)
  - **Active Mode Toggle** — global on/off kill-switch
- [ ] **W-7.3.25** Connection test button — sends test message to admin's WhatsApp number
- [ ] **W-7.3.26** Device status indicator (Connected / Disconnected / Banned) — auto-refresh
- [ ] **W-7.3.27** Template management UI:
  - List Laravel-side templates
  - Edit template body with variable picker
  - Preview with sample data
  - Activate / deactivate per template
  - Multi-language template variants (EN / BM)
- [ ] **W-7.3.28** Per-event channel matrix UI — admin toggles which events trigger WhatsApp:
  ```
  Event              | Push | WhatsApp
  Order Received     | ✓    | ✓
  Order Ready        | ✓    | ✓ (critical)
  Order Cancelled    | ✓    | ✓ (critical)
  Voucher Expiring   | ✓    | ✗
  Birthday Greeting  | ✓    | ✓
  Promo Broadcast    | ✓    | ✗ (anti-spam)
  ```
- [ ] **W-7.3.29** Rate limit configuration:
  - Max messages per minute (default: 20)
  - Max messages per day (default: 1000)
  - Burst protection
- [ ] **W-7.3.30** Usage dashboard:
  - Messages sent today / week / month
  - Per-event breakdown
  - Failed delivery count
  - Device status timeline (uptime %)
- [ ] **W-7.3.31** Customer opt-out management:
  - Profile toggle (customer-side)
  - Auto-opt-out on STOP / UNSUBSCRIBE keyword reply
  - Opt-out audit log
- [ ] **W-7.3.32** Manual WhatsApp send (admin → specific customer for support)
- [ ] **W-7.3.33** Bulk WhatsApp campaign tool:
  - Select segment
  - Choose template
  - Schedule with throttling
  - Cost preview (estimated based on subscription/usage)
  - **Block button** if message volume exceeds safe threshold

#### W-7.3.E OnSend Risk Mitigation (Anti-Ban Strategy)
- [ ] **W-7.3.34** Number warmup phase before launch — use OnSend's warmup feature for 7-14 days
- [ ] **W-7.3.35** Inter-message delay enforcement (queue worker honors `delay_seconds` from settings)
- [ ] **W-7.3.36** Daily volume cap (configurable, default 1000/day per device)
- [ ] **W-7.3.37** Avoid identical message bodies — randomize emoji/greeting variations
- [ ] **W-7.3.38** Skip WhatsApp for users who haven't initiated contact (risk: cold messaging)
- [ ] **W-7.3.39** Multi-device rotation — distribute load across paired numbers
- [ ] **W-7.3.40** Daily health check report to admin email (device status, send count, failures)
- [ ] **W-7.3.41** Emergency kill-switch — single toggle disables all WhatsApp sends instantly
- [ ] **W-7.3.42** Email fallback chain — if WhatsApp disabled/banned, critical events route to email
- [ ] **W-7.3.43** Documented SOP: "What to do if WhatsApp number gets banned"

### W-7.4 Referral Program [✔]
- [✔] **W-7.4.1** Unique code per user — `User::referral_code` auto-generated on creation (8-char alphanumeric); duplicate-check loop guarantees uniqueness (W-0.6)
- [✔] **W-7.4.2** Share intents — `/referral` page has copy-link + Web Share API (falls back to `wa.me/?text=…`)
- [✔] **W-7.4.3** Referrer + referee bonus on first completed order — `ReferralService::maybeAwardForCompletedOrder` runs in the Completed transition; `referral_rewards` table has `unique(referee_user_id)` for idempotency
- [✔] **W-7.4.4** Referee welcome bonus — credits both users with configurable point amounts via `services.referral.referrer_bonus_points` + `referee_bonus_points` (default 100/100)
- [✔] **W-7.4.5** Referral history page — `/referral` shows code, share URL, total earned, list of friends who joined
- [ ] **W-7.4.6** Device fingerprint / IP anti-abuse — deferred (basic single-reward-per-referee guard via DB unique works for MVP)

### W-7.5 Performance Polish [deferred to W-8]
- [ ] **W-7.5.1-7** CDN, lazy load, Lighthouse audit, query optimization, Redis cache, bundle analysis, compression — bundle into the W-8 pilot QA pass.

### W-7.6 Content & Settings [✔ MVP]
- [ ] **W-7.6.1** Banner CMS — deferred to W-8
- [ ] **W-7.6.2** FAQ CMS — for now, FAQ is a static React page with hardcoded Q&A (easy to edit in code)
- [✔] **W-7.6.3** Terms & Conditions page at `/terms`
- [✔] **W-7.6.4** Privacy Policy page at `/privacy` (PDPA-compliant disclosures)
- [ ] **W-7.6.5** Cookie consent banner — deferred (we only use essential session/CSRF cookies; explicit consent not legally required under PDPA for those)
- [ ] **W-7.6.6** Maintenance mode toggle — Laravel's `php artisan down` handles this; Filament toggle deferred
- [✔] **W-7.6.7** FAQ page at `/faq` (static, 6 entries)

**Sprint W-7 Verified:** 97 tests passing (9 new PushReferralTest), PHPStan level 5 clean, ESLint+TypeScript+Prettier+Vite build clean.

---

## Sprint W-8 — Pilot, Bug Fix, Launch (Weeks 9-10) [✔ MVP code-side]

### W-8.1 Pre-Launch QA [partial — automated covered, manual pending hosting]
- [✔] **W-8.1.1** Full regression test suite — 105 Pest tests covering auth, branches, staff, RBAC, menu, stock, orders, POS, TV display, loyalty, vouchers, tiers, push, referral, security, PDPA. Larastan level 5 clean.
- [ ] **W-8.1.2** Browser test (Chrome / Safari / Edge / Firefox) — manual, run during pilot
- [ ] **W-8.1.3** Mobile browser test (iOS Safari / Android Chrome) — manual
- [ ] **W-8.1.4** PWA install test (iOS + Android) — manual
- [ ] **W-8.1.5** Billplz live payment E2E — blocked on Billplz credentials (W-DEC pending)
- [ ] **W-8.1.6** Load test (100 concurrent orders) — manual via k6 in staging
- [✔] **W-8.1.7** Security hardening (OWASP-aligned):
  - `SecurityHeaders` middleware appends X-Content-Type-Options, X-Frame-Options, Referrer-Policy, Permissions-Policy, HSTS (when HTTPS)
  - Rate limits: login 6/min, register 6/min, POS PIN 5/min, order create 30/min, account delete 3/hour
  - IDOR guard: `OrderPolicy::view` allows owner OR `view_order` permission; `/orders/{order}` and `/api/orders/{order}` enforce via `can:view,order`
  - Mass assignment protection: all models declare `$fillable` (no `$guarded = []`)
  - Branch-scoped query filter prevents branch_manager from seeing other branches
- [✔] **W-8.1.8** PDPA: `GET /account/data-export` (JSON download of user, orders, points), `DELETE /account` (anonymise + soft delete + drop push subscriptions + scrub `customer_snapshot`). Auth-gated, throttled.
- [ ] **W-8.1.9** WCAG 2.1 AA audit — basic semantic HTML in place; full audit pending pilot

### W-8.2 Pilot Branch [pending hosting decision]
- [ ] **W-8.2.1** Deploy to production — blocked on W-DEC-1 (hosting) + W-DEC-2 (domain)
- [ ] **W-8.2.2** Onboard pilot branch staff — operational task
- [ ] **W-8.2.3** Real customer test orders — operational
- [ ] **W-8.2.4** Daily bug triage + hotfix — operational
- [✔] **W-8.2.5** Performance monitoring — Laravel Pulse already at `/pulse` + Horizon at `/horizon`; Sentry hook documented in Runbook

### W-8.3 Documentation [✔ MVP]
- [ ] **W-8.3.1** Admin user manual PDF — defer to handoff (W-8.2.2 training)
- [ ] **W-8.3.2** Branch staff training video — defer to handoff
- [✔] **W-8.3.3** API docs — Knuckles/Scribe installed, generated, served at `/docs` (Postman + OpenAPI spec also in `storage/app/private/scribe/`)
- [✔] **W-8.3.4** Customer FAQ at `/faq` (W-7.6.7)
- [✔] **W-8.3.5** Internal runbook at `docs/Runbook.md` covering stack, local dev, prod deploy, env vars, hotfix flow, rollback, common ops, monitoring, on-call playbook, PDPA exports

### W-8.4 Production Launch [blocked on hosting]
- [✔] **W-8.4.1** Final security review — covered by W-8.1.7 + W-8.1.8 + Runbook on-call playbook
- [✔] **W-8.4.2** PDPA compliance — endpoints + privacy policy live
- [✔] **W-8.4.3** T&C + Privacy Policy live at `/terms` + `/privacy` (W-7.6.3-4)
- [ ] **W-8.4.4** Marketing soft launch — operational
- [ ] **W-8.4.5** Monitor Sentry + Pulse + uptime — operational, runbook documents it
- [ ] **W-8.4.6** Post-launch retrospective — operational

**Sprint W-8 Verified:** 105 tests passing (8 new SecurityPdpaTest), PHPStan level 5 clean. Phase 1 web platform code-complete; remaining items are operational and unblock as W-DEC decisions land.

---

## Cross-Sprint Tasks (Ongoing)

- [ ] **W-X.1** Weekly code review checkpoint
- [ ] **W-X.2** Update `session-memory.md` after each work session
- [ ] **W-X.3** Update `MEMORY.md` when patterns emerge
- [ ] **W-X.4** Run review-protocol after each significant feature
- [ ] **W-X.5** Run security-protocol weekly

---

## Pending Decisions (Block Sprint W-0 Completion)

| ID | Decision | Status |
|---|---|---|
| W-DEC-1 | Hosting provider (Hetzner / DO / Vultr) | [ ] |
| W-DEC-2 | Domain (staging + production) | [ ] |
| W-DEC-3 | POS hardware (iPad / Android) + thermal printer model | [ ] |
| W-DEC-4 | Branding assets (logo, colors, fonts) | [ ] |
| W-DEC-5 | Initial branch count (for seed data) | [ ] |
| W-DEC-6 | Email provider (SES / SendGrid / Mailgun / Resend) | [ ] |
| W-DEC-7 | SST registered? (affects receipt format) | [ ] |
| W-DEC-8 | ~~WhatsApp BSP provider~~ — **LOCKED: OnSend** | [✔] |
| W-DEC-9 | WhatsApp number(s) ready for OnSend pairing? (1 primary + 1+ backup recommended) | [ ] |
| W-DEC-10 | OnSend subscription tier purchased? | [ ] |
| W-DEC-11 | Number warmup window — when to start? (recommend 14 days before launch) | [ ] |
| W-DEC-12 | TV Display hardware per branch — Smart TV + Chromecast / mini PC / iPad? | [ ] |
| W-DEC-13 | TV Display number format — full order # / masked / pickup code? | [ ] |
| W-DEC-14 | TV Display idle content — promo videos, banners, brand reel? | [ ] |

---

## Out of Scope (Phase 1 Web)

- iOS native app → see `Planning-Mobile.md`
- Android native app → see `Planning-Mobile.md`
- Apple/Google Wallet pass → Phase 3
- Native biometric login → Phase 3
- Delivery integration → Phase 2
- KDS hardware → Phase 2
- Self-delivery fleet → not planned
- Catering / bulk orders → not planned
- Subscription → not planned
- Physical gift cards → not planned

---

## Linkage to Mobile Phase

When **Sprint W-7 (PWA Polish)** completes, mobile phase (`Planning-Mobile.md`) can begin in parallel using the same backend API.
- Backend API endpoints are stable from **W-4** onwards
- Auth flow, menu, cart logic ready for mobile reuse from **W-3 onwards**
- Loyalty/voucher engines ready for mobile from **W-6 onwards**

---

**Next Action:** Resolve `W-DEC-1` to `W-DEC-5`, then start `W-0.1.1`.
