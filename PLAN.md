# NetBill — ISP Platform Plan

Universal ISP management platform: hotspot, PPPoE, and static-IP subscribers under one billing and provisioning core. MikroTik-first, direct RouterOS API (no RADIUS).

---

## 1. Audit findings (why it isn't best-in-class today)

### P0 — Revenue loss / security (fix before any paying customer)

- **M-Pesa callback is unauthenticated and unverified.** `mpesaCallback` marks transactions complete on `CheckoutRequestID` + `ResultCode` alone; the `CheckoutRequestID` is returned to the API caller, so a forged success callback = free internet. A second unverified copy exists in `PaymentService::handleMpesaCallback`.
- **Hotspot API trusts client-supplied identity.** Router resolved by request-body `platformID`/`host`; device identity by client-supplied `mac`/`ip`. All spoofable.
  - `activate-free`: one free package per MAC → unlimited free internet via MAC rotation.
  - `lookup-code`: returns live hotspot credentials for the latest completed transaction matching a phone number/receipt — session theft.
  - `verify-voucher` / `stkpush` / `payment-status`: no meaningful throttling → voucher brute-force and STK-push spam.
- **Secrets in git**: `.env.bak` tracked with a real `APP_KEY`. Rotate key, purge history.
- **Plaintext credentials**: `Router.password`, `Setting.mpesa_*`, `smtp_password`, `sms_api_key` stored unencrypted.

### P1 — Broken / dead code

- Paystack half-integrated: no callback route, no signature verification.
- Stripe/Cashier dead paths in `PaymentController` (`store()` has no validation/authz; `process()` charges cards inside staff routes).
- `User::due_amount()` always returns 0 (identical queries subtracted).
- `Staff.role` collected but never enforced — every staff login can do everything.
- Default seeded admin password is `password`.
- Duplicate MikroTik clients: dead `app/Classes/RouterOS.php` + abandoned `evilfreelancer/routeros-api-php`.
- CORS `allowed_origins: *` on `api/*`.

### P2 — Scale / reliability

- `QUEUE_CONNECTION=sync`: STK push, SMS, router calls run inside HTTP requests.
- Scheduler lacks `withoutOverlapping()`/`onOneServer()`; `sessions:sync` every minute reconnects to every router (5 retries × 3s delay each).
- `MpesaService` hits DB in constructor and logs config per request.
- Multi-tenancy half-built: `BelongsToCompanyScope` no-ops when unauthenticated; `User` opts out; `Setting::loadSettings()` depends on `Auth::check()`.
- phpunit.xml requires a real MySQL DB (`wingufi_billing_test`); ~14 feature tests, none covering payment/hotspot security paths.
- No PPPoE, static-IP, topology, or monitoring — the "universal ISP" gaps.

---

## 2. Target architecture

- **Access methods**: hotspot (voucher / STK / free tier), PPPoE (`/ppp/secret` + `/ppp/active`), static IP (DHCP leases / address-lists). All enforced via direct MikroTik API — **no RADIUS** (keeps logic short, avoids timeouts; revisit only at carrier scale behind a `NetworkDevice` interface).
- **Unified subscriber**: one `users`/`subscribers` record with `connection_type` (`hotspot` | `pppoe` | `static`) and many `connections`. One session table for all access types so expiry/renewal/billing logic is shared.
- **Tenancy**: see §5 — recommended model is **one database per ISP** (self-hosted/managed instance), with `company_id` retained only for internal org units if needed.
- **Payments**: single verified callback pipeline per gateway; M-Pesa primary, Paystack optional (finish or remove), wallet ledger with row-locked DB transactions.
- **Jobs**: Redis queue for STK/SMS/sync/notifications; scheduler with `withoutOverlapping()->onOneServer()`.

---

## 3. Roadmap

### Phase 0 — Stop the bleeding (before any paying customer)

