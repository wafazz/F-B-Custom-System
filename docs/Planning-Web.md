# Star Coffee — Planning (Phase 1: Web App + PWA)

**Project:** Star Coffee — Multi-branch F&B Platform
**Phase:** 1 of 3 — Web App + PWA (Customer Web + Admin Portal + Branch POS)
**Stack:** Laravel 12 + Filament 3 + Inertia 2 + React 18 + TypeScript + Tailwind + shadcn/ui
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
| Foundation | W-0 | 1 | Setup, packages, auth, CI/CD | [ ] |
| Branches & Staff | W-1 | 2 | Branch + staff CRUD + RBAC | [ ] |
| Menu & Catalog | W-2 | 3 | Categories, products, modifiers | [ ] |
| Customer Web Frontend | W-3 | 4 | Auth UI, home, browse, branch select | [ ] |
| Cart, Checkout, Orders | W-4 | 5 | Cart, Billplz, order tracking | [ ] |
| Branch POS + TV Display | W-5 | 6 | POS, order queue, walk-in, TV dine-in display | [ ] |
| Loyalty/Voucher/Dashboard | W-6 | 7 | Loyalty, vouchers, tiers, analytics | [ ] |
| PWA & Polish | W-7 | 8 | PWA, web push, referral | [ ] |
| Pilot & Launch | W-8 | 9-10 | QA, pilot branch, production launch | [ ] |

---

## Sprint W-0 — Foundation (Week 1)

### W-0.1 Environment & Setup [✔]
- [✔] **W-0.1.1** PHP 8.4.10 (Herd) — exceeds 8.3 minimum
- [✔] **W-0.1.2** Node 20.19.4 LTS + npm 10.8.2
- [✔] **W-0.1.3** MariaDB running (MySQL-compatible drop-in)
- [✔] **W-0.1.4** Redis 8.4.0 running
- [✔] **W-0.1.5** Composer 2.8.10
- [✔] **W-0.1.6** pnpm installed

### W-0.2 Project Bootstrap
- [ ] **W-0.2.1** `composer create-project laravel/laravel star-coffee "^12.0"`
- [ ] **W-0.2.2** Initialize git repository + `.gitignore`
- [ ] **W-0.2.3** Setup `.env` with DB, Redis, mail
- [ ] **W-0.2.4** Configure `config/app.php` (timezone Asia/Kuala_Lumpur, locale en)
- [ ] **W-0.2.5** Create `.env.example` with all required vars
- [ ] **W-0.2.6** First commit baseline

### W-0.3 Core Packages Install
- [ ] **W-0.3.1** `laravel/sanctum` — API auth
- [ ] **W-0.3.2** `laravel/horizon` — queue dashboard
- [ ] **W-0.3.3** `laravel/reverb` — WebSocket
- [ ] **W-0.3.4** `laravel/pulse` — performance monitoring
- [ ] **W-0.3.5** `spatie/laravel-permission` — RBAC
- [ ] **W-0.3.6** `spatie/laravel-activitylog` — audit logs
- [ ] **W-0.3.7** `spatie/laravel-medialibrary` — file uploads
- [ ] **W-0.3.8** `filament/filament:^3.0` — admin
- [ ] **W-0.3.9** `inertiajs/inertia-laravel:^2.0`
- [ ] **W-0.3.10** `tightenco/ziggy` — route names in JS

### W-0.4 Frontend Setup
- [ ] **W-0.4.1** Install React 18 + TypeScript via Inertia starter
- [ ] **W-0.4.2** Configure Vite + Tailwind 3.4
- [ ] **W-0.4.3** Install shadcn/ui CLI + base components (button, input, dialog, toast)
- [ ] **W-0.4.4** Install React Hook Form + Zod
- [ ] **W-0.4.5** Install TanStack Query v5
- [ ] **W-0.4.6** Install vite-plugin-pwa
- [ ] **W-0.4.7** Setup TypeScript strict mode
- [ ] **W-0.4.8** Configure ESLint + Prettier

### W-0.5 Project Structure & Conventions
- [ ] **W-0.5.1** Setup folder structure (Domain/Service pattern)
- [ ] **W-0.5.2** Create base layouts (Auth, App, Admin)
- [ ] **W-0.5.3** Setup error pages (404, 500, 403)
- [ ] **W-0.5.4** Setup maintenance mode page
- [ ] **W-0.5.5** Configure logging channels

