# Star Coffee — Session Memory

**Project:** Star Coffee — Multi-branch F&B Platform (Coffee & Pastry)
**Phase:** 1 of 3 — Web App + PWA
**Started:** 2026-05-08
**Last Updated:** 2026-05-25 (usual-order reminder campaign + inactive repeat-mode toggle on Scheduled Push)

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
- [✔] **W-8** Pilot prep code-side. Security: `SecurityHeaders` middleware (X-Frame, X-Content-Type, Referrer-Policy, Permissions-Policy, HSTS); rate limits on login (6/min), register (6/min), POS PIN (5/min), order create (30/min), account delete (3/hour); IDOR guard via `OrderPolicy::view` allowing owner OR `view_order` permission, enforced via `can:view,order` middleware on web + api routes. PDPA: `GET /account/data-export` (JSON dump), `DELETE /account` (anonymise + soft delete user + drop push subscriptions + scrub `customer_snapshot` on past orders, transaction-wrapped). API docs: Knuckles/Scribe v5.9 generated and served at `/docs` with Postman + OpenAPI collections. Internal runbook at `docs/Runbook.md` covering deploy, rollback, hotfix, monitoring, on-call playbook.
- [✔] **Wallet** Customer wallet with Billplz top-up + checkout payment selector. Schema: `wallets` (PK user_id, balance, lifetime_topup, lifetime_spent), `wallet_transactions` (signed amount + balance_after + morph reference), `wallet_topups` (pending/paid/failed + billplz_reference). `WalletService` does all mutations under `lockForUpdate()` inside `DB::transaction`; `applyTopupPaid` is idempotent. `BillplzGateway::createTopupBill` reuses the same Billplz API and the existing webhook controller now dispatches by `billplz_reference` lookup → either WalletTopup (credit + mark paid) or Order (existing flow). `OrderPayload->paymentMethod` is `'gateway'` or `'wallet'`. Wallet-paid orders skip the gateway entirely, atomically debit + mark paid in `OrderService::place`; cancellation refunds back to wallet via `transition()`. Routes: `GET /wallet`, `POST /wallet/topup` (throttle 10/min). Storefront layout grew a Wallet tab (now 5 cols). Checkout shows Wallet (with balance hint, disabled when insufficient or not authenticated) vs Billplz. 11 Pest tests, all green; 130 total passing; PHPStan level 5 clean.
- [✔] **2026-05-12 bundle** (commits `def1cfe`, `62d6b95`, `590580d`, `79d42d1`, `d0c8e5b`):
  - **branch-home** intermediary page between branch-select and menu — carousel + featured products + category grid; storefront-layout "Order" tab + branch-select now route to `/branches/{id}`; `?category=` deep-link consumed by menu via lazy `useState` initializer (no `setState-in-effect`).
  - **label printing**: `branches` gained `auto_print_labels` / `label_copies` / `label_size`; POS queue auto-prints thermal labels (58/80mm via iframe) on transition to Preparing, plus reprint button. `print-labels.ts` renders HTML labels server-side-shape from order items × modifiers × copies.
  - **user address**: `address_line` / `city` / `postcode` / `state` on users; ProfileController validates + persists; profile form has Malaysian state dropdown.
  - **InstallPrompt** was authored at `resources/js/components/storefront/install-prompt.tsx` but never mounted — now imported by StorefrontLayout. Captures `beforeinstallprompt`.
  - **UserSeeder**: `superadmin@starcoffee.my` (super_admin role + Shield bypass) + `admin@starcoffee.my` (hq_admin role), both `MoHd20188!`. Moved the `shield:super-admin` call out of `RolesAndPermissionsSeeder` (which depended on `User::find(1)`) into the new seeder.
  - **WebPushSettings** Filament page at `Settings → Web Push` (super_admin + hq_admin only). Form: subject + public_key + private_key (encrypted). "Generate new keypair" button uses `Minishlink\WebPush\VAPID::createVapidKeys()`. `PaymentServiceProvider::boot()` now also hydrates `services.webpush.*` from settings table, falling back to .env. `PushService` + `/api/push/vapid-key` already read from config so no further wiring needed.
  - **fix(settings)**: `SettingsRepository::set()` was writing `['value' => ..., 'is_encrypted' => ...]` — on a fresh row, Eloquent's mutator on `value` ran before `is_encrypted` landed, so plaintext got stored under the encrypted flag → next read threw `DecryptException`. Swapped the array order. Latent bug, also affected first-time Billplz saves.
  - 137 tests passing (130 + 7 new WebPushSettings tests covering guest redirect, customer 403, super/hq admin render, Livewire generateKeys + save with encryption-at-rest assertion, and `/api/push/vapid-key` DB override). PHPStan clean, ESLint + tsc + Vite build all green.
