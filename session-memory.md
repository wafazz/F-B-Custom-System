# Star Coffee — Session Memory

**Project:** Star Coffee — Multi-branch F&B Platform (Coffee & Pastry)
**Phase:** 1 of 3 — Web App + PWA
**Started:** 2026-05-08
**Last Updated:** 2026-05-08

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
- [✔] **W-0.2** Laravel 12 bootstrap, git init, `.env` configured, first commit
- [✔] **W-0.3** All 10 core packages installed + Filament admin at `/admin`
- [✔] **W-0.4** Frontend: Inertia + React 19 + TS + Tailwind 4 + PWA + RHF/Zod/TanStack/Zustand. Build verified.

## Tasks In Progress
- [ ] **W-0.4.3** shadcn/ui CLI + base components
- [ ] **W-0.4.8** ESLint + Prettier

## Tasks Next
- W-0.5 Project structure & layouts
- W-0.6 Auth scaffolding (User table extension, login/register/OTP)
- W-0.7 CI/CD setup
- W-0.8 Deployment prep (blocked on W-DEC-1, W-DEC-2)

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
