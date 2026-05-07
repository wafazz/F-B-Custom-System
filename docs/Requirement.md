# Star Coffee — F&B Platform Requirement (Coffee & Pastry)

**Version:** 2.0
**Last Updated:** 2026-05-08
**Target Platforms:** Web App + PWA, iOS, Android
**Business Type:** Multi-branch Coffee & Pastry Chain

---

## 1. Project Overview

Star Coffee is a multi-branch F&B platform for coffee & pastry retail. The system serves three user groups:

1. **Customers** — order via mobile app (iOS/Android) or PWA web, earn loyalty, redeem vouchers
2. **Branch Staff** — POS, order management, inventory at branch level
3. **Admin / HQ** — global control, analytics, branch management, marketing

### 1.1 Platform Architecture (LOCKED)

**Phase 1 Focus:** Web App + PWA only. iOS/Android native deferred to Phase 3.

| Layer | Technology | Version |
|---|---|---|
| Backend Framework | **Laravel** | **12.x (LTS-grade stable)** |
| PHP Runtime | **PHP** | **8.2 / 8.3** |
| Database | **MySQL** | **8.0+** |
| Admin Portal | **Filament** | **3.x** |
| Customer Web + PWA | **Inertia 2 + React 18 + TypeScript 5** | latest |
| Branch POS (tablet web) | Same Inertia + React app | latest |
| UI Components | **shadcn/ui** + Tailwind | Tailwind 3.4 |
| Forms & Validation | React Hook Form + Zod | latest |
| Server State | TanStack Query | v5 |
| Build Tool | **Vite** | 5.x |
| Real-time | **Laravel Reverb** (WebSocket) | built-in |
| Queue | **Redis + Horizon** | latest |
| PWA | **vite-plugin-pwa + Workbox** | latest |
| Push (Web) | **Web Push API + VAPID** | native |
| Payment | Billplz + DuitNow QR + eWallets | — |
| Storage | Cloudflare R2 / AWS S3 | — |
| Hosting | VPS (Hetzner/DO) + Cloudflare CDN | — |
| Error Tracking | Sentry | — |

**Phase 3 (future) Mobile:** **React Native + Expo** — reuses 60-70% logic from Phase 1 web (hooks, types, API client, validation, utilities).

### 1.2 Compliance & Localization
- **Country:** Malaysia (primary)
- **Currency:** MYR (RM)
- **Tax:** SST 6% (configurable per item)
- **Language:** English + Bahasa Malaysia (i18n ready)
- **Data Protection:** PDPA Malaysia compliant
- **Payment:** Malaysian payment gateways (Billplz, eWallet, FPX, DuitNow QR)

---

## 2. Customer Mobile App (iOS + Android) & PWA

### 2.1 Onboarding & Authentication
- [ ] Splash screen with brand logo
- [ ] Walkthrough/intro slides (first launch only)
- [ ] Sign up via:
  - Email + password
  - Phone number + OTP (SMS)
  - Google Sign-In
  - Apple Sign-In (iOS requirement)
  - Facebook Login (optional)
- [ ] Email verification (link/OTP)
- [ ] Phone verification (OTP)
- [ ] Forgot password (email link / phone OTP)
- [ ] Profile completion:
  - Full name
  - Email
  - Phone
  - Date of birth (for birthday rewards)
  - Gender (optional)
  - Profile photo
  - Preferred branch
- [ ] Referral code entry (during signup)
- [ ] Terms & Conditions + Privacy Policy acceptance
- [ ] Marketing consent (PDPA)

### 2.2 Home & Discovery
- [ ] Dynamic banner carousel (promotions)
- [ ] Featured products section
- [ ] Category quick access (Coffee, Pastry, Tea, Cold Drinks, etc.)
- [ ] "Order Again" — last 5 orders
- [ ] Nearest branch indicator
- [ ] Loyalty points balance preview
- [ ] Active vouchers count
- [ ] Membership tier badge

### 2.3 Menu & Product Catalog
- [ ] Browse by category
- [ ] Search products (with filters: price, category, dietary)
- [ ] Product detail page:
  - Image gallery
  - Description
  - Price
  - Nutrition info (calories, allergens)
  - Modifiers (size, sugar level, milk type, temperature, add-ons)
  - Quantity selector
  - Add to cart
  - Add to favorites