- [✔] **2026-05-13 admin profile + role-gating**:
  - **Filament profile page enabled** via `->profile(EditProfile::class, isSimple: false)` so admin/super_admin can edit their own account.
  - **Custom `App\Filament\Pages\Auth\EditProfile`** overrides the form to show **Basic information** (name, email-disabled, phone, DOB, gender) + **Address** (line, city, postcode, state). Email is `disabled()` AND `dehydrated(false)` — UI lock + payload strip so it can't be tampered.
  - **Separate `App\Filament\Pages\ChangePassword`** page at `/admin/change-password` with `currentPassword()` rule on Current password, `Password::default()` rule on New password, `same('passwordConfirmation')` on confirmation, and session `password_hash_<guard>` re-sync after save to keep the user logged in.
  - **User menu** got a second item ("Change password") via `->userMenuItems(['change-password' => MenuItem::make()->url(fn () => ChangePassword::getUrl())])`. Profile entry is still the default Filament one.
  - **UserResource hardening** — `getEloquentQuery()` filters out `super_admin` AND `hq_admin` users for anyone who isn't a super_admin themselves. Roles `Select` uses `modifyQueryUsing` to strip `super_admin` + `hq_admin` from the dropdown for non-super_admins; Filament's relationship validator rejects tampered submissions with hidden role IDs.
  - **Access Control opened to hq_admin** — `RolesAndPermissionsSeeder` now grants `hq_admin` the full `role` permission set (was only `view_any_role` + `view_role`), so they see the **Roles & Permissions** nav and can create/edit/delete roles. `RolePolicy::update` and `RolePolicy::delete` defend the two privileged roles: non-super_admins are denied on `super_admin` or `hq_admin` rows, so Filament hides those edit/delete actions automatically while leaving the rows visible (read-only).
  - 142 tests still green; no regressions in user-admin flows.
- [✔] **2026-05-18 push notification mount fix**:
  - **Root cause**: `PushToggle` was authored at `resources/js/components/storefront/push-toggle.tsx` back in W-7 but **never imported anywhere** (same class of bug as InstallPrompt before the 2026-05-12 bundle). Customers had no UI surface to subscribe — `push_subscriptions` table stayed empty in production, so `PushService::sendToUser` always returned 0.
  - **Fix A — early soft prompt**: New `NotificationPrompt` component at `resources/js/components/storefront/notification-prompt.tsx`, mounted at the top of `branch-home.tsx` (the `/branches/{id}` page). Renders only when (push supported AND logged in AND `Notification.permission === 'default'` AND not dismissed within last 7 days, via `localStorage` key `sc.notif-prompt.dismissed-at`). Soft in-app banner with Enable/Not now buttons — never auto-fires the OS prompt (required on iOS where `Notification.requestPermission` only works inside a user gesture).
  - **Fix B — long-term toggle**: `PushToggle` now imported into `loyalty.tsx` header (next to the Vouchers pill) so users can re-enable later if they dismissed the early banner.
  - **Production caveats**: still depends on (1) HTTPS on the VPS (Push API + service workers refuse otherwise), (2) VAPID keys present in `services.webpush.*` — either `.env` (`VAPID_PUBLIC_KEY`/`VAPID_PRIVATE_KEY`) OR Filament `Settings → Web Push` page (hydrated by `PaymentServiceProvider::boot`), (3) iOS 16.4+ and PWA installed to homescreen.
  - **Fix C — admin test button**: New "Send test push" row action on Filament `UserResource` (color=warning, bell-alert icon). Auto-hidden for users with zero `push_subscriptions` rows. Modal shows active-device count + editable title/body/deep-link URL, defaults to a friendly test payload. Calls `PushService::sendToUser` via constructor injection; surfaces "Sent to N device(s)" or "Web push is not configured" via Filament Notification. Lets admins verify delivery per customer without placing a real order. tsc clean, Vite build clean (72 precache entries), PHPStan clean on UserResource.