### W-0.6 Auth Scaffolding
- [ ] **W-0.6.1** User table migration (phone, DOB, photo, referral_code)
- [ ] **W-0.6.2** Login (email + phone tabs)
- [ ] **W-0.6.3** Registration with email verification
- [ ] **W-0.6.4** Phone OTP flow (placeholder SMS provider)
- [ ] **W-0.6.5** Forgot password
- [ ] **W-0.6.6** 2FA for admin (TOTP)

### W-0.7 CI/CD & Quality
- [ ] **W-0.7.1** Setup GitHub repo
- [ ] **W-0.7.2** GitHub Actions: lint + test + build
- [ ] **W-0.7.3** Pest 3 test runner configured
- [ ] **W-0.7.4** PHPStan / Larastan setup
- [ ] **W-0.7.5** Husky pre-commit hook (PHP CS Fixer + ESLint)

### W-0.8 Deployment Prep
- [ ] **W-0.8.1** Provision VPS (decision pending)
- [ ] **W-0.8.2** Setup Cloudflare DNS
- [ ] **W-0.8.3** Configure staging subdomain
- [ ] **W-0.8.4** SSL via Let's Encrypt or Cloudflare
- [ ] **W-0.8.5** Setup deploy script (Envoyer / Forge / manual)

---

## Sprint W-1 — Branches & Staff (Week 2)

### W-1.1 Branch Module (Admin)
- [ ] **W-1.1.1** Migration: `branches` (name, code, address, lat, lng, phone, status)
- [ ] **W-1.1.2** Branch model + factory + seeder
- [ ] **W-1.1.3** Operating hours per day (JSON or related table)
- [ ] **W-1.1.4** Filament resource: list, create, edit, soft delete
- [ ] **W-1.1.5** Branch status toggle (open/closed)
- [ ] **W-1.1.6** Map pin picker (Google Maps API)
- [ ] **W-1.1.7** Branch image upload (cover + logo)

### W-1.2 Staff Module (Admin)
- [ ] **W-1.2.1** Migration: `staff` (linked to users, branch_id, role, pin)
- [ ] **W-1.2.2** Filament resource: CRUD
- [ ] **W-1.2.3** PIN reset flow
- [ ] **W-1.2.4** Ban/unban toggle
- [ ] **W-1.2.5** Multi-branch assignment
- [ ] **W-1.2.6** Soft delete with history preserved

### W-1.3 RBAC
- [ ] **W-1.3.1** Spatie Permission seeder with default roles
- [ ] **W-1.3.2** Roles: SuperAdmin, HQAdmin, OpsManager, MktManager, BranchManager, Cashier, Barista
- [ ] **W-1.3.3** Filament permission UI
- [ ] **W-1.3.4** Policy classes for each model
- [ ] **W-1.3.5** Branch-scoped data isolation (Branch Manager sees only own branch)

---

## Sprint W-2 — Menu & Catalog (Week 3)

### W-2.1 Category & Product Models
- [ ] **W-2.1.1** Migration: `categories` (name, slug, image, sort_order, status)
- [ ] **W-2.1.2** Migration: `products` (name, description, base_price, sku, sst_applicable, etc.)
- [ ] **W-2.1.3** Migration: `product_modifier_groups` + `modifier_options`
- [ ] **W-2.1.4** Migration: `branch_product` (pivot for availability + price override)
- [ ] **W-2.1.5** Models + relationships
- [ ] **W-2.1.6** Seeders with sample coffee/pastry data

### W-2.2 Filament Resources
- [ ] **W-2.2.1** Category CRUD with image upload
- [ ] **W-2.2.2** Product CRUD with image gallery
- [ ] **W-2.2.3** Modifier groups builder UI (Size, Sugar, Milk, Add-ons)
- [ ] **W-2.2.4** Branch availability toggle per product
- [ ] **W-2.2.5** Branch-specific pricing override
- [ ] **W-2.2.6** Bulk import products (CSV)
- [ ] **W-2.2.7** Bulk price update