- [ ] Modifier groups:
  - **Size:** Regular / Large
  - **Temperature:** Hot / Iced
  - **Sugar Level:** 0%, 25%, 50%, 75%, 100%
  - **Milk:** Fresh, Oat, Soy, Almond (price difference)
  - **Add-ons:** Extra shot, whipped cream, syrup flavors
- [ ] Out-of-stock indicator (per branch)
- [ ] Recommended pairings ("Goes well with...")

### 2.4 Order Flow

#### 2.4.1 Order Type Selection
- [ ] **Pickup** — order ahead, collect at branch
- [ ] **Dine-in** — scan table QR at branch
- [ ] **Delivery** — (optional Phase 2, integrate Lalamove/Grab)

#### 2.4.2 Branch Selection
- [ ] Auto-detect nearest branch (GPS)
- [ ] Manual branch selection (list + map)
- [ ] Show branch info: address, hours, distance, busy status
- [ ] Branch operating hours validation (block orders outside hours)

#### 2.4.3 Cart & Checkout
- [ ] Cart with line items, modifiers, quantity
- [ ] Edit/remove items
- [ ] Apply voucher / promo code
- [ ] Apply loyalty points redemption
- [ ] Order notes (e.g., "less ice")
- [ ] Pickup time selection (ASAP / scheduled)
- [ ] Subtotal, SST, discount, total breakdown
- [ ] Earned loyalty points preview

#### 2.4.4 Payment
- [ ] Billplz (FPX online banking)
- [ ] DuitNow QR
- [ ] eWallet: GrabPay, Touch'n Go, Boost, ShopeePay
- [ ] Credit/Debit card (via Billplz/Stripe)
- [ ] Stored cards (tokenized, PCI-compliant)
- [ ] Pay at counter (cash, optional)

#### 2.4.5 Order Tracking
- [ ] Live status: Received → Preparing → Ready → Completed
- [ ] Push notification per status change
- [ ] Estimated ready time
- [ ] QR code / order number for pickup
- [ ] Cancel order (within X minutes, configurable)

### 2.5 Loyalty & Rewards
- [ ] Points balance display
- [ ] Earn rate (e.g., RM1 = 1 point, configurable)
- [ ] Redeem points for:
  - Free items (catalog)
  - Discount on order
  - Voucher conversion
- [ ] Points history (earned + redeemed)
- [ ] Points expiry rules (e.g., 12 months)
- [ ] Birthday bonus points (auto-credit)
- [ ] Streak rewards (visit X days in a row)

### 2.6 Membership Tiers
- [ ] Tiers: **Basic → Silver → Gold → Platinum**
- [ ] Tier criteria: spending threshold over rolling 12 months
- [ ] Tier benefits:
  - Higher earn rate
  - Exclusive vouchers
  - Free upgrades
  - Priority queue
  - Birthday gifts
- [ ] Tier progress bar (e.g., "Spend RM50 more to reach Gold")
- [ ] Auto-upgrade / downgrade engine
- [ ] Tier expiry & retention rules

### 2.7 Vouchers & Promotions
- [ ] Voucher wallet (active, used, expired tabs)
- [ ] Voucher detail: T&C, validity, branch restrictions
- [ ] Digital voucher (QR/code redeemable in-store)
- [ ] Auto-apply best voucher at checkout (optional toggle)
- [ ] Promo banner deep links to product/category

### 2.8 Referral Program
- [ ] Unique referral code per user
- [ ] Share via WhatsApp, social, copy link
- [ ] Referrer reward (points/voucher) on referee's first purchase
- [ ] Referee welcome bonus
- [ ] Referral history & earnings
- [ ] Anti-abuse: device fingerprint, single-use per device

### 2.9 Profile & Account
- [ ] Edit profile (name, phone, email, DOB, photo)
- [ ] Change password
- [ ] Saved addresses (for delivery)
- [ ] Saved payment methods
- [ ] Order history (with reorder button)
- [ ] Favorite items
- [ ] Notification preferences (push, email, SMS toggles)
- [ ] Language preference
- [ ] Logout / Delete account (PDPA right to erasure)

### 2.10 Notifications

**Two-Layer Strategy:**
- **Layer 1 (Primary):** Web Push Notification (Web/PWA) — free, instant, official Web standard
- **Layer 2 (Fallback):** **OnSend WhatsApp API** (onsend.io) — supplemental for users without push subscription (especially iOS Safari without PWA installed)