- [✔] **2026-05-12 dashboard + chime**:
  - **Interactive Filament dashboard** at `/admin`: 5 new widgets + 1 enhanced (`SalesOverviewWidget`). LiveOrdersWidget polls 10s for Pending/Preparing/Ready counts; SalesOverviewWidget now has 7-day sparklines + % change vs previous period; RevenueChartWidget is a dual-axis (revenue + order count) line chart with 7/14/30-day filter, polls 60s; RevenueByBranchWidget is a doughnut over last 30 days, gated to super_admin/hq_admin/ops_manager; LowStockWidget surfaces `BranchStock::lowStock()` rows, polls 60s; RecentOrdersWidget shows last 8 orders with status-colored badges, click-to-view, polls 15s. All registered in `AdminPanelProvider` with `$sort` for deterministic layout.
  - **Bug fix**: `RecentOrdersWidget` badge closure type-hinted `string $state` but Order casts `status` to `OrderStatus` enum — Filament passed the enum, TypeError on row render. Changed to `OrderStatus $state` matching enum cases directly. Strengthened the widget mount test to seed an Order in every `OrderStatus` case so the closure executes for each (would have caught this in CI).
  - **Chime everywhere a page is open** (`sc7.mp3` at `public/sounds/sc7.mp3`):
    - POS queue tablet on new `pending` order (was silent base64 placeholder).
    - TV display board on order moving to Ready panel (was silent base64 placeholder).
    - Customer `/order/{id}` page when Echo flips status to `ready` and prior wasn't ready (idempotent against duplicate broadcasts). New behaviour — page had no audio before.
    - Added to PWA precache via `includeAssets` + `additionalManifestEntries` in vite.config.ts; 50 → 51 entries.
    - **Caveat**: OS-level push notifications can't use custom sounds (Web Notifications spec dropped the `sound` property ~2018); custom mp3 only plays when a tab is open and Echo delivers the event. Customer page may hit browser autoplay-block on cold load with no interaction; `.catch(() => {})` swallows it.
  - 142 tests passing (137 + 5 new DashboardWidgetsTest). PHPStan + ESLint + tsc + Vite build clean.