### W-2.3 Branch-Specific Stock & Availability (Core Feature)
- [ ] **W-2.3.1** Migration: `branch_stock` (product_id, branch_id, quantity, low_threshold, is_available)
- [ ] **W-2.3.2** Stock management UI in Filament (HQ admin view)
- [ ] **W-2.3.3** Mark out-of-stock toggle (admin)
- [ ] **W-2.3.4** Low stock alert email/notification (HQ + branch manager)
- [ ] **W-2.3.5** **Branch-scoped menu API endpoint** — `GET /api/branches/{branch}/menu` returns only products where `branch_product.is_available=true` AND `branch_stock.quantity > 0`
- [ ] **W-2.3.6** Eloquent scope: `Product::availableAtBranch($branchId)` for reuse
- [ ] **W-2.3.7** Real-time stock event broadcast (Reverb) when item goes out-of-stock → customer UI auto-updates
- [ ] **W-2.3.8** Stock decrement on order confirmation (atomic transaction, prevents oversell)
- [ ] **W-2.3.9** Stock restore on order cancel/refund
- [ ] **W-2.3.10** Stock audit log (who changed, when, before/after qty)

---

## Sprint W-3 — Customer Web Frontend (Week 4)

### W-3.1 Customer Auth + Onboarding Flow (Inertia + React)

**Canonical flow:** Splash → Session Check → (Auth if needed) → Branch Select → Storefront

- [ ] **W-3.1.1** Splash screen (1-2s brand animation, lazy-load app shell behind)
- [ ] **W-3.1.2** Session/cache detection middleware — auto-route logged-in users
- [ ] **W-3.1.3** Login page (email/phone tabs)
- [ ] **W-3.1.4** Register page with referral code field
- [ ] **W-3.1.5** "Already have an account? Login" link on register screen
- [ ] **W-3.1.6** "New here? Register" link on login screen
- [ ] **W-3.1.7** OTP verification page (email + phone)
- [ ] **W-3.1.8** Forgot password flow
- [ ] **W-3.1.9** Profile setup page (DOB, photo, preferred branch) — required before first order
- [ ] **W-3.1.10** Auto-redirect after auth: → Branch Selection (if no preferred branch) → Storefront
- [ ] **W-3.1.11** Auto-redirect after splash: → Storefront if session valid + branch selected
- [ ] **W-3.1.12** Guest browse mode — allow menu view without login (login required at checkout)
- [ ] **W-3.1.13** App layout with header (logo, points, cart badge, branch name)
- [ ] **W-3.1.14** Mobile responsive bottom nav (Home, Order, Loyalty, Profile)

### W-3.2 Home Page
- [ ] **W-3.2.1** Banner carousel (swiper)
- [ ] **W-3.2.2** Featured products grid
- [ ] **W-3.2.3** Category quick access tiles
- [ ] **W-3.2.4** Order Again section (last 5 orders)
- [ ] **W-3.2.5** Loyalty preview card (points + tier)
- [ ] **W-3.2.6** Active vouchers shortcut

### W-3.3 Menu Browse (Branch-Filtered)
- [ ] **W-3.3.1** Category page with product grid — **filtered by selected branch only**
- [ ] **W-3.3.2** Product detail page with image gallery
- [ ] **W-3.3.3** Modifier selection UI (radio + checkbox + counter)
- [ ] **W-3.3.4** Add to cart with toast feedback
- [ ] **W-3.3.5** Search with filters — **scoped to selected branch's available items**
- [ ] **W-3.3.6** Out-of-stock indicator (per branch, real-time updates via Reverb)
- [ ] **W-3.3.7** Favorites (heart icon)
- [ ] **W-3.3.8** Branch-specific pricing display (uses `branch_product.price_override` if set, else base price)
- [ ] **W-3.3.9** Empty state when branch has no items (e.g., "Branch closed" or "Menu loading")
- [ ] **W-3.3.10** Branch switcher clears cart with confirmation modal (cart is branch-bound)

### W-3.4 Branch Selection
- [ ] **W-3.4.1** Branch list with map view
- [ ] **W-3.4.2** Geolocation auto-detect (browser API)
- [ ] **W-3.4.3** Distance + ETA display
- [ ] **W-3.4.4** Operating hours validation (block if closed)
- [ ] **W-3.4.5** Branch switcher in header

---

## Sprint W-4 — Cart, Checkout & Orders (Week 5)

### W-4.1 Cart (Branch-Bound)
- [ ] **W-4.1.1** Cart state (Zustand persist) — bound to one branch_id
- [ ] **W-4.1.2** Line item edit/remove
- [ ] **W-4.1.3** Voucher input field with validation
- [ ] **W-4.1.4** Loyalty redemption slider
- [ ] **W-4.1.5** Order notes textarea
- [ ] **W-4.1.6** Pickup time selector (ASAP / scheduled)
- [ ] **W-4.1.7** Cart totals (subtotal, SST, discount, grand total)
- [ ] **W-4.1.8** Cart validation on checkout — re-verify all items still available at branch (handle race conditions)
- [ ] **W-4.1.9** Stock conflict UI — if item went out-of-stock during browsing, show alert + remove from cart
- [ ] **W-4.1.10** Branch switch warning — "Switching branch will clear your cart. Continue?"