1. Rotate `APP_KEY`; delete `.env.bak` from git + history; gitignore `.env*`.
2. M-Pesa callback: Safaricom IP allowlist at web server + `stkpushquery` cross-check before completing; delete the duplicate unverified path in `PaymentService`.
3. Per-router API auth on `/api/hotspot/*`: Sanctum token or HMAC-signed requests (timestamp + nonce) embedded in captive portal pages; map token → router, drop `platformID`/IP resolution.
4. Identity fix: STK push returns a one-time poll token required by `payment-status`; voucher redeem issues a one-time token; stop trusting client MAC for entitlement decisions; strict per-endpoint throttles + lockout.
5. `encrypted` casts on `Router.password`, `Setting.mpesa_*`, `smtp_password`, `sms_api_key`.
6. Remove dead code: Stripe/Cashier paths, `due_amount()`, `app/Classes/RouterOS.php`; finish Paystack (route + `x-paystack-signature` verify) or drop it.
7. Staff RBAC: enforce `Staff.role` via middleware/policies; 2FA for staff.

### Phase 1 — Harden the platform

8. Redis queue + queue workers; move STK/SMS/session-sync/expiry into jobs.
9. Scheduler hardening: `withoutOverlapping()`, `onOneServer()`, cached router connections.
10. MikroTik transport: API-SSL (8729) with cert verification; maintained client behind `RouterOsClientInterface`; per-router least-privilege API users.
11. Deployment: `.env.production` (`APP_DEBUG=false`), queue worker + cron as services, DB + `storage/` backups, health endpoint, artifact cleanup (`netbill.zip`).

### Phase 2a — Universal subscriber core

12. Subscriber unification: `connection_type` on subscribers; one `sessions` table (`connection_type` column) shared by hotspot/PPPoE/static.
13. PPPoE provisioning: `/ppp/secret` CRUD, ppp profile ↔ package rate limits, `/ppp/active` sync into session pipeline, expiry disconnect (`/ppp/active/remove`), renewal reconnect.
14. Static IP management: DHCP static leases + firewall address-lists, IPAM-lite (free/assigned/reserved), plan mapping per IP.
15. Provisioning drift detection: desired-state vs router-state diff → auto-heal or alert (replace fragile 15-min blind sync).

### Phase 2b — Network operations

16. Topology: core → distribution → AP/tower → subscriber hierarchy.
17. Monitoring: SNMP interface traffic, per-device up/down history, health dashboard, alerts (email/SMS/webhook) on router/tower down or flapping.
18. Blast-radius ticketing: tickets linked to affected subscribers/devices; auto-tickets on device-down.

### Phase 2c — Reseller / landlord network

Resellers are delegated accounts *inside* one ISP's installation (not platform tenants — consistent with DB-per-ISP). A landlord buys internet wholesale and retails to tenants; an ISP reseller operates hotspots/PPPoE under their own brand.

**Ownership modes:**

- **Reseller-owned router**: reseller's MikroTik registered with its own API credentials; payments settle to the reseller's own M-Pesa till/paybill (per-reseller `mpesa_*` credential overrides — `MpesaService` already prefers stored settings, extend to reseller-first resolution).
- **ISP-hosted router**: ISP router designated reseller-enabled via `router_reseller` pivot (with commission %); payments settle to the ISP till and revenue is attributed per reseller.

**Schema:**

- `resellers`: business name, contact, phone, status, `brand_name`/logo (per-router portal white-label via `{Company}` replacement), optional own M-Pesa creds.
- `staff.reseller_id` + `role='reseller'`: resellers log into the same panel; `staff.role`/new `reseller.scope` middleware restricts all queries to their `reseller_id`.
- `routers.reseller_id` (null = ISP-owned) + `router_reseller` pivot for shared ISP routers.
- `packages.reseller_id` + `wholesale_price`: retail price is reseller-set; wholesale is platform-billed.
- `users.reseller_id` (subscriber ownership) scoping customers, vouchers, sessions, reports.