- [✔] **2026-05-22 → 2026-05-24 PWA-pilot polish bundle** (commits `476c5bc`, `a707732`, `8b85548`, `05e1ced`, `bac197c`, `1924ec3`, `2af788e`, `f98b297`, `ab03113`, `c852a12`, `9725ce5`, `8e4b4a1`, `c5fb2c9`, `35eb13f`, `36bd3ff`):
  - **PWA checkout CSRF 419 fix** — long-running iOS standalone PWAs hit `419 CSRF mismatch` on every Billplz/wallet checkout because both the `<meta name="csrf-token">` AND the `XSRF-TOKEN` cookie were stale (cookie rotates on every full-page nav; PWA stays warm for hours without one). Two-part fix mirroring the `1d431b5` push-subscribe playbook: (a) checkout.tsx now `await fetch('/sanctum/csrf-cookie')` before reading the cookie + posting; (b) `api/orders` added to CSRF except-list in `bootstrap/app.php` (safe because route is `auth:sanctum,web` + cookies are SameSite=Lax). Saved as `pwa-csrf-cookie-refresh.md` memory.
  - **Pull-to-refresh + manual refresh button** on order details page — touchstart/move/end listeners only engage at `scrollY=0`, rubber-band capped at 110px, 70px threshold triggers `router.reload()`. Refresh button next to "Placed" timestamp shares the same `refreshingRef` so both paths can't fire over each other.
  - **Admin Referral Program settings** — new Filament page (`Settings → Referral Program`) with enable toggle, referrer points, referee points, min first-order amount, and share-text template with `{code}`/`{points}`/`{url}` placeholders. `ReferralService` reads from `SettingsRepository` with env config fallback. Storefront swaps placeholders client-side for the Web Share API / WhatsApp.
  - **POS recent-orders endpoint** — `GET /api/pos/branches/{branch}/recent` returns preparing/ready/completed/cancelled orders (complement to `/queue`'s live trio). Optional `?status=` narrows to one bucket, `?limit=` caps page size.
  - **Voucher BxGy save-bug fix** — `bxgy_free_scope` had `dehydrated(false)`, so the chosen scope never reached `normaliseBxgyPayload()`. The `?? 'same'` fallback then fired on every save and the 'same' arm reset both `bxgy_free_product_ids` and `bxgy_free_combo_ids` to null — silently wiping admin's Cross-sell picks. Dropped `dehydrated(false)`; the matching `unset()` already strips it before Eloquent sees it.
  - **Voucher redemptions modal** — new "Redemptions" row action on voucher list opens a modal listing every redemption (customer name + email/phone + linked order + discount + timestamp + header totals). Added missing `VoucherRedemption::user()` belongsTo to power the lookup.
  - **Public digital receipt page** at `/r/{order_number}` — controller + standalone Inertia React page (no storefront chrome). Initially shipped as signed URL, switched to plain URL per Fakrul's preference (security tradeoff: sequential numbers are guessable, accepted).
  - **API response expansion** — `OrderController::present()`, `PosApiController::presentOrder()` + `receipt()`, and `ReceiptController` all now include `service_charge_amount`, `discount_amount`, `tumbler_discount_amount`, `vouchers[]` (code + name + discount, sourced from `VoucherRedemption`), and per-line `voucher_code` + `voucher_role` (BxGy free-item rendering signal).
  - **`Order::redemptions()` hasMany added** (commit `c5fb2c9`) — initially missed; the API expansion called `$order->loadMissing(['redemptions.voucher...'])` but `Order` only had the inverse `Voucher::redemptions()`. Hit prod with 500 on `/r/PASTRY-260523-0013`. Saved as `eager-load-verify-relation.md` memory: grep the model file for the relation before adding any eager-load call.
  - **Order notes plumbing** — exposed order-level `notes` in POS queue/recent/transition/receipt + public receipt page (was only on the customer `/api/orders` side). New `PATCH /api/pos/orders/{order}/notes` and `PATCH /api/pos/orders/{order}/items/{item}/notes` for POS staff to edit remarks. Walk-in endpoint extended to accept `notes` + `lines.*.notes` at creation time. Receipt page renders a "Special remarks" card.
  - **Split review submit** — `OrderPagesController` hydrates `has_reviewed_branch` (bool) + `reviewed_product_ids` (int[]) instead of a single `has_reviewed` boolean. `order.tsx` tracks branch + per-product independently; each `ReviewForm` only retires its own card on submit. "All reviews submitted" card only when both branch + every product have been rated.

- [✔] **2026-05-25 inactive-user reactivation repeat mode** — The "we miss you" reactivation push already existed (Marketing → Scheduled Push, `audience='inactive'`, admin sets `inactivity_signal` no-order/no-app-activity + `inactivity_days`). Added an admin choice for the cadence: new `inactivity_repeat` toggle + `inactivity_cooldown_days` (migration `2026_05_25_000100`). OFF = original drip (fires once on the exact day they hit N days inactive — stack 7/14/30-day campaigns). ON = re-engage anyone inactive N+ days every daily scan, throttled by the cooldown via `SendScheduledCampaign::recentlyNudged()` (skips users with a `campaign_deliveries` row inside the window). Touched: migration, `ScheduledCampaign` (fillable+casts), `ScheduledCampaignResource` (form fields + `normalizeSchedule` null-out + table badge), `SendScheduledCampaign::inactiveUserIds()` (query flips `=`→`<=`). Verified: migration ran, tinker smoke test confirmed new columns persist + repeat-mode SQL executes; PHPStan delta = 0 new errors (stash-compared, 23 pre-existing local larastan cast artifacts unchanged).

- [✔] **2026-05-25 usual-order reminder campaign** — New `audience='usual'` on Scheduled Push: a daily "come back & buy your usual {item}" push. **No migration** — reuses `audience` + `inactivity_days` (= "remind if no order in last N days") + `inactivity_cooldown_days` (re-send gap). New `{usual}` placeholder filled per customer from their most-bought *completed*-order item via `SendScheduledCampaign::usualProductsFor()` (one grouped `order_items ⋈ orders` query per chunk). `usualReminderUserIds()` targets lapsed N+ day buyers minus cooldown; customers with no completed order are skipped in the send loop. `renderMessage()` gained a 4th `$usual` arg. Touched: `ScheduledCampaign` (renderMessage), `SendScheduledCampaign` (audienceQuery + 2 helpers + chunk loop), `ScheduledCampaignResource` (audience option, field labels/visibility, body helper, normalizeSchedule, table badge). Verified via reflection smoke test (targeting + usual lookup + {usual} render all correct); PHPStan still 23 (zero new). **Known gap:** usual item not availability/stock-checked. Not yet committed.

## Tasks Next (Pending Decisions)
- **W-0.7.1** GitHub repo (blocked on W-DEC-2)
- **W-0.8** Deployment prep (blocked on W-DEC-1)
- **W-0.6.4-6** Phone OTP, forgot password, 2FA — deferred to W-3.1 (proper customer auth sprint)
- **W-1.1.6** Branch map pin picker — needs Google Maps API key
- **W-2.2.6/7** Bulk product CSV / price update — deferred (MVP-skippable)
- **W-2.3.4** Low stock notifications — needs email provider (W-DEC-6)
- **W-2.3.8/9** Stock decrement on order events — deferred to W-4 (orders sprint)

## Sprint Status: W-0 [✔] · W-1 [✔] · W-2 [✔] · W-3 [✔] · W-4 [✔] · W-5 [✔] · W-6 [✔] · W-7 [✔] · W-8 [✔ code] · Wallet [✔] · 2026-05-12 bundle [✔] · 2026-05-12 dashboard + chime [✔] · 2026-05-13 admin profile + role-gating [✔] — 142 tests passing, PHPStan level 5 clean, ESLint + tsc + Vite build all green.

**Phase 1 Web App is code-complete.** Remaining unchecked items are operational and unblock when W-DEC decisions land:
- W-DEC-1 hosting + W-DEC-2 domain → unblocks W-0.8 deploy + W-8.2 pilot deployment
- W-DEC-6 email provider → unblocks W-2.3.4 low-stock alerts + W-7.2.6 voucher expiry + receipt emails
- W-DEC-9/10/11 OnSend WhatsApp → unblocks W-7.3 entire WhatsApp layer
- Billplz credentials → unblocks W-4.3.5-9 real payment integration (StubGateway in place as drop-in replacement)
- Google Maps API key → unblocks W-1.1.6 + W-3.4.2-3 map features

Ready for **Phase 3 — Mobile (Planning-Mobile.md)** to start in parallel using the existing API.

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
- **2026-05-25 (usual-order reminder):** New `audience='usual'` Scheduled Push — daily "come back for your usual {item}" to lapsed buyers (no order in N+ days), throttled by cooldown. `{usual}` placeholder = each customer's most-bought completed item (`usualProductsFor()`). No migration (reuses inactivity columns). No-history customers skipped. Verified via reflection smoke test; PHPStan 23 (no new). Not yet committed.
- **2026-05-25 (inactive-user reactivation cadence):** The "we miss you" inactive-customer push was already built (Scheduled Push, `audience='inactive'`). Added an admin per-campaign choice: `inactivity_repeat` toggle (+ `inactivity_cooldown_days`) — OFF = drip once at exactly N days, ON = keep reminding anyone N+ days inactive each scan, cooldown-throttled. Migration `2026_05_25_000100`. Verified via tinker smoke test; zero new PHPStan errors (stash-compared). Not yet committed.
- **2026-05-22 → 2026-05-24 (PWA-pilot polish bundle):** Fixed long-standing PWA checkout 419 (cookie refresh + CSRF except-list, same playbook as `1d431b5`); shipped pull-to-refresh + manual refresh on the order page; admin-configurable referral program (rewards, gating, share text with placeholders); new POS `/recent` endpoint; voucher BxGy save-bug fix (`dehydrated(false)` was wiping cross-sell selections); voucher redemptions modal in admin; public digital receipt page at `/r/{number}` (plain URL, not signed); expanded order API to include service charge, discount, tumbler, vouchers array + per-line voucher_code/role; full notes plumbing (POS read + edit endpoints + walk-in capture + receipt page render); split review submit so branch and per-product are independent. Caught and fixed missing `Order::redemptions()` hasMany after a prod 500.
- **2026-05-12:** Validated Phase 1 end-to-end (routes ↔ controllers ↔ Inertia pages ↔ Echo channels ↔ PWA build), shipped a feature bundle (branch-home + label printing + user address), seeded admin accounts, added the Filament Web Push settings page with provider-level config hydration, and fixed a latent settings-encryption bug. 137 tests green.
- Stack: Laravel 12 + Filament 3 + Inertia + React + TS, OnSend WhatsApp as Layer 2.
- W-1 → W-8 closed (see Tasks Completed). TV Display + Wallet + PWA + Web Push + Referral + Legal pages all wired.
- Common gotchas seen so far:
  - `bootstrap/cache/filament` caches resource/page discovery — must clear after adding a new Filament page (`rm -rf bootstrap/cache/filament` then `php artisan filament:cache-components`).
  - CLI php (Homebrew) doesn't have `redis.so`; only Herd's `php84` does. Use Herd's binary for tinker/seed commands that touch Redis cache.
  - Eloquent `updateOrCreate` fills attributes in array order — when an accessor/mutator depends on another column (like `value` needing `is_encrypted`), order the array so the dependency lands first.
  - React 19 ESLint flags `setState` inside `useEffect` and `ref.current` reads during render — use lazy `useState` initializers for one-shot URL/query-param derivations.
  - Eloquent relations are unidirectional — defining `Voucher::redemptions` does NOT create `Order::redemptions`. Grep the model before any `loadMissing` / eager-load call.
  - Filament form-only selects with `dehydrated(false)` are EXCLUDED from `mutateFormDataBeforeSave` $data — if `normalisePayload()` reads the field with `?? 'default'`, it'll silently overwrite the persisted columns on every save. Either remove `dehydrated(false)` (and `unset()` before Eloquent sees it) or read from the live form state instead.
  - PWA on iOS standalone keeps the app warm for hours without a full-page nav, so BOTH the meta CSRF token AND the XSRF cookie can go stale. Raw `fetch()` to `/api/*` must `await fetch('/sanctum/csrf-cookie')` first; for high-traffic auth-protected routes, also add them to the CSRF except-list as a backstop (safe when route is `auth:sanctum,web` + SameSite=Lax).