### W-4.2 Order Backend
- [ ] **W-4.2.1** Migrations: `orders`, `order_items`, `order_item_modifiers`
- [ ] **W-4.2.2** Order service (calculation, SST, discounts)
- [ ] **W-4.2.3** Order state machine (Received → Preparing → Ready → Completed)
- [ ] **W-4.2.4** Order events (broadcast via Reverb)
- [ ] **W-4.2.5** Order number generator (branch-prefixed)

### W-4.3 Checkout & Payment (Billplz)
- [ ] **W-4.3.1** Checkout page with totals breakdown
- [ ] **W-4.3.2** **Order type selector — Pickup or Dine-in** (radio cards)
- [ ] **W-4.3.3** Dine-in: table number entry / QR scan
- [ ] **W-4.3.4** Pickup: pickup time selector (ASAP / scheduled)
- [ ] **W-4.3.5** Billplz integration (collection setup)
- [ ] **W-4.3.6** Bill creation API call
- [ ] **W-4.3.7** Webhook handler (signature verify)
- [ ] **W-4.3.8** DuitNow QR display + verification
- [ ] **W-4.3.9** eWallet redirect flow
- [ ] **W-4.3.10** Order confirmation page (with pickup code or table number)
- [ ] **W-4.3.11** Receipt email (queued mailable)
- [ ] **W-4.3.12** Order broadcast → branch POS queue + TV display (if dine-in)

### W-4.4 Order Tracking
- [ ] **W-4.4.1** Order detail page (live status)
- [ ] **W-4.4.2** WebSocket subscription via Reverb
- [ ] **W-4.4.3** Order history list (paginated)
- [ ] **W-4.4.4** Reorder button
- [ ] **W-4.4.5** Cancel order flow (within X minutes)

---

## Sprint W-5 — Branch POS (Week 6)

### W-5.1 POS Auth & Layout
- [ ] **W-5.1.1** Staff PIN login screen
- [ ] **W-5.1.2** Tablet-optimized layout (landscape, large touch targets)
- [ ] **W-5.1.3** Active staff indicator + clock-out
- [ ] **W-5.1.4** Shift session tracking

### W-5.2 Order Queue
- [ ] **W-5.2.1** Live incoming orders panel (Reverb)
- [ ] **W-5.2.2** Sound + visual alert on new order
- [ ] **W-5.2.3** Status update buttons (Accept, Preparing, Ready, Complete)
- [ ] **W-5.2.4** Reject order with reason modal
- [ ] **W-5.2.5** Estimated prep time entry

### W-5.3 Walk-in POS
- [ ] **W-5.3.1** Quick product grid (touch-optimized)
- [ ] **W-5.3.2** Modifier selection sheet
- [ ] **W-5.3.3** Cart panel (right sidebar)
- [ ] **W-5.3.4** Cash payment flow
- [ ] **W-5.3.5** Card terminal trigger (placeholder API)
- [ ] **W-5.3.6** DuitNow QR display
- [ ] **W-5.3.7** Print receipt (thermal printer API)
- [ ] **W-5.3.8** Discount apply (with manager approval if > X%)
- [ ] **W-5.3.9** Refund / void flow

### W-5.4 Customer Lookup
- [ ] **W-5.4.1** Search by phone / membership ID / QR scan
- [ ] **W-5.4.2** Display customer profile + tier
- [ ] **W-5.4.3** Apply loyalty / voucher in-store
- [ ] **W-5.4.4** Earn points on walk-in transaction

### W-5.5 Branch Stock Self-Management (Branch Staff)
- [ ] **W-5.5.1** Stock screen in POS (own branch only — RBAC enforced)
- [ ] **W-5.5.2** Quick toggle: mark item out-of-stock (one tap)
- [ ] **W-5.5.3** Quick toggle: mark item back in-stock
- [ ] **W-5.5.4** Adjust stock quantity (with reason: sale, wastage, restock)
- [ ] **W-5.5.5** Stock movement audit log (per branch)
- [ ] **W-5.5.6** Low stock visual alert in POS dashboard
- [ ] **W-5.5.7** Stock toggle broadcasts to customer app (Reverb) — instant UI update
- [ ] **W-5.5.8** End-of-day stock summary report

