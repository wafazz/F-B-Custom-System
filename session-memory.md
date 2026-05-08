# Star Coffee — Session Memory

**Project:** Star Coffee — Multi-branch F&B Platform (Coffee & Pastry)
**Phase:** 1 of 3 — Web App + PWA
**Started:** 2026-05-08
**Last Updated:** 2026-05-09 (W-7 closed)

---

## Stack (Locked)
- **Backend:** Laravel 12.58 + PHP 8.4.10 (Herd) + MariaDB on port 3307 (XAMPP)
- **Admin:** Filament 3.3.50 (route: `/admin`)
- **Customer Web:** Inertia 2 + React 19 + TypeScript (strict) + Tailwind 4
- **Build:** Vite 7 + vite-plugin-pwa 1.3
- **Forms:** React Hook Form 7 + Zod 4
- **State:** TanStack Query 5 + Zustand 5
- **Real-time:** Laravel Reverb 1.10 (WebSocket)
- **Queue:** Redis + Horizon 5.46
- **Auth:** Sanctum 4.3 (API) + session (web)
- **RBAC:** Spatie Permission 7.4
- **Audit:** Spatie ActivityLog 5.0
- **Files:** Spatie MediaLibrary 11.22
- **Routes-in-JS:** Ziggy 2.6
- **WhatsApp:** OnSend (onsend.io) — Layer 2 fallback to Web Push

---

## Project Files
- `docs/Requirement.md` — Master requirement (v2.0)
- `docs/Planning-Web.md` — Phase 1 sprints (W-0 → W-8) — **EXECUTING**
- `docs/Planning-Mobile.md` — Phase 3 sprints (M-0 → M-8) — pending
- `session-memory.md` — this file

---

