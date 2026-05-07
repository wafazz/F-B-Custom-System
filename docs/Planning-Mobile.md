# Star Coffee — Planning (Phase 3: iOS + Android Native)

**Project:** Star Coffee — Multi-branch F&B Platform
**Phase:** 3 of 3 — Native Mobile (iOS + Android)
**Stack:** React Native 0.74+ + Expo SDK 51+ + TypeScript + Tamagui/NativeWind
**Target Duration:** ~8 weeks
**Prerequisite:** Phase 1 Web backend stable (`Planning-Web.md` Sprint W-4 minimum)
**Task ID Prefix:** `M-` (Mobile)

---

## Status Legend
- `[ ]` — Pending / Not started
- `[~]` — In progress
- `[✔]` — Completed

## Traceability
Each task has a unique ID `M-{sprint}.{section}.{task}` for cross-referencing in commits, PRs, session memory, and bug reports.
Example commit: `feat(M-2.3.1): integrate menu browse with API`

---

## Mobile Stack (LOCKED)

| Layer | Technology |
|---|---|
| Framework | React Native 0.74+ |
| Toolchain | Expo SDK 51+ (managed workflow) |
| Language | TypeScript 5 (strict) |
| Navigation | Expo Router (file-based) |
| UI | Tamagui or NativeWind (Tailwind) + custom |
| Forms | React Hook Form + Zod |
| Server State | TanStack Query v5 (shared hook patterns from web) |
| Client State | Zustand (shared from web) |
| API Client | Axios (shared from web) |
| Push | Expo Notifications + FCM (Android) + APNS (iOS) |
| Storage | expo-secure-store + AsyncStorage |
| Auth | Sanctum tokens + biometric unlock |
| Maps | react-native-maps |
| Camera/QR | expo-camera + expo-barcode-scanner |
| Wallet Pass | passkit-generator (server) + expo-apple-pay |
| Build | EAS Build |
| Submit | EAS Submit |
| OTA Updates | EAS Update |
| Analytics | Mixpanel + Sentry |

---

## Sprint Overview

| Sprint | ID | Week | Focus | Status |
|---|---|---|---|---|
| Mobile Foundation | M-0 | 1 | Expo setup, monorepo, shared packages | [ ] |
| Auth & Onboarding | M-1 | 2 | Login, register, OTP, profile setup | [ ] |
| Home & Menu | M-2 | 3 | Home feed, browse, product detail | [ ] |
| Cart & Checkout | M-3 | 4 | Cart, payment integration, order placement | [ ] |
| Order Tracking & Push | M-4 | 5 | Real-time tracking, FCM/APNS push | [ ] |
| Loyalty/Voucher/Profile | M-5 | 6 | Points, vouchers, tier, settings | [ ] |
| Native Features | M-6 | 7 | Biometric, wallet pass, deep links, QR | [ ] |
| QA & App Store Submit | M-7 | 8 | Beta, store assets, submission | [ ] |
| Launch & Monitor | M-8 | post | Production release + monitoring | [ ] |

---

## Sprint M-0 — Mobile Foundation (Week 1)

### M-0.1 Prerequisite Verification
- [ ] **M-0.1.1** Phase 1 web backend (`Planning-Web.md` W-4) is in production
- [ ] **M-0.1.2** API documentation (Scribe/Swagger) up-to-date
- [ ] **M-0.1.3** Sanctum token endpoints tested
- [ ] **M-0.1.4** Apple Developer account active ($99/yr)
- [ ] **M-0.1.5** Google Play Console account active ($25 one-time)
- [ ] **M-0.1.6** Expo account + EAS subscription decided

### M-0.2 Repository Structure (Monorepo)
- [ ] **M-0.2.1** Decide: monorepo (pnpm workspaces / Turborepo) or separate repo
- [ ] **M-0.2.2** If monorepo: `apps/web`, `apps/mobile`, `packages/shared`
- [ ] **M-0.2.3** Shared package contains: types, API client, validation schemas, hooks, utils
- [ ] **M-0.2.4** Setup TypeScript path aliases