### W-5.6 Order-Ready Notification Fork (Pickup vs Dine-in)
- [ ] **W-5.6.1** Order ready handler reads order type (pickup / dine-in)
- [ ] **W-5.6.2** **Pickup path** — fire `OrderReadyNotification`:
  - Web Push (Layer 1) — opens order detail
  - OnSend WhatsApp (Layer 2) — `order_ready` template
  - In-app notification + sound
- [ ] **W-5.6.3** **Dine-in path** — broadcast event to TV Display:
  - `OrderReadyForDineInEvent` via Reverb
  - Order number appears in TV "Ready" panel
  - Audio chime triggers on TV
  - No push/WhatsApp sent (customer is on-premise)
- [ ] **W-5.6.4** Both paths log notification delivery + status
- [ ] **W-5.6.5** Mixed-mode handling — if dine-in customer leaves before order ready, fallback to push

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

## Sprint W-6 — Loyalty, Vouchers, Membership, Dashboard (Week 7)

### W-6.1 Loyalty Engine
- [ ] **W-6.1.1** Migrations: `loyalty_points`, `point_transactions`
- [ ] **W-6.1.2** Earn rules engine (per RM spent, configurable)
- [ ] **W-6.1.3** Redemption flow (points → discount)
- [ ] **W-6.1.4** Points expiry job (12-month rolling)
- [ ] **W-6.1.5** Birthday bonus job (cron daily)
- [ ] **W-6.1.6** Manual point adjustment (admin, with reason)
- [ ] **W-6.1.7** Customer points history page

### W-6.2 Voucher System
- [ ] **W-6.2.1** Migrations: `vouchers`, `voucher_codes`, `voucher_redemptions`
- [ ] **W-6.2.2** Voucher templates (Filament builder)
- [ ] **W-6.2.3** Bulk code generation
- [ ] **W-6.2.4** Apply at checkout (validation)
- [ ] **W-6.2.5** Customer voucher wallet UI (active/used/expired tabs)
- [ ] **W-6.2.6** Voucher distribution to segment

### W-6.3 Membership Tiers
- [ ] **W-6.3.1** Migrations: `membership_tiers`, `customer_tiers`
- [ ] **W-6.3.2** Tier configuration UI (Filament)
- [ ] **W-6.3.3** Tier auto-upgrade job (daily cron)
- [ ] **W-6.3.4** Tier display on customer profile
- [ ] **W-6.3.5** Tier-based earn multiplier
- [ ] **W-6.3.6** Tier progress bar UI

### W-6.4 Promotion Engine
- [ ] **W-6.4.1** Migrations: `promotions`, `promotion_rules`
- [ ] **W-6.4.2** Filament promo builder
- [ ] **W-6.4.3** Eligibility engine (tier, segment, branch, time)
- [ ] **W-6.4.4** Auto-apply best promo logic
- [ ] **W-6.4.5** Promo banner CMS

### W-6.5 Admin Dashboard
- [ ] **W-6.5.1** Sales summary widgets (today, week, month)
- [ ] **W-6.5.2** Revenue trend chart (line)
- [ ] **W-6.5.3** Top products chart
- [ ] **W-6.5.4** Order type distribution pie
- [ ] **W-6.5.5** Hourly sales heatmap
- [ ] **W-6.5.6** Filter by branch + date range
- [ ] **W-6.5.7** Export to CSV / Excel
- [ ] **W-6.5.8** Drill-down detailed reports

### W-6.6 Branch Detail Dashboard
- [ ] **W-6.6.1** Per-branch dashboard widgets
- [ ] **W-6.6.2** Today's live orders
- [ ] **W-6.6.3** Staff on duty
- [ ] **W-6.6.4** Stock levels overview
- [ ] **W-6.6.5** Customer feedback / ratings

---

## Sprint W-7 — PWA, Notifications, Referral, Polish (Week 8)

### W-7.1 PWA Configuration
- [ ] **W-7.1.1** Manifest.json (icons all sizes, splash, theme color, name)
- [ ] **W-7.1.2** Service worker (Workbox runtime caching)
- [ ] **W-7.1.3** Offline menu cache (IndexedDB)
- [ ] **W-7.1.4** Install prompt UI (custom)
- [ ] **W-7.1.5** Add to Home Screen flow (iOS Safari + Android Chrome)
- [ ] **W-7.1.6** Update notification (new version available)
- [ ] **W-7.1.7** Splash screens for iOS