⚠️ **OnSend is unofficial WhatsApp gateway** — risk mitigations include backup numbers, anti-ban delays, kill-switch, and email fallback.

- [ ] Web Push notifications (Layer 1)
- [ ] OnSend WhatsApp notifications (Layer 2 fallback)
- [ ] Smart routing engine (push first, WhatsApp on failure / iOS without PWA)
- [ ] Order status updates (push + WhatsApp for critical events)
- [ ] Promotional broadcasts (push only — protects WhatsApp number from anti-spam ban)
- [ ] Voucher expiry reminders (push)
- [ ] Birthday greetings (push + WhatsApp)
- [ ] Tier upgrade celebrations (push)
- [ ] In-app notification center (history)
- [ ] Email notifications (transactional only — receipts, password reset, fallback if WhatsApp disabled)
- [ ] SMS notifications (OTP only — phone verification)
- [ ] Admin-configurable per-event channel matrix
- [ ] OnSend free-form template management (no Meta approval needed)
- [ ] Customer opt-out controls (per channel)
- [ ] OnSend device health monitoring + auto-failover to backup number
- [ ] Number warmup procedure (anti-ban)
- [ ] Emergency kill-switch (admin disables WhatsApp instantly)

### 2.11 Support & Feedback
- [ ] In-app FAQ
- [ ] Contact support (chat / email / WhatsApp)
- [ ] Order rating (1-5 stars + comment)
- [ ] Product review
- [ ] Report issue (with photo upload)

### 2.12 PWA-Specific Features
- [ ] Installable (Add to Home Screen)
- [ ] Service worker (offline menu browsing)
- [ ] Push notifications (Web Push API)
- [ ] Camera access (QR scan)
- [ ] Geolocation (nearest branch)
- [ ] App-like UI (no browser chrome)

### 2.13 Mobile-Native Features
- [ ] Biometric login (Face ID / Touch ID / Fingerprint)
- [ ] Apple Wallet pass for membership card (iOS)
- [ ] Google Wallet pass (Android)
- [ ] Deep links + universal links
- [ ] Force update mechanism (version control)
- [ ] App rating prompt (after positive interactions)

---

## 2bis. Customer Flow (Canonical)

**End-to-end happy path:**

1. **Splash Screen** (1-2s brand animation)
2. **Session Check**
   - Has valid session/cache → auto-login → skip to Branch Selection
   - No session → Auth screen (Register or Login)
3. **Auth (if needed)**
   - Register → OTP verification → Profile setup
   - Login → enter credentials → session created
4. **Branch Selection**
   - Auto-detect nearest (GPS)
   - Manual pick from list / map
5. **Storefront** (Menu Browse, filtered to selected branch)
6. **Checkout**
   - Order type: Pickup or Dine-in
   - Apply voucher / loyalty / payment
7. **Order Received** at Branch POS (live alert via WebSocket)
8. **Order Processing** (Preparing status)
9. **Order Ready** — fork by order type:
   - **Pickup** → Web Push + OnSend WhatsApp notification to customer
   - **Dine-in** → Order number displayed on **TV Display Screen** at branch + audio chime
10. **Order Completed** (rating + loyalty points credited)

---

## 2ter. TV Display Screen (Dine-in Order Number Board)

**Purpose:** Public-facing display at each branch showing dine-in order numbers — replaces traditional "now serving" calls.

**Use case:** Customer dines in, places order via app → gets order number → sits at table → watches TV display → walks to counter when their number appears in "Ready" panel.

### Hardware Options
- Smart TV + mini PC (Intel NUC / Mac Mini)
- Smart TV + Chromecast / Android TV stick (browser kiosk app)
- Large iPad in landscape, wall-mounted
- Any monitor + Raspberry Pi 4 with Chromium kiosk mode

### Software Requirements
- [ ] Web route: `/branch/{branchId}/display` (no customer auth, branch-scoped token)
- [ ] Full-screen kiosk layout (auto-hide cursor, no browser chrome)
- [ ] Two main panels:
  - **Now Preparing** — orders currently being made (left side, smaller)
  - **Ready for Pickup** — orders ready to collect (right side, large, highlighted)