### M-0.3 Expo Project Init
- [ ] **M-0.3.1** `pnpm create expo-app star-coffee-mobile --template`
- [ ] **M-0.3.2** Install Expo Router (file-based navigation)
- [ ] **M-0.3.3** Configure `app.json` (name, slug, icon, splash, scheme)
- [ ] **M-0.3.4** Setup environment variables (expo-constants + .env)
- [ ] **M-0.3.5** Configure TypeScript strict mode
- [ ] **M-0.3.6** ESLint + Prettier (shared config from web)

### M-0.4 Core Mobile Packages
- [ ] **M-0.4.1** `@tanstack/react-query` (same version as web)
- [ ] **M-0.4.2** `zustand` (same version as web)
- [ ] **M-0.4.3** `axios` (shared API client)
- [ ] **M-0.4.4** `react-hook-form` + `zod`
- [ ] **M-0.4.5** `expo-secure-store` (token storage)
- [ ] **M-0.4.6** `nativewind` or `tamagui` (UI framework)
- [ ] **M-0.4.7** `expo-image` (optimized images)
- [ ] **M-0.4.8** `expo-router`
- [ ] **M-0.4.9** `expo-localization` (i18n)
- [ ] **M-0.4.10** `expo-notifications`
- [ ] **M-0.4.11** `@react-native-firebase/app` + `@react-native-firebase/messaging`
- [ ] **M-0.4.12** `expo-camera` + `expo-barcode-scanner`
- [ ] **M-0.4.13** `react-native-maps`
- [ ] **M-0.4.14** `expo-location`
- [ ] **M-0.4.15** `expo-local-authentication` (biometric)
- [ ] **M-0.4.16** `@sentry/react-native`

### M-0.5 Brand & Assets
- [ ] **M-0.5.1** App icon (1024x1024 master + iOS/Android variants)
- [ ] **M-0.5.2** Splash screen (light + dark)
- [ ] **M-0.5.3** Custom font loading (expo-font)
- [ ] **M-0.5.4** Brand color tokens (sync with web design system)
- [ ] **M-0.5.5** Adaptive icon (Android)
- [ ] **M-0.5.6** Notification icon (Android transparent PNG)

### M-0.6 EAS Build Setup
- [ ] **M-0.6.1** `eas init` — link Expo project
- [ ] **M-0.6.2** Configure `eas.json` (development, preview, production profiles)
- [ ] **M-0.6.3** First development build (iOS Simulator)
- [ ] **M-0.6.4** First development build (Android Emulator)
- [ ] **M-0.6.5** Setup EAS Update channels

### M-0.7 CI/CD
- [ ] **M-0.7.1** GitHub Actions: lint + type-check on PR
- [ ] **M-0.7.2** EAS Build trigger on main branch
- [ ] **M-0.7.3** Automated OTA update for JS-only changes
- [ ] **M-0.7.4** Sentry source maps upload

### M-0.8 App Architecture
- [ ] **M-0.8.1** Folder structure: `app/`, `components/`, `hooks/`, `services/`, `stores/`
- [ ] **M-0.8.2** Theme provider (light/dark)
- [ ] **M-0.8.3** API client with interceptors (auth header, error handling)
- [ ] **M-0.8.4** TanStack Query provider
- [ ] **M-0.8.5** Toast/snackbar provider
- [ ] **M-0.8.6** Error boundary

---

## Sprint M-1 — Auth & Onboarding (Week 2)