### W-7.2 Web Push Notifications
- [ ] **W-7.2.1** VAPID keys generation
- [ ] **W-7.2.2** Subscription endpoint (store push subscriptions per device)
- [ ] **W-7.2.3** Push send service (Laravel WebPush package: `minishlink/web-push`)
- [ ] **W-7.2.4** Order status push automation (received → accepted → preparing → ready → completed)
- [ ] **W-7.2.5** Promo broadcast composer (Filament)
- [ ] **W-7.2.6** Voucher expiry reminder push
- [ ] **W-7.2.7** Birthday greeting push
- [ ] **W-7.2.8** Notification permission UX — request after 1st order (not on first visit)
- [ ] **W-7.2.9** Tap-to-open deep link (notification opens order detail page)
- [ ] **W-7.2.10** Notification action buttons (e.g., "View Order", "Rate")
- [ ] **W-7.2.11** Subscription cleanup (remove dead subscriptions on 410 Gone response)

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

### W-7.4 Referral Program
- [ ] **W-7.4.1** Unique code per user (auto on signup)
- [ ] **W-7.4.2** Share intents (WhatsApp, copy link, social)
- [ ] **W-7.4.3** Referrer reward on referee's first purchase
- [ ] **W-7.4.4** Referee welcome bonus (voucher)
- [ ] **W-7.4.5** Referral history & earnings page
- [ ] **W-7.4.6** Anti-abuse (device fingerprint, IP check, single-use)

### W-7.5 Performance Polish
- [ ] **W-7.5.1** Image CDN + auto-optimize (Cloudflare Images)
- [ ] **W-7.5.2** Lazy load components (React.lazy)
- [ ] **W-7.5.3** Lighthouse audit (target > 90 on Mobile)
- [ ] **W-7.5.4** Database query optimization (eager loading)
- [ ] **W-7.5.5** Redis cache for hot data (menu, branches)
- [ ] **W-7.5.6** Bundle analysis + code splitting
- [ ] **W-7.5.7** Compress responses (gzip/brotli)

### W-7.6 Content & Settings
- [ ] **W-7.6.1** Banner CMS (Filament)
- [ ] **W-7.6.2** FAQ CMS
- [ ] **W-7.6.3** Terms & Conditions page
- [ ] **W-7.6.4** Privacy Policy page (PDPA compliant)
- [ ] **W-7.6.5** Cookie consent banner
- [ ] **W-7.6.6** Maintenance mode toggle (Filament)

---

## Sprint W-8 — Pilot, Bug Fix, Launch (Weeks 9-10)

### W-8.1 Pre-Launch QA
- [ ] **W-8.1.1** Full regression test suite (Pest)
- [ ] **W-8.1.2** Browser test: Chrome, Safari, Edge, Firefox
- [ ] **W-8.1.3** Mobile browser test: iOS Safari, Android Chrome
- [ ] **W-8.1.4** PWA install test on iOS + Android
- [ ] **W-8.1.5** Payment flow E2E (Billplz live + sandbox)
- [ ] **W-8.1.6** Load test (100 concurrent orders) — k6 or Artillery
- [ ] **W-8.1.7** Security audit (OWASP Top 10)
- [ ] **W-8.1.8** PDPA compliance audit
- [ ] **W-8.1.9** Accessibility audit (WCAG 2.1 AA)

### W-8.2 Pilot Branch
- [ ] **W-8.2.1** Deploy to production
- [ ] **W-8.2.2** Onboard 1 pilot branch staff (training session)
- [ ] **W-8.2.3** Real customer test orders (soft launch)
- [ ] **W-8.2.4** Daily bug triage + hotfix
- [ ] **W-8.2.5** Performance monitoring (Pulse + Sentry)

### W-8.3 Documentation
- [ ] **W-8.3.1** Admin user manual (PDF)
- [ ] **W-8.3.2** Branch staff training video / PDF
- [ ] **W-8.3.3** API docs (Swagger / Scribe)
- [ ] **W-8.3.4** Customer FAQ
- [ ] **W-8.3.5** Internal runbook (deployment, rollback, hotfix)

### W-8.4 Production Launch
- [ ] **W-8.4.1** Final security review sign-off
- [ ] **W-8.4.2** PDPA compliance check
- [ ] **W-8.4.3** T&C + Privacy Policy live
- [ ] **W-8.4.4** Marketing soft launch (social, email)
- [ ] **W-8.4.5** Monitor Sentry + Pulse + uptime
- [ ] **W-8.4.6** Post-launch retrospective

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