- [ ] Real-time WebSocket updates via Laravel Reverb
- [ ] Audio chime when new order moves to "Ready"
- [ ] Order numbers auto-clear from "Ready" after X minutes (configurable, default 10 min)
- [ ] Rotating promo banner / brand video in idle area
- [ ] Branch logo + name header
- [ ] Current time + date footer
- [ ] Connection status indicator (online/offline)
- [ ] Auto-reconnect on network drop
- [ ] Dark mode support (for low-light cafes)
- [ ] Number masking for privacy (show last 4 digits or pickup code)

### Admin Configuration
- [ ] Per-branch display token generation
- [ ] Display layout customization (colors, fonts, logo)
- [ ] Idle media slots (banners, videos)
- [ ] Sound on/off + volume
- [ ] Auto-clear timeout (minutes)
- [ ] Number display format (full / masked / pickup code)

---

## 3. Branch POS / Staff App (Tablet PWA)

### 3.1 Staff Authentication
- [ ] Staff PIN login (fast)
- [ ] Role-based access (Cashier, Barista, Manager)
- [ ] Shift clock-in / clock-out
- [ ] Activity log per staff

### 3.2 Order Management
- [ ] Live order queue (incoming online orders)
- [ ] Sound + visual alert for new orders
- [ ] Update order status: Accept → Preparing → Ready → Completed
- [ ] Reject / cancel order (with reason)
- [ ] Walk-in order entry (POS mode)
- [ ] Print receipt (thermal printer)
- [ ] Print kitchen ticket (separate printer)
- [ ] Order modifications (add note, remove item)

### 3.3 POS / Counter Sale
- [ ] Quick product grid (categorized)
- [ ] Modifier selection
- [ ] Apply discount (manager approval for above X%)
- [ ] Split bill / split payment
- [ ] Cash drawer integration
- [ ] Card terminal integration
- [ ] QR payment display (DuitNow)
- [ ] Print receipt / email receipt
- [ ] Refund / void (with reason + manager approval)
- [ ] End-of-day Z-report

### 3.4 Stock Management (Branch-level)
- [ ] View current stock per item / ingredient
- [ ] Mark item as out-of-stock (real-time sync to customer app)
- [ ] Stock take entry
- [ ] Low stock alerts
- [ ] Receive stock (incoming from HQ/warehouse)
- [ ] Wastage logging

### 3.5 Customer Lookup
- [ ] Search customer by phone / membership ID / QR
- [ ] Apply loyalty / voucher in-store
- [ ] Show customer profile + order history

### 3.6 Branch Dashboard (Staff View)
- [ ] Today's sales summary
- [ ] Today's order count
- [ ] Top-selling items
- [ ] Pending orders count
- [ ] Average prep time

---

## 4. Admin Portal (Web — Desktop)

### 4.1 Authentication & Security
- [ ] Email + password login
- [ ] 2FA (TOTP / SMS)
- [ ] Session timeout
- [ ] IP whitelist (optional)
- [ ] Audit log (all admin actions)
- [ ] Password policy (length, complexity, rotation)

### 4.2 Dashboard (Global)
- [ ] **Sales Summary:** Today / Week / Month / Custom range
- [ ] **KPIs:** Total revenue, orders, avg order value, new customers
- [ ] **Charts:**
  - Revenue trend (line)
  - Orders by branch (bar)
  - Top-selling products (horizontal bar)
  - Order type distribution (pie: pickup vs dine-in vs delivery)
  - Hourly sales heatmap
- [ ] **Filters:** Date range, branch, category
- [ ] **Drill-down:** Click chart → detailed report
- [ ] **Real-time:** Live order ticker
- [ ] **Export:** CSV, Excel, PDF
- [ ] **Comparisons:** vs previous period, YoY

### 4.3 Branch Management
- [ ] List all branches (search, filter)
- [ ] Add branch:
  - Name, code, address (with map pin)
  - Contact (phone, email, manager)
  - Operating hours per day
  - Pickup radius
  - Tax/SST settings
  - Receipt header/footer
  - Printer config
- [ ] Edit branch
- [ ] Toggle branch open/closed (temporary)
- [ ] Soft delete branch (preserve history)
- [ ] Branch staff assignment
- [ ] Branch-specific menu (enable/disable items per branch)
- [ ] Branch-specific pricing (override)

### 4.4 Branch Detail Page (Per-Branch Dashboard)
- [ ] Same dashboard widgets as global, scoped to one branch
- [ ] Today's live orders
- [ ] Staff on duty
- [ ] Stock levels
- [ ] Performance vs target
- [ ] Customer feedback / ratings for this branch