## Tasks Completed
- [✔] **W-0.1** Environment verified (PHP 8.4, Node 20, MariaDB on 3307, Redis 8.4, pnpm)
- [✔] **W-0.2** Laravel 12 bootstrap, git init, `.env` configured
- [✔] **W-0.3** All 10 core packages installed + Filament admin at `/admin`
- [✔] **W-0.4** Frontend: Inertia + React 19 + TS strict + Tailwind 4 + shadcn/ui + ESLint + Prettier + RHF/Zod + TanStack + Zustand + PWA
- [✔] **W-0.5** Folder structure, layouts (app/auth), error pages, Inertia shared data, global types
- [✔] **W-0.6** User table extended (phone/dob/photo/referral/consent), Login + Register controllers + pages, Filament admin user
- [✔] **W-0.7** Pest 3, Larastan 3.9 level 5, GitHub Actions CI workflow
- [✔] **W-1.1** Branches: migration + model + factory + seeder + Filament resource (operating hours, SST, image upload, status toggle)
- [✔] **W-1.2** Staff: branch_staff pivot + dual RelationManagers (BranchResource & UserResource) + PIN reset + suspend toggle
- [✔] **W-1.3** RBAC: 8 roles, 48 permissions, 4 policies, Filament Shield UI, branch_manager scope isolation
- [✔] **W-2.1** Catalog schema: categories, products, modifier_groups + options + product pivot, branch_product pivot, branch_stock + stock_movements; full Eloquent models w/ slug auto-gen + scopes
- [✔] **W-2.2** Filament: Category, Product (gallery + modifier picker + branch availability/pricing relation + stock relation), ModifierGroup with Options builder
- [✔] **W-2.3** Stock model with `applyMovement()` (atomic transaction + audit), branch-scoped menu API at `GET /api/branches/{branch}/menu`, BranchStockChanged event broadcasting on `branch.{id}.stock`. Order-side decrement deferred to W-4.
- [✔] **W-3** Customer storefront: splash → branch select → menu (real-time stock via Reverb). Inertia + React 19 pages, Zustand persisted cart bound to a branch, ModifierSheet sheet for product picker, TanStack Query for menu fetching. Mobile-first layout with bottom nav.
- [✔] **W-4** Cart → Checkout → Order placement → Live tracking. `orders`/`order_items`/`order_item_modifiers` schema, `OrderStatus` + `OrderType` + `PaymentStatus` enums, `OrderService::place` (atomic, locks branch row, validates stock, decrements via `BranchStock::applyMovement`, computes SST), state machine with `allowedTransitions`, `OrderStatusChanged` event on `orders.{id}` + `branch.{id}.orders`. Frontend cart/checkout/order/orders pages with Echo live status. `PaymentGateway` interface + `StubGateway` (Billplz adapter pluggable later); `/orders/{order}/simulate-paid` dev callback marks paid + advances to Preparing. Filament `OrderResource` for staff with quick advance/cancel actions, branch-scoped query for branch_manager.
- [✔] **W-5** Branch POS + TV Display. POS PIN login (branch_staff hashed pin), `EnsurePosSession` middleware, `pos-layout` (dark slate tablet UI), live order queue (Echo + sound chime + advance/cancel actions), POS stock toggle (broadcasts BranchStockChanged), walk-in POS (touch product grid + cart sidebar + cash/card/duitnow payment, marks paid + advances to Preparing). TV Display: `branch_display_tokens` table, public `/branch/{branch}/display?token=…` route, kiosk page with two-panel Now-Preparing / Ready layout, Echo subscription + slide+flash animation, heartbeat endpoint. `OrderQueuedForDineIn` + `OrderReadyForDineIn` events fire from state machine for dine-in orders only (pickup path notifications deferred to W-7).
- [✔] **W-6** Loyalty + Vouchers + Tiers + Admin dashboard. `point_transactions` table is the single source of truth (each row stores `balance_after` so balance = latest row). `LoyaltyService::earnFromOrder` runs on Order→Completed (1pt per RM × tier multiplier); `redeem` consumes points at checkout; `refundFromOrder` reverses on Refunded transition; `applyTierUpgrade` bumps customer_tier on lifetime spend crossings. Tiers seeded: Bronze 0 / Silver 200 (1.25×) / Gold 500 (1.5×) / Platinum 1500 (2×). Vouchers: percentage or fixed, min subtotal, max discount cap, branch scope JSON, per-user + global use caps. `OrderService::place` accepts `voucherCode` + `loyaltyRedeemPoints` and recomputes SST proportionally on the discounted subtotal. Filament: `VoucherResource` + `MembershipTierResource` + `SalesOverviewWidget` (today/week/month) + `TopProductsWidget` (last 30d top 10). Customer `/loyalty` page shows balance + tier + progress + history.
- [✔] **W-7** PWA + Web Push + Referral + Legal pages. PWA: switched vite-plugin-pwa to `injectManifest` strategy with custom `resources/js/sw.ts` (precache + push handler + notificationclick deep link); icons + apple-touch-icon + favicon generated from logo via `sips`; `InstallPrompt` component captures `beforeinstallprompt`. Web Push: `minishlink/web-push` v10, `push_subscriptions` table, `PushService::sendToUser` (queue+flush, prunes 410 endpoints), wired into `OrderService::transition` for pickup-Ready and Cancelled events. `PushToggle` button on `/loyalty` for permission opt-in. Referral: `referral_rewards` table with `unique(referee_user_id)` for idempotency, `ReferralService::maybeAwardForCompletedOrder` runs on Completed transition crediting referrer + referee bonus points (default 100/100, configurable). `/referral` page with copy + Web Share API (wa.me fallback). Legal: static `/terms`, `/privacy` (PDPA), `/faq` Inertia pages. Note: `User::referred_by` is a foreign user ID not a code (caught during integration). OnSend WhatsApp deferred entirely until W-DEC-9/10/11.

## Tasks Next (Pending Decisions)
- **W-0.7.1** GitHub repo (blocked on W-DEC-2)
- **W-0.8** Deployment prep (blocked on W-DEC-1)
- **W-0.6.4-6** Phone OTP, forgot password, 2FA — deferred to W-3.1 (proper customer auth sprint)
- **W-1.1.6** Branch map pin picker — needs Google Maps API key
- **W-2.2.6/7** Bulk product CSV / price update — deferred (MVP-skippable)
- **W-2.3.4** Low stock notifications — needs email provider (W-DEC-6)
- **W-2.3.8/9** Stock decrement on order events — deferred to W-4 (orders sprint)

## Sprint Status: W-0 [✔] · W-1 [✔] · W-2 [✔] · W-3 [✔] · W-4 [✔] · W-5 [✔] · W-6 [✔] · W-7 [✔] — 97 tests passing, lint+typecheck+phpstan clean
Ready to proceed to **Sprint W-8 — Pilot, Bug Fix, Launch** (regression suite, browser+mobile testing, PWA install QA, security audit, PDPA audit, accessibility audit, pilot branch deployment).

---

## Pending Decisions (Active)
- **W-DEC-1** Hosting provider
- **W-DEC-2** Domain (staging + prod)
- **W-DEC-3** POS hardware (iPad / Android tablet + thermal printer)
- **W-DEC-4** Branding assets (logo, colors, fonts)
- **W-DEC-5** Initial branch count (seed data)
- **W-DEC-6** Email provider (SES / SendGrid / Resend)
- **W-DEC-7** SST registered?
- **W-DEC-9** WhatsApp numbers ready for OnSend?
- **W-DEC-10** OnSend subscription tier
- **W-DEC-11** Number warmup window
- **W-DEC-12** TV Display hardware
- **W-DEC-13** TV number format (full / masked / pickup code)
- **W-DEC-14** TV idle content