**Revenue attribution (hosted mode):** encode reseller in STK `AccountReference` (e.g. `HSP{tenant}R{resellerId}`) — callback already persists it; settlement report = gross per reseller minus commission. v2: commission accrues to reseller wallet.

**Reseller capabilities:** own packages & prices, voucher generation, scoped subscriber list, session visibility on their routers, own revenue reports, portal branding on their routers. **Not:** other resellers' data, ISP settings, router-level config beyond package/profile sync.

**Edge cases:** voucher cross-router package matching must respect reseller boundaries; free-package abuse limits apply per reseller; session sync must not leak co-tenant resellers' sessions on shared routers; reseller-owned routers still need per-router `hotspot_api_token` + least-privilege RouterOS API user.

### Phase 3 — Commercial & customer experience

19. Customer portal parity for all connection types: STK self-pay, usage, plan change, invoices, tickets with SLA.
20. Billing correctness: wallet mutations only via ledger in DB transactions + row locks; idempotent payment→provision pipeline; M-Pesa reconciliation report; real refunds.
21. Promotions: voucher campaigns, referrals, flash sales, abuse-proof free trials.
22. Multi-gateway payments done right: M-Pesa (primary), Paystack (finished or removed), bank-transfer reconciliation.
23. API-first: versioned Sanctum API + scopes, payment/session webhooks; optional integrations (accounting export, external monitoring).
24. Owner-grade reporting: MRR/ARR, churn, ARPU, collection rate, revenue per router, plan mix.

### Phase 4 — Engineering maturity

25. Tests for money paths: sqlite unit tests; feature tests for forged-callback rejection, voucher brute-force lockout, session idempotency, wallet atomicity, PPPoE provision/expire.
26. Refactor god classes: split `MikroTikService` (2.4k lines) → HotspotProvisioner / PppoeProvisioner / SessionSyncer / Diagnostics / WalledGarden; Form Requests + API Resources.
27. Observability: structured logging (remove per-request config dumps), Sentry, queue monitoring, per-router uptime checks.
28. Docs: real README, runbooks (router offline, payment stuck, callback missing), staging environment.

---

## 4. Explicit non-goals / deferred

- **RADIUS / FreeRADIUS**: rejected — direct RouterOS API is simpler and sufficient. Revisit only if a customer requires 802.1X or carrier-scale NAS count; keep behind `NetworkDevice` interface.
- **TR-069 smart CPE**: deferred unless fiber/CPE management becomes a target market.
- **Non-MikroTik vendors**: interface only until a second vendor is a real customer requirement.

---

## 5. Tenancy decision: database per ISP

**Recommendation: one database per ISP instance** (the Splynx-style model), not a shared multi-tenant DB.

| | DB per ISP | Shared DB + `company_id` |
|---|---|---|
| Isolation | Strong — a bug/leak affects one ISP | Weak — one scoping bug leaks all ISPs |
| Ops | Migrations × N deployments; per-ISP backups/restores are trivial and safe | One migration, one backup; but blast radius is everyone |
| Selling model | Self-hosted or managed VPS per ISP — ISPs trust this, own their data | SaaS — faster onboarding, central billing of ISPs |
| Code requirement | Simple — no tenant scoping needed at all | Must fix `BelongsToCompanyScope`/`Auth::check()` properly everywhere |
| Reporting | Per-ISP only (fine — each ISP sees own data) | Cross-ISP analytics possible (only needed if *you* resell) |

**Decision**: if NetBill is sold/deployed per-ISP (your current WAMP/Laravel deploy model suggests this), DB-per-ISP is correct and *simpler* — it deletes the entire class of tenant-isolation bugs that exist today. Keep `company_id` only if a single ISP needs internal branches/departments.

If you later offer a hosted SaaS tier, add shared-DB tenancy as a separate deployment mode — don't design the core around it now.

Either way: tenant identity must come from **deployment context or router token**, never from `Auth::check()` global scopes.