### 4.5 Staff Management
- [ ] List staff (filter by branch, role, status)
- [ ] Add staff:
  - Name, IC/passport, phone, email
  - Role (multi-role)
  - Assigned branch(es)
  - Employment type (full-time, part-time)
  - PIN for POS
  - Photo
- [ ] Edit staff
- [ ] Ban / unban
- [ ] Reset password / PIN
- [ ] Soft delete
- [ ] Shift schedule (optional Phase 2)
- [ ] Performance: orders processed, avg prep time

### 4.6 Role-Based Access Control (RBAC)
- [ ] Predefined roles:
  - **Super Admin** — all access
  - **HQ Admin** — all except billing
  - **Operations Manager** — branches, staff, orders
  - **Marketing Manager** — promo, voucher, loyalty
  - **Branch Manager** — own branch only
  - **Cashier** — POS + own orders
  - **Barista** — order queue only
- [ ] Custom roles (granular permissions)
- [ ] Permission matrix UI
- [ ] Role assignment per staff

### 4.7 Menu / Product Management
- [ ] Categories (CRUD, reorder, image)
- [ ] Products (CRUD):
  - Name (multi-language)
  - Description
  - Image gallery
  - Base price
  - Cost price (for margin)
  - SKU
  - SST applicable (yes/no)
  - Nutrition info
  - Allergens
  - Tags
  - Available branches
  - Available modifiers
  - Stock tracking (yes/no)
- [ ] Modifier groups & options:
  - Group name (e.g., "Size")
  - Options with price delta
  - Required / optional
  - Min/max selections
- [ ] Bulk import (CSV)
- [ ] Bulk price update
- [ ] Schedule price change (effective date)
- [ ] Recipe builder (link product to ingredients for stock deduction)

### 4.8 Order Management (Global)
- [ ] All orders list (search, filter by branch, status, date, customer)
- [ ] Order detail view
- [ ] Manual order creation
- [ ] Cancel / refund (with audit trail)
- [ ] Issue replacement order
- [ ] Export orders (CSV)

### 4.9 Promotion Management
- [ ] Create promo:
  - Type: % discount, fixed discount, BOGO, bundle, free item
  - Eligibility: all customers, members only, tier-based, new customers, segment
  - Min spend
  - Max discount
  - Usage limit (total + per customer)
  - Branch restriction
  - Date range
  - Day/time restriction (e.g., happy hour 3-5pm)
  - Stackable (yes/no)
- [ ] Promo banner upload (with deep link)
- [ ] A/B testing (optional)
- [ ] Performance: redemptions, revenue impact

### 4.10 Voucher Management
- [ ] Voucher templates:
  - Name, description, image
  - Value type: % / fixed / free item
  - Code generation (single / bulk codes / unique-per-user)
  - Validity period
  - Usage rules
  - Issuance method: manual, signup bonus, birthday, tier perk, loyalty redemption
- [ ] Bulk distribute to segment
- [ ] Track issuance + redemption
- [ ] Expire/revoke voucher

### 4.11 Loyalty Management
- [ ] Earn rules:
  - Per RM spent
  - Per visit
  - Per product (boost)
  - Bonus events (double points day)
- [ ] Redemption rules:
  - Points-to-RM ratio
  - Min redemption
  - Eligible products
- [ ] Expiry policy (rolling 12 months / fixed date)
- [ ] Manual point adjustment (with reason + audit)
- [ ] Loyalty analytics

### 4.12 Membership Management
- [ ] Tier configuration:
  - Tier name + color/badge
  - Qualification criteria (spending threshold)
  - Benefits (earn multiplier, perks)
  - Validity period
- [ ] Tier auto-upgrade engine (cron)
- [ ] Manual tier override
- [ ] Tier transition history per customer

### 4.13 Customer Management (CRM)
- [ ] Customer list (search, filter by tier, branch, spending, last visit)
- [ ] Customer detail:
  - Profile, contact, DOB
  - Order history, total spent, visit count
  - Loyalty points, tier, vouchers
  - Notes (staff-added)
  - Communication log
- [ ] Segment builder (rules-based: age, tier, spend, branch, last order)
- [ ] Send broadcast (push / email / SMS) to segment
- [ ] Block / unblock customer
- [ ] Export customer data
- [ ] PDPA tools: anonymize, export, delete