## Locked Decisions
- **W-DEC-8** WhatsApp BSP = OnSend (onsend.io) — unofficial WhatsApp gateway, mitigations baked into W-7.3.E

---

## Notable Deviations from Plan
1. **React 19** instead of 18 — Laravel 12's default Inertia kit ships with React 19 (no issue, fully forward-compatible)
2. **Tailwind 4** instead of 3.4 — Laravel 12's default. Uses `@tailwindcss/vite` plugin + `@theme` syntax in CSS
3. **MariaDB** instead of pure MySQL 8 — XAMPP-based local dev on port 3307 (drop-in compatible)
4. **`@vitejs/plugin-react` 4.7** instead of 6.x — v6 requires Vite 8, Laravel 12 ships with Vite 7

---

## Local Dev Quick Reference
- **Project root:** `/Users/wafazztechnology/Desktop/Codex Lure/project/Star Coffee`
- **DB:** XAMPP MySQL on `127.0.0.1:3307`, database `star_coffee`, user `root`, no password
- **Redis:** Homebrew, `127.0.0.1:6379`
- **Dev server:** `php artisan serve --port=8000`
- **Vite dev:** `pnpm run dev`
- **Build:** `pnpm run build`
- **Admin URL:** `http://127.0.0.1:8000/admin` (no admin user created yet)
- **Reverb:** `php artisan reverb:start` (when working on real-time)

---

## Session Recap
- Created comprehensive Requirement.md, Planning-Web.md, Planning-Mobile.md with full traceability (W-X.Y.Z and M-X.Y.Z task IDs).
- Decided stack: Laravel 12 + Filament 3 + Inertia + React + TS, OnSend WhatsApp as Layer 2.
- Added TV Display Screen feature for dine-in order numbers.
- Added branch-specific stock filtering, real-time stock sync, customer flow with splash → branch select → storefront.
- Bootstrapped the entire Laravel project end-to-end. Verified Inertia + React render successfully.
- **W-1 closed:** Branch + Staff modules + RBAC. Branch resource (operating hours, SST, image upload, branch-scoped query); Staff via dual RelationManagers (PIN reset, suspend/reinstate, multi-branch); 4 hand-written policies (Branch, BranchStaff, User, Role) wired to 48 generated permissions across 8 roles. Branch managers isolated to their assigned branches via `BranchPolicy::scopeAllows()` + Filament query filter. Fixed Shield-generated RolePolicy placeholder bug (`{{ ForceDelete }}` → `force_delete_role` etc.).
- **W-2 closed:** Menu & Catalog. Categories (nested via parent_id) + Products (with gallery, sst_applicable, prep_time, featured) + reusable Modifier Groups + Options. Branch-specific availability + price override via `branch_product` pivot. `branch_stock` table with `track_quantity` flag (off = always available) + low_threshold + audit via `stock_movements` morphs table. `BranchStock::applyMovement()` is the single atomic mutation entry point — handles quantity update, audit row, and broadcasts `BranchStockChanged` event when availability flips. Public branch-scoped menu API at `GET /api/branches/{branch}/menu`. PHPStan caveat: chained `->map()` on Eloquent\Collection doesn't narrow types — rewrite as foreach + presenter helper, or add `@return BelongsToMany<ChildModel, $this>` to relationships.
- **W-3 closed:** Customer storefront in Inertia + React 19 + TanStack Query + Zustand. Pages: `storefront/splash.tsx` (branded loading, auto-routes via persisted branch), `storefront/branch-select.tsx` (list view with open/closed badge, switching clears cart with alert), `storefront/menu.tsx` (category pills + ProductCard list, real-time stock via Reverb's Echo channel `branch.{id}.stock`). Components: `StorefrontLayout` (sticky header + cart badge + mobile bottom nav), `ModifierSheet` (single/multiple selection with min/max validation + price preview), `Badge` + `Sheet` shadcn primitives. Stores: `branchStore` (persisted to `star-coffee:branch`), `cartStore` (persisted to `star-coffee:cart`, exposes `rebindBranch()` that auto-clears on branch switch). Hooks: `useBranchMenu` (TanStack Query `['menu', branchId]`), `useStockSubscription` (Echo listener that invalidates the menu query on `.stock.changed`). Lint caveat: `react-refresh/only-export-components` rejects re-exporting Radix primitives directly — wrap them in tiny components or add to `allowExportNames`.