### M-1.1 Splash & Walkthrough
- [ ] **M-1.1.1** Splash screen (animated logo)
- [ ] **M-1.1.2** First-launch walkthrough (3-4 slides)
- [ ] **M-1.1.3** Skip + Get Started buttons
- [ ] **M-1.1.4** Walkthrough state in AsyncStorage (don't show twice)

### M-1.2 Authentication Screens
- [ ] **M-1.2.1** Login screen (email/phone tabs)
- [ ] **M-1.2.2** Register screen with referral code
- [ ] **M-1.2.3** OTP entry screen (6-digit auto-paste support)
- [ ] **M-1.2.4** Forgot password flow
- [ ] **M-1.2.5** Email verification screen
- [ ] **M-1.2.6** Apple Sign-In (iOS — required by Apple)
- [ ] **M-1.2.7** Google Sign-In
- [ ] **M-1.2.8** Facebook Login (optional)

### M-1.3 Token Management
- [ ] **M-1.3.1** Secure token storage (expo-secure-store)
- [ ] **M-1.3.2** Auto-refresh on 401
- [ ] **M-1.3.3** Logout (clear tokens + cache)
- [ ] **M-1.3.4** Auth state context

### M-1.4 Profile Setup
- [ ] **M-1.4.1** Profile completion form (DOB, gender, photo)
- [ ] **M-1.4.2** Avatar upload (expo-image-picker)
- [ ] **M-1.4.3** Preferred branch selector
- [ ] **M-1.4.4** T&C + Privacy Policy acceptance modal
- [ ] **M-1.4.5** Marketing consent toggle (PDPA)

### M-1.5 Auth Guard
- [ ] **M-1.5.1** Protected routes (redirect if not authenticated)
- [ ] **M-1.5.2** Onboarding required check
- [ ] **M-1.5.3** Email verified check
- [ ] **M-1.5.4** Phone verified check

---

## Sprint M-2 — Home & Menu Browse (Week 3)

### M-2.1 Home Tab
- [ ] **M-2.1.1** Tab navigator (Home, Order, Loyalty, Profile)
- [ ] **M-2.1.2** Banner carousel (auto-scroll, deep links)
- [ ] **M-2.1.3** Featured products horizontal list
- [ ] **M-2.1.4** Category quick access grid
- [ ] **M-2.1.5** Order Again section
- [ ] **M-2.1.6** Loyalty preview card (points + tier badge)
- [ ] **M-2.1.7** Active vouchers shortcut
- [ ] **M-2.1.8** Pull-to-refresh
- [ ] **M-2.1.9** Skeleton loading states

### M-2.2 Branch Selection
- [ ] **M-2.2.1** Request location permission
- [ ] **M-2.2.2** Auto-detect nearest branch
- [ ] **M-2.2.3** Branch list screen with map view
- [ ] **M-2.2.4** Branch detail (hours, contact, busy status)
- [ ] **M-2.2.5** Branch switcher in header
- [ ] **M-2.2.6** Operating hours validation

### M-2.3 Menu Browse
- [ ] **M-2.3.1** Category grid screen
- [ ] **M-2.3.2** Category detail with product grid
- [ ] **M-2.3.3** Product detail screen with image gallery
- [ ] **M-2.3.4** Modifier selection bottom sheet
- [ ] **M-2.3.5** Add to cart with haptic feedback
- [ ] **M-2.3.6** Search screen with filters
- [ ] **M-2.3.7** Recent searches
- [ ] **M-2.3.8** Out-of-stock indicator
- [ ] **M-2.3.9** Favorites tab in profile

### M-2.4 Offline Support
- [ ] **M-2.4.1** Cache menu data (TanStack Query persist)
- [ ] **M-2.4.2** Offline indicator banner
- [ ] **M-2.4.3** Graceful degradation for offline browse

---

## Sprint M-3 — Cart, Checkout, Payment (Week 4)

### M-3.1 Cart
- [ ] **M-3.1.1** Cart screen with line items
- [ ] **M-3.1.2** Edit/remove items (swipe to delete)
- [ ] **M-3.1.3** Voucher input field
- [ ] **M-3.1.4** Loyalty redemption slider
- [ ] **M-3.1.5** Order notes textarea
- [ ] **M-3.1.6** Pickup time picker (ASAP / scheduled)
- [ ] **M-3.1.7** Order type selector (Pickup / Dine-in)
- [ ] **M-3.1.8** Cart totals breakdown

### M-3.2 Dine-in Flow
- [ ] **M-3.2.1** Table QR scan (expo-camera)
- [ ] **M-3.2.2** Table number entry fallback
- [ ] **M-3.2.3** Dine-in cart context

### M-3.3 Checkout
- [ ] **M-3.3.1** Checkout screen with totals
- [ ] **M-3.3.2** Saved payment methods list
- [ ] **M-3.3.3** Payment method selector
- [ ] **M-3.3.4** Tax + discount breakdown
- [ ] **M-3.3.5** Place order button (disabled if invalid)

### M-3.4 Payment Integration
- [ ] **M-3.4.1** Billplz redirect via WebView (in-app browser)
- [ ] **M-3.4.2** DuitNow QR display + status polling
- [ ] **M-3.4.3** eWallet deep link (GrabPay, TnG, Boost, ShopeePay)
- [ ] **M-3.4.4** Apple Pay (iOS, optional Phase 3.5)
- [ ] **M-3.4.5** Google Pay (Android, optional Phase 3.5)
- [ ] **M-3.4.6** Payment success/failure screens
- [ ] **M-3.4.7** Retry failed payment

### M-3.5 Order Confirmation
- [ ] **M-3.5.1** Confirmation screen with order number QR
- [ ] **M-3.5.2** Estimated ready time
- [ ] **M-3.5.3** Track order CTA
- [ ] **M-3.5.4** Receipt email triggered

---

## Sprint M-4 — Order Tracking & Push (Week 5)

### M-4.1 Real-time Order Tracking
- [ ] **M-4.1.1** Order detail screen (live status)
- [ ] **M-4.1.2** WebSocket subscription via Reverb (laravel-echo + native)
- [ ] **M-4.1.3** Status timeline UI
- [ ] **M-4.1.4** Pickup QR code display
- [ ] **M-4.1.5** Cancel order button (within window)
- [ ] **M-4.1.6** Order history screen with filters
- [ ] **M-4.1.7** Order detail from history
- [ ] **M-4.1.8** Reorder button

### M-4.2 Push Notifications Setup
- [ ] **M-4.2.1** Configure Firebase project (Android)
- [ ] **M-4.2.2** Configure APNS certificates (iOS)
- [ ] **M-4.2.3** Request notification permission (with rationale)
- [ ] **M-4.2.4** Register device token to backend
- [ ] **M-4.2.5** Handle notification tap (deep link to order)
- [ ] **M-4.2.6** Foreground notification banner
- [ ] **M-4.2.7** Notification channels (Android: orders, promo, reminders)

### M-4.3 Push Triggers
- [ ] **M-4.3.1** Order accepted push
- [ ] **M-4.3.2** Order preparing push
- [ ] **M-4.3.3** Order ready for pickup push
- [ ] **M-4.3.4** Promotional broadcast push
- [ ] **M-4.3.5** Voucher expiry reminder push
- [ ] **M-4.3.6** Birthday greeting push
- [ ] **M-4.3.7** Tier upgrade celebration push

### M-4.4 In-App Notification Center
- [ ] **M-4.4.1** Notification list screen
- [ ] **M-4.4.2** Mark as read
- [ ] **M-4.4.3** Clear all
- [ ] **M-4.4.4** Notification preferences (toggles in profile)

---

## Sprint M-5 — Loyalty, Vouchers, Membership, Profile (Week 6)

### M-5.1 Loyalty Tab
- [ ] **M-5.1.1** Points balance card
- [ ] **M-5.1.2** Tier badge + progress bar
- [ ] **M-5.1.3** Earn rate explainer
- [ ] **M-5.1.4** Redeem catalog (free items)
- [ ] **M-5.1.5** Points history list
- [ ] **M-5.1.6** Birthday banner

### M-5.2 Voucher Wallet
- [ ] **M-5.2.1** Voucher list (Active / Used / Expired tabs)
- [ ] **M-5.2.2** Voucher detail with T&C
- [ ] **M-5.2.3** Generate QR for in-store redemption
- [ ] **M-5.2.4** Redeem code entry

### M-5.3 Membership
- [ ] **M-5.3.1** Tier benefits screen
- [ ] **M-5.3.2** Tier requirements visual
- [ ] **M-5.3.3** Spending progress to next tier
- [ ] **M-5.3.4** Tier history

### M-5.4 Referral
- [ ] **M-5.4.1** Referral code display
- [ ] **M-5.4.2** Share button (native share sheet)
- [ ] **M-5.4.3** Referral history & rewards earned
- [ ] **M-5.4.4** Referral T&C

### M-5.5 Profile & Settings
- [ ] **M-5.5.1** Profile screen (edit name, phone, email, DOB, photo)
- [ ] **M-5.5.2** Change password
- [ ] **M-5.5.3** Saved addresses
- [ ] **M-5.5.4** Saved payment methods
- [ ] **M-5.5.5** Favorite items
- [ ] **M-5.5.6** Notification preferences
- [ ] **M-5.5.7** Language toggle (EN/BM)
- [ ] **M-5.5.8** Theme toggle (light/dark/system)
- [ ] **M-5.5.9** About + version info
- [ ] **M-5.5.10** Help & support
- [ ] **M-5.5.11** Logout
- [ ] **M-5.5.12** Delete account (PDPA right to erasure)

### M-5.6 Support & Feedback
- [ ] **M-5.6.1** FAQ screen (CMS-driven)
- [ ] **M-5.6.2** Contact support (email / WhatsApp deep link)
- [ ] **M-5.6.3** Order rating after pickup (1-5 stars + comment)
- [ ] **M-5.6.4** Product review
- [ ] **M-5.6.5** Report issue with photo upload

---

## Sprint M-6 — Native Features (Week 7)

### M-6.1 Biometric Login
- [ ] **M-6.1.1** Face ID / Touch ID prompt (iOS)
- [ ] **M-6.1.2** Fingerprint prompt (Android)
- [ ] **M-6.1.3** Toggle in settings (enable/disable)
- [ ] **M-6.1.4** Fallback to PIN/password
- [ ] **M-6.1.5** Re-auth prompt for sensitive actions (refund, payment change)

### M-6.2 Apple Wallet Pass (iOS)
- [ ] **M-6.2.1** Server-side pass generator (passkit-generator)
- [ ] **M-6.2.2** Pass design (logo, tier color, member number, QR)
- [ ] **M-6.2.3** Add to Apple Wallet button
- [ ] **M-6.2.4** Pass auto-update on tier change (push notification to wallet)

### M-6.3 Google Wallet Pass (Android)
- [ ] **M-6.3.1** Google Wallet API integration
- [ ] **M-6.3.2** Loyalty pass class definition
- [ ] **M-6.3.3** Add to Google Wallet button
- [ ] **M-6.3.4** Pass auto-update

### M-6.4 Deep Links & Universal Links
- [ ] **M-6.4.1** Configure Associated Domains (iOS)
- [ ] **M-6.4.2** Configure App Links (Android)
- [ ] **M-6.4.3** Deep link routes: `/product/:id`, `/promo/:id`, `/voucher/:code`, `/order/:id`
- [ ] **M-6.4.4** Branch.io / Firebase Dynamic Links (optional)
- [ ] **M-6.4.5** Test deep links from email/SMS/push

### M-6.5 Camera & QR
- [ ] **M-6.5.1** QR scanner for table dine-in
- [ ] **M-6.5.2** QR scanner for voucher redemption (admin/staff side)
- [ ] **M-6.5.3** Membership card QR display

### M-6.6 Force Update Mechanism
- [ ] **M-6.6.1** App version check on launch
- [ ] **M-6.6.2** Soft update prompt (recommend)
- [ ] **M-6.6.3** Hard update block (force, link to store)
- [ ] **M-6.6.4** Backend endpoint returns minimum supported version

### M-6.7 App Rating Prompt
- [ ] **M-6.7.1** `expo-store-review` integration
- [ ] **M-6.7.2** Trigger after positive interactions (5th order, 4+ star rating)
- [ ] **M-6.7.3** Throttle (don't ask too often)

### M-6.8 Performance Optimizations
- [ ] **M-6.8.1** Hermes engine enabled
- [ ] **M-6.8.2** Image caching (expo-image)
- [ ] **M-6.8.3** List virtualization (FlashList for long lists)
- [ ] **M-6.8.4** Bundle splitting
- [ ] **M-6.8.5** Reduce app size (asset optimization)

---

## Sprint M-7 — QA & App Store Submission (Week 8)

### M-7.1 Quality Assurance
- [ ] **M-7.1.1** Manual test plan execution (full app flows)
- [ ] **M-7.1.2** iOS device test matrix (iPhone SE, 13, 15 Pro Max, iPad)
- [ ] **M-7.1.3** Android device test matrix (low-end, mid, flagship)
- [ ] **M-7.1.4** iOS version test (iOS 15+)
- [ ] **M-7.1.5** Android version test (Android 10+)
- [ ] **M-7.1.6** Network conditions test (3G, offline, flaky)
- [ ] **M-7.1.7** Accessibility test (VoiceOver, TalkBack)
- [ ] **M-7.1.8** Memory leak audit (Flipper / Hermes inspector)
- [ ] **M-7.1.9** Battery drain test
- [ ] **M-7.1.10** Crash-free rate target > 99.5% (Sentry)

### M-7.2 Beta Testing
- [ ] **M-7.2.1** TestFlight build (iOS internal)
- [ ] **M-7.2.2** TestFlight external testers (up to 100)
- [ ] **M-7.2.3** Google Play Internal Testing track
- [ ] **M-7.2.4** Google Play Closed Testing (10-100 testers)
- [ ] **M-7.2.5** Bug triage + hotfix
- [ ] **M-7.2.6** UX feedback collection

### M-7.3 App Store (iOS) Assets
- [ ] **M-7.3.1** App icon final (no transparency, 1024x1024)
- [ ] **M-7.3.2** Screenshots: 6.7" (iPhone 15 Pro Max), 6.5", 5.5", iPad
- [ ] **M-7.3.3** App preview video (optional, 30s)
- [ ] **M-7.3.4** App name, subtitle, description, keywords
- [ ] **M-7.3.5** Promotional text
- [ ] **M-7.3.6** Privacy policy URL
- [ ] **M-7.3.7** Support URL
- [ ] **M-7.3.8** Marketing URL
- [ ] **M-7.3.9** Age rating questionnaire
- [ ] **M-7.3.10** App Privacy disclosure (data collection)
- [ ] **M-7.3.11** Demo account credentials for review
- [ ] **M-7.3.12** Export Compliance (encryption usage)

### M-7.4 Google Play Assets
- [ ] **M-7.4.1** App icon (512x512)
- [ ] **M-7.4.2** Feature graphic (1024x500)
- [ ] **M-7.4.3** Screenshots: phone (min 2), 7" tablet, 10" tablet
- [ ] **M-7.4.4** Promo video (optional)
- [ ] **M-7.4.5** Short description (80 chars)
- [ ] **M-7.4.6** Full description (4000 chars)
- [ ] **M-7.4.7** Privacy policy URL
- [ ] **M-7.4.8** Content rating questionnaire (IARC)
- [ ] **M-7.4.9** Data safety form
- [ ] **M-7.4.10** Target audience declaration

### M-7.5 Submission
- [ ] **M-7.5.1** EAS Submit to App Store
- [ ] **M-7.5.2** EAS Submit to Google Play
- [ ] **M-7.5.3** Respond to review feedback (iOS rejections common)
- [ ] **M-7.5.4** Resubmit if rejected
- [ ] **M-7.5.5** Phased release (Google Play) / Phased Release (App Store)

---

## Sprint M-8 — Launch & Post-Launch Monitoring (Post-Week 8)

### M-8.1 Production Launch
- [ ] **M-8.1.1** Approval received from both stores
- [ ] **M-8.1.2** Coordinate launch date with marketing
- [ ] **M-8.1.3** Soft launch (gradual rollout 1% → 10% → 50% → 100%)
- [ ] **M-8.1.4** Press release / social media announcement
- [ ] **M-8.1.5** In-app announcement banner

### M-8.2 Monitoring & Analytics
- [ ] **M-8.2.1** Sentry crash monitoring active
- [ ] **M-8.2.2** Mixpanel funnel tracking (signup → first order)
- [ ] **M-8.2.3** App Store Connect analytics review
- [ ] **M-8.2.4** Google Play Console vitals review
- [ ] **M-8.2.5** Daily DAU/MAU tracking
- [ ] **M-8.2.6** App Store rating monitoring + responses

### M-8.3 Hotfix & OTA Update Pipeline
- [ ] **M-8.3.1** EAS Update for JS-only fixes (no store review)
- [ ] **M-8.3.2** Native fix → EAS Build → resubmit
- [ ] **M-8.3.3** Rollback procedure documented
- [ ] **M-8.3.4** Incident response runbook

---

## Cross-Sprint Tasks (Ongoing)

- [ ] **M-X.1** Weekly device testing checkpoint
- [ ] **M-X.2** Update `session-memory.md` after each work session
- [ ] **M-X.3** Sync shared package changes from web team
- [ ] **M-X.4** Run review-protocol after each significant feature
- [ ] **M-X.5** Run security-protocol weekly
- [ ] **M-X.6** Track Apple/Google policy changes

---

## Pending Decisions (Block Sprint M-0 Start)

| ID | Decision | Status |
|---|---|---|
| M-DEC-1 | Monorepo or separate repo? | [ ] |
| M-DEC-2 | UI framework: Tamagui or NativeWind? | [ ] |
| M-DEC-3 | Apple Developer account holder (company / individual) | [ ] |
| M-DEC-4 | Google Play Console account holder | [ ] |
| M-DEC-5 | EAS plan (free vs Production $99/mo) | [ ] |
| M-DEC-6 | Bundle ID + package name (e.g., com.starcoffee.app) | [ ] |
| M-DEC-7 | Push notification provider (FCM only or also APNS direct) | [ ] |
| M-DEC-8 | Apple Pay enabled? | [ ] |
| M-DEC-9 | Google Pay enabled? | [ ] |
| M-DEC-10 | Wallet pass design approval | [ ] |

---

## Code Reuse from Phase 1 Web

The following from `Planning-Web.md` is directly reusable in mobile:

| Web Module | Mobile Reuse |
|---|---|
| API client (Axios + interceptors) | 100% reuse |
| TypeScript types (User, Order, Product, etc.) | 100% reuse |
| Zod validation schemas | 100% reuse |
| TanStack Query hooks (useOrders, useMenu, etc.) | 95% reuse |
| Zustand stores (cart, auth) | 90% reuse |
| Business logic utilities (price calc, SST) | 100% reuse |
| i18n translations | 100% reuse |
| Form validation rules | 100% reuse |

**Estimated total reuse: 60-70%** of Phase 1 web logic.

---

## Out of Scope (Phase 3 Mobile)

- Tablet POS app (covered by web PWA in Phase 1)
- Apple Watch / Wear OS companion
- Widgets (iOS Home Screen / Android)
- App Clips (iOS) / Instant Apps (Android)
- AR menu visualization
- In-app chat with support (use WhatsApp deep link instead)

---

## Linkage to Web Phase

**Mobile cannot start until:**
- Backend API stable (Phase 1 Sprint W-4 completed)
- Auth + Order endpoints documented & tested
- Webhook + WebSocket events verified
- Push notification endpoints ready

**Mobile shares with Web:**
- Same backend API (no separate mobile API)
- Same database
- Same admin portal manages both
- Same payment gateway integration
- Same loyalty/voucher engines

---

**Next Action:** Wait for Phase 1 Web to reach Sprint W-4. Resolve `M-DEC-1` to `M-DEC-6` in parallel during Phase 1.