### 4.14 Stock & Inventory (Branch-based)
- [ ] Ingredient master (name, unit, cost)
- [ ] Recipe linkage (product → ingredients)
- [ ] Stock per branch
- [ ] Stock movements (purchase, sale, wastage, transfer)
- [ ] Low stock alerts (configurable threshold)
- [ ] Stock take session (with variance report)
- [ ] Inter-branch transfer
- [ ] Supplier management
- [ ] Purchase orders

### 4.15 Payment & Billplz Settings
- [ ] Billplz API key & collection ID
- [ ] eWallet credentials
- [ ] Test / live mode toggle
- [ ] Transaction logs
- [ ] Reconciliation report
- [ ] Failed payment retry

### 4.16 Notifications & Marketing
- [ ] Push notification composer (with image, deep link)
- [ ] Email template builder
- [ ] SMS template
- [ ] Schedule send / immediate
- [ ] Segment targeting
- [ ] A/B test
- [ ] Open / click tracking
- [ ] Notification history

### 4.17 Reports & Analytics
- [ ] Sales report (by branch, product, category, payment method)
- [ ] Daily / weekly / monthly / custom
- [ ] Loyalty report (earn / burn)
- [ ] Voucher utilization report
- [ ] Customer acquisition / retention
- [ ] Cohort analysis
- [ ] Refund / cancellation report
- [ ] Staff performance report
- [ ] Stock valuation report
- [ ] SST report (for tax filing)
- [ ] Export all reports (PDF / Excel)

### 4.18 Content Management
- [ ] Banner management (web + app, with deep links)
- [ ] FAQ management
- [ ] Terms & Conditions
- [ ] Privacy Policy
- [ ] About Us
- [ ] App version control (force update mobile)
- [ ] Maintenance mode toggle

### 4.19 Settings (Global)
- [ ] Brand settings (logo, colors, name)
- [ ] Tax / SST configuration
- [ ] Currency settings
- [ ] Default operating hours
- [ ] Order rules (cancellation window, prep time)
- [ ] Loyalty defaults
- [ ] Email/SMS provider config
- [ ] FCM credentials
- [ ] Map API key (Google Maps)
- [ ] Backup & restore

### 4.20 Audit & Logs
- [ ] All admin actions logged (who, what, when, IP)
- [ ] Filter by user, action type, date
- [ ] Login attempts log
- [ ] API access log

---

## 5. Customer Web App + PWA (Desktop & Mobile Web)

All Section 2 features adapted to responsive web, plus:

- [ ] SEO-optimized public pages (menu, branches, about)
- [ ] Public branch finder (no login required)
- [ ] Public menu browse (no login required)
- [ ] Sitemap + robots.txt
- [ ] Open Graph / Twitter Cards
- [ ] Google Analytics / Meta Pixel
- [ ] Cookie consent banner (PDPA)
- [ ] Web Push notifications
- [ ] Add to Home Screen prompt
- [ ] Offline fallback page

---

## 6. Backend / API

### 6.1 Architecture
- [ ] RESTful API (versioned: /api/v1/...)
- [ ] WebSocket / SSE for real-time order updates
- [ ] JWT or Sanctum authentication
- [ ] Rate limiting (per IP, per user)
- [ ] API documentation (Swagger / Postman)
- [ ] CORS configuration

### 6.2 Database
- [ ] MySQL 8 / PostgreSQL 14+
- [ ] Migrations + seeders
- [ ] Soft deletes where applicable
- [ ] Indexed for performance
- [ ] Foreign key constraints
- [ ] Daily automated backups
- [ ] Point-in-time recovery

### 6.3 Background Jobs
- [ ] Queue worker (Redis / Database)
- [ ] Scheduled tasks (cron):
  - Tier auto-upgrade
  - Voucher expiry
  - Points expiry
  - Birthday rewards
  - Stock low-alert checks
  - Daily report generation
  - Database backup
- [ ] Failed job retry & notification

### 6.4 Third-Party Integrations
- [ ] Billplz (payment)
- [ ] eWallet APIs (GrabPay, TnG, Boost)
- [ ] Firebase Cloud Messaging (push)
- [ ] Twilio / MessageBird (SMS)
- [ ] SendGrid / SES (email)
- [ ] Google Maps API (geocoding, distance)
- [ ] Cloudflare R2 / AWS S3 (file storage)
- [ ] Sentry (error tracking)
- [ ] Mixpanel / GA4 (analytics)

### 6.5 Security
- [ ] HTTPS enforced
- [ ] OWASP Top 10 protection
- [ ] SQL injection prevention (parameterized queries)
- [ ] XSS prevention
- [ ] CSRF tokens
- [ ] Input validation everywhere
- [ ] Rate limiting on auth endpoints
- [ ] Bcrypt/Argon2 password hashing
- [ ] Tokenized payment cards (PCI-DSS via gateway)
- [ ] Encrypted sensitive data at rest
- [ ] Regular security audits
- [ ] Penetration testing pre-launch

---

## 7. Non-Functional Requirements

### 7.1 Performance
- [ ] API response < 300ms (p95)
- [ ] Mobile app cold start < 3s
- [ ] Web Lighthouse score > 90
- [ ] Image CDN with auto-optimization
- [ ] Database queries indexed
- [ ] Redis cache for hot data

### 7.2 Scalability
- [ ] Horizontal scaling ready (stateless API)
- [ ] CDN for static assets
- [ ] Queue-based async processing
- [ ] Database read replicas (future)

### 7.3 Reliability
- [ ] 99.5% uptime SLA
- [ ] Automated health checks
- [ ] Graceful degradation
- [ ] Circuit breakers for third-party APIs
- [ ] Daily backups + tested restore

### 7.4 Accessibility
- [ ] WCAG 2.1 AA compliance (web)
- [ ] iOS VoiceOver support
- [ ] Android TalkBack support
- [ ] High contrast mode
- [ ] Adjustable font size

### 7.5 Localization
- [ ] English + Bahasa Malaysia
- [ ] Date/time localization
- [ ] Currency formatting (MYR)
- [ ] RTL-ready (future-proof)

---

## 8. Phased Delivery Plan (Suggested)

### Phase 1 — MVP (3-4 months)
- Customer web + PWA + iOS + Android (basic order flow)
- Branch POS (basic)
- Admin: branches, staff, menu, orders, basic dashboard
- Loyalty (basic earn/redeem)
- Billplz + DuitNow QR payment
- Pickup orders only

### Phase 2 — Growth (2 months)
- Vouchers + Promotions engine
- Membership tiers
- Referral program
- Push notifications + segments
- Stock management (basic)
- Dine-in (table QR)

### Phase 3 — Scale (2 months)
- Advanced analytics + cohort
- Recipe-based stock auto-deduction
- Inter-branch transfers
- A/B testing
- Delivery integration (Lalamove/Grab)
- Apple/Google Wallet pass

### Phase 4 — Optimization (ongoing)
- Performance tuning
- Marketing automation
- Advanced reports
- AI recommendations (optional)

---

## 9. Out of Scope (Phase 1)

- Franchise / multi-tenant (different brands)
- Self-delivery fleet
- Kitchen Display System (KDS) — separate hardware
- Online table reservation
- Catering / bulk orders
- Subscription (coffee-as-a-service)
- Gift cards (physical)

---

## 10. Decisions Locked & Pending

### ✅ Locked
1. **Backend:** Laravel 12 + PHP 8.2/8.3 + MySQL 8
2. **Admin:** Filament 3
3. **Frontend:** Inertia 2 + React 18 + TypeScript 5 + shadcn/ui + Tailwind
4. **Phase 1 Scope:** Web App + PWA only (iOS/Android deferred)
5. **Real-time:** Laravel Reverb
6. **Mobile (Phase 3):** React Native + Expo

### ⏳ Pending (need Fakrul input before Sprint 0)
1. **Hosting provider:** Hetzner / DigitalOcean / Vultr / cPanel?
2. **Payment gateway primary:** Billplz only, or also iPay88 / Stripe?
3. **POS hardware:** iPad / Android tablet? Thermal printer model?
4. **Initial branch count?** (affects seed data + load planning)
5. **Branding assets ready?** (logo, color palette, fonts)
6. **Existing customer data?** (migration needed)
7. **SST registered?** (affects tax handling on receipts)
8. **Delivery partner preference?** (Phase 3)

---

## 11. Success Metrics (Post-Launch)

- Customer app DAU / MAU ratio > 25%
- Average order value (AOV)
- Order completion rate > 95%
- Loyalty enrollment rate > 70% of repeat customers
- App Store rating > 4.5
- Customer retention (30-day) > 40%
- Order processing time (online) < 15 min
- Refund rate < 2%

---

**End of Requirement Document**
 