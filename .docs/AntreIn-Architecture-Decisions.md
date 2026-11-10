# AntreIn — Architecture Decision Records (ADR) & Product Scope Locks

> **Document Status:** Milestone 1 Approved Architecture Baseline  
> **Date:** 9 November 2026  
> **Version:** 2.0 (Approved Baseline)  
> **Target Platform:** Monolithic Laravel Responsive Web Application  
> **Authority References:** `AntreIn-PRD.md` v1.0, `AntreIn-design.md` v1.0, `AntreIn — Spesifikasi Fitur.md` v1.0, `AntreIn-Tasklist.md` v1.0

---

## 1. Executive Summary

This document serves as the final, approved Architecture Decision Record (ADR) and Technical Blueprint for the AntreIn platform. All 15 Open Questions (OQ-01 through OQ-15) have received official stakeholder approval to establish the baseline for Milestone 1.

Key validated architecture and scope locks:
1. **Database Referential Integrity & Tenant Scoping:** Enforced `ON DELETE RESTRICT` on historical queue tickets, composite foreign keys `(daily_queue_id, business_id)` and `(service_id, business_id)` guaranteeing tenant isolation at the MySQL storage engine level, and soft deletes on businesses and services.
2. **Race-Safe Daily Queue Initialization:** Atomic `firstOrCreate` with duplicate key exception recovery, wrapped in `DB::transaction()` with `SELECT ... FOR UPDATE` row locking on `daily_queues.current_number`, backed by `UNIQUE KEY uq_business_date (business_id, queue_date)`.
3. **Structured Event-History Model:** Added `event_type` enum (`status_changed`, `ticket_recalled`, `ticket_skipped`, `ticket_cancelled`, `note_added`) to `queue_status_histories` ensuring every recall event is independently auditable without fabricating status changes.
4. **Three-Tier Lifecycle Interlocking & Emergency Action:** Decoupled `users.is_active`, `businesses.is_suspended`, and `businesses.status`. Normal owner account deactivation is blocked if active tickets exist; emergency deactivation by Admin cancels active tickets with logged justification; suspended businesses preserve in-flight queue completion; manual walk-in tickets are permitted under `Jeda` (with operator confirmation) but blocked under `Tutup`.
5. **Double-Submit Prevention & Rate Limiting:** Form submissions pass UUID `idempotency_key` backed by `UNIQUE KEY uq_ticket_idempotency (business_id, idempotency_key)`, accompanied by 30 requests per 15 minutes per IP (`throttle:30,15`), session concurrency caps (max 3 active tickets), and same-service duplicate locks.
6. **Data Retention & 30-Day PII Anonymization:** Perpetual retention of transactional queue metrics; automated 30-day PII anonymization for customer names, masking for phone numbers, and scrubbing/nullification of operator notes.
7. **Dynamic Per-Business Timezone:** Defaulting to `Asia/Makassar` (WITA, UTC+8) for the initial Bali pilot deployment.

---

## 2. Complete Open Questions Matrix & Approval Checklist (OQ-01 to OQ-15)

| ID | Topic & Question | Document Reference | Final Recommended Decision | Technical Implications | Required Stakeholder Approval | Governance Status |
|---|---|---|---|---|---|:---:|
| **OQ-01** | **Merchant Multiplicity**<br>*Apakah satu akun pemilik hanya mengelola satu UMKM?* | `AntreIn-PRD.md` §18 (OQ-01); `Spesifikasi Fitur` §8.1; `AntreIn-Tasklist.md` G0-07 | **1 User = 1 Business for MVP.**<br>Each owner account is strictly 1:1 with a single business entity. | Enforce `UNIQUE KEY(user_id)` on `businesses`. Simplifies tenant scoping middleware (`EnsureUserOwnsBusiness`) without schema friction when 1:N multi-business is added in P1/P2. | Confirm 1:1 constraint for MVP. | **APPROVED** |
| **OQ-02** | **Timezone & Daily Reset**<br>*Bagaimana definisi hari antrean dan zona waktu?* | `AntreIn-PRD.md` §18 (OQ-02); `AntreIn-Tasklist.md` G0-08 | **Per-UMKM Timezone (Configurable Default).**<br>Store timezone per business (`businesses.timezone`). Default resolved via `.env` (`APP_DEFAULT_BUSINESS_TIMEZONE=Asia/Makassar` for Bali pilot). | Queue dates, daily number resets at `00:00:00`, reports, and operating hours dynamically evaluate using `Carbon::now($business->timezone)`. Never use a hardcoded global timezone. | Confirm per-business timezone with `Asia/Makassar` default for Bali pilot. | **APPROVED** |
| **OQ-03** | **Customer Self-Cancellation**<br>*Apakah pembatalan oleh pelanggan masuk MVP?* | `AntreIn-PRD.md` §18 (OQ-03), §7.7 (FR-CUS-05); `Spesifikasi Fitur` §5.7; `AntreIn-Tasklist.md` G0-09 | **Excluded from MVP (Deferred to P1).**<br>Only merchant operators can cancel tickets in MVP. | Eliminates public state mutation attack vectors, CSRF risks on public tracking, and quota churn complexity in the first release. | Confirm customer cancellation is deferred to P1. | **APPROVED** |
| **OQ-04** | **Walk-in Ticket Tracking**<br>*Apakah antrean walk-in mendapat tautan tiket publik?* | `AntreIn-PRD.md` §18 (OQ-04); `Spesifikasi Fitur` §4.5.I; `AntreIn-Tasklist.md` G0-10 | **Universal Token Generation for All Tickets.**<br>Both `ONLINE` and `WALK_IN` tickets receive a secure 40-character `access_token`. | Operator console displays the ticket number and provides an optional QR / tracking URL for walk-in customers who wish to monitor on mobile. | Confirm universal access token generation for walk-ins. | **APPROVED** |
| **OQ-05** | **Ticket Number Prefix**<br>*Apakah prefix tiket dikonfigurasi pemilik atau ditentukan sistem?* | `AntreIn-PRD.md` §18 (OQ-05); `Spesifikasi Fitur` §7.1; `AntreIn-Tasklist.md` G0-11 | **System Default Prefix per UMKM for MVP.**<br>System provisions standard prefix `A-` followed by a 3-digit zero-padded sequence (e.g. `A-001`). | Avoids entry errors, collisions, and complex regex validation. Dynamic prefix customization is deferred to P1. | Confirm system default prefix `A-001` for MVP. | **APPROVED** |
| **OQ-06** | **Merchant Account Activation**<br>*Apakah UMKM baru langsung aktif atau perlu persetujuan Admin?* | `AntreIn-PRD.md` §18 (OQ-06); `AntreIn-Tasklist.md` G0-12 | **Immediate Self-Serve Activation.**<br>Merchants activate immediately upon completing minimum onboarding. Admin retains power to suspend at any time. | Removes friction during onboarding and enables self-service pilot testing. Platform Admin moderates via `businesses.is_suspended`. | Confirm immediate self-serve merchant activation. | **APPROVED** |
| **OQ-07** | **Merchant Administrative Suspension**<br>*Apa dampak penonaktifan UMKM oleh Admin terhadap tiket aktif?* | `AntreIn-PRD.md` §18 (OQ-07), §7.2 (FR-ADM-04); `AntreIn-Tasklist.md` G0-13 | **Halt New Tickets; Complete In-Flight.**<br>Suspension (`is_suspended = true`) blocks all new tickets (`ONLINE` and `WALK_IN`). Active tickets can be served to completion. | Public page `/q/{slug}` shows a polite suspension notice. Merchant console allows calling/completing existing in-flight tickets so on-premise customers are not abandoned. | Confirm in-flight queue completion under merchant suspension. | **APPROVED** |
| **OQ-08** | **Unattended Call Handling**<br>*Bagaimana penanganan tiket Dipanggil yang tidak ditanggapi?* | `AntreIn-PRD.md` §18 (OQ-08); `Spesifikasi Fitur` §4.5.H; `AntreIn-Tasklist.md` G0-14 | **Manual Operator Action (No Auto-Timeout).**<br>No automated background timeout. Operator manually triggers "Panggil Ulang" or "Lewati". | Prevents premature dismissal in unpredictable physical service environments. Each recall event updates `recalled_at` and appends to `queue_status_histories`. | Confirm manual operator handling without cron auto-timeouts. | **APPROVED** |
| **OQ-09** | **Service Quota Allocation**<br>*Apakah kuota layanan dihitung berdasarkan tiket dibuat atau tiket aktif?* | `AntreIn-PRD.md` §18 (OQ-09), §8.7; `Spesifikasi Fitur` §7.5; `AntreIn-Tasklist.md` G0-15 | **Time-Consuming Tickets Count Against Quota.**<br>`Menunggu`, `Dipanggil`, `Sedang dilayani`, `Selesai`, and `Dilewati` all consume quota. Only `Dibatalkan` releases quota. | Accurately models merchant operational capacity: skipped customers occupied an allocated service slot. Quota formula: $\text{Used} = \text{Count}(\text{status} \neq \text{'dibatalkan'})$. | Confirm that `Dilewati` tickets consume quota, while `Dibatalkan` releases quota. | **APPROVED** |
| **OQ-10** | **Customer Multi-Ticket & Rate Limiting**<br>*Apakah pelanggan dapat mengambil lebih dari satu tiket pada hari yang sama?* | `AntreIn-PRD.md` §18 (OQ-10); `AntreIn-Tasklist.md` G0-16 | **Allow Cross-Service; Block Same-Service Active Duplicate; Public Wi-Fi Tolerant.**<br>Customer can take tickets for different services. Duplicate active tickets for the same service within the same session are blocked. Submissions are protected by client-side button disable, form idempotency tokens (`uq_ticket_idempotency`), session limit (max 3 active tickets), and IP rate limit (30 requests per 15 min). | Active ticket definition: status in (`Menunggu`, `Dipanggil`, `Sedang dilayani`). If duplicate attempted, redirect to existing `/ticket/{token}`. 30 req/15 min IP rate limit protects shared venue Wi-Fi from locking out distinct patrons. | Confirm multi-ticket rules, idempotency tokens, and Wi-Fi-tolerant rate limits. | **APPROVED** |
| **OQ-11** | **Customer Phone & Privacy**<br>*Apakah nomor telepon opsional hanya disimpan atau digunakan untuk notifikasi?* | `AntreIn-PRD.md` §18 (OQ-11), §7.7; `Spesifikasi Fitur` §5.4; `AntreIn-Tasklist.md` G0-17 | **Optional Field; Stored for Operator Desk Identification Only.**<br>Phone number is optional. No automated SMS/WhatsApp in MVP. Masked in platform Admin monitoring. | Complies with UU PDP privacy principles. Avoids external messaging infrastructure dependencies during MVP while providing on-premise operator contact. | Confirm phone is optional, used solely on-desk, and masked in admin views. | **APPROVED** |
| **OQ-12** | **Customer Polling Cadence**<br>*Interval polling pelanggan berapa detik?* | `AntreIn-PRD.md` §18 (OQ-12), §7.7 (FR-CUS-04); `Spesifikasi Fitur` §5.6; `AntreIn-Tasklist.md` G0-18 | **Adaptive Polling Cadence.**<br>5 seconds for `Menunggu` and `Dipanggil`; 15 seconds for `Sedang dilayani`; immediately stop polling on terminal status. | Reduces server load by over 60% compared to flat polling while guaranteeing sub-5s response when a customer's turn is called. | Confirm adaptive polling cadence (5s active / 15s in-service / 0s terminal). | **APPROVED** |
| **OQ-13** | **Token Lifespan & Retention**<br>*Berapa lama tiket dan token dapat diakses?* | `AntreIn-PRD.md` §18 (OQ-13), §8.9; `Spesifikasi Fitur` §7.7; `AntreIn-Tasklist.md` G0-19 | **Active While Non-Terminal; 24 Hours Post-Terminal; Indefinite in Merchant DB.**<br>Non-terminal tickets trackable continuously. Terminal tickets viewable for 24 hours after completion/skip/cancel. Expired tokens show zero PII. DB records are never deleted. | After 24 hours post-terminal timestamp, public `/ticket/{token}` displays a generic expired notice without customer name/phone. Authenticated merchant dashboard retains full historical records indefinitely. | Confirm 24-hour post-terminal token access rule and permanent DB retention. | **APPROVED** |
| **OQ-14** | **Admin Monitoring Privacy**<br>*Apakah Admin boleh melihat nomor telepon pelanggan saat monitoring?* | `AntreIn-PRD.md` §18 (OQ-14), §7.2 (FR-ADM-06); `AntreIn-Tasklist.md` G0-20 | **Mask Customer Phone Numbers in Admin View.**<br>Format: `0812****7890`. Only the owning merchant operator sees raw phone numbers. | Enforces strict PII minimization across multi-tenant platform oversight. Admin monitors operational throughput, not customer identity. | Confirm customer phone number masking in Admin monitoring console. | **APPROVED** |
| **OQ-15** | **Peak-Hour Demand Analytics**<br>*Apakah laporan MVP perlu ringkasan jam/hari ramai?* | `AntreIn-PRD.md` §18 (OQ-15), §7.5 (FR-REP-03); `Spesifikasi Fitur` §4.7.C; `AntreIn-Tasklist.md` G0-21 | **Customer Demand Based on `created_at` with 50-Ticket Threshold.**<br>Hourly buckets (1-hour bins) based on ticket creation timestamp (`created_at`) across all non-cancelled tickets. Requires $\ge 50$ tickets in selected period. | Uses ticket creation time to represent true customer arrival demand (not completion time). If sample $< 50$, renders an informational notice: *"Data antrean belum mencukupi untuk analisis jam ramai (minimal 50 tiket)."* | Confirm peak-hour demand analysis based on `created_at` with 50-ticket minimum sample. | **APPROVED** |

---

## 3. Detailed Architecture Decisions (ADR-01 to ADR-12)

### ADR-01: Dynamic Timezone Configuration (Bali Pilot Baseline)
- **Decision:** Business operating hours, daily queue boundaries, and analytics evaluate dynamically using each merchant's local timezone.
- **Specification:**
  - Column: `timezone VARCHAR(50) NOT NULL DEFAULT 'Asia/Makassar'` in `businesses`.
  - Default resolved from `config('app.business_default_timezone', env('APP_DEFAULT_BUSINESS_TIMEZONE', 'Asia/Makassar'))`.
  - Options: `Asia/Makassar` (WITA, UTC+8, Bali pilot baseline), `Asia/Jakarta` (WIB, UTC+7), `Asia/Jayapura` (WIT, UTC+9).
  - All daily resets, hours validations, and reports call `$business->timezone`.

### ADR-02: True Responsive Web Customer Experience
- **Decision:** The customer interface (`/q/{slug}` and `/ticket/{token}`) must be implemented as a fully responsive website, **NOT** constrained within an artificial phone mockup or narrow mobile container on desktop.
- **Specification:**
  - **Desktop ($\ge 1024\text{px}$):** Max container width `max-w-5xl` or `max-w-6xl`. Two-column structured layout on `/q/{slug}` (Left: UMKM profile, operating hours, operational status badge; Right: available service cards with pricing, duration, and queue trigger).
  - **Tablet ($768\text{px} - 1023\text{px}$):** Fluid adaptive grid with optimized touch targets.
  - **Mobile ($< 768\text{px}$):** Clean single-column layout, full-width CTA buttons, minimum touch target $44\times 44\text{px}$, sticky header.
  - **Ticket Tracking (`/ticket/{token}`):** Centered card container (`max-w-xl` on desktop), featuring large tabular queue numbers (`Display 32px/700`), clear visual status pill, and position indicator.

### ADR-03: HTTP Routing Conventions for Queue Registration (PRG Pattern)
- **Decision:** Separate the presentation of the queue form from the action that records the ticket to maintain standard REST semantics and prevent form resubmission issues on refresh.
- **Specification:**
  - `GET /q/{slug}`: Landing view for merchant information, operational status, and service catalog.
  - `GET /q/{slug}/take-queue?service={id}`: Dedicated queue registration form displaying service summary, customer name/label input, and optional phone input.
  - `POST /q/{slug}/take-queue`: Backend submission endpoint implementing Post/Redirect/Get (PRG) pattern. Validates quota, creates ticket atomically, and redirects directly to `GET /ticket/{token}` with a flash confirmation message.

### ADR-04: Queue Cancellation Matrix & Terminal Lifecycle
- **Decision:** Explicitly delineate cancellation authority and allowable state transitions:
  - **Customer Self-Cancellation:** Excluded from MVP (Deferred to P1).
  - **Merchant Operator Cancellation:** Allowed **only** when ticket status is `Menunggu`.
  - **Transition Path:** `Menunggu → Dibatalkan`. Once a ticket is `Dipanggil` or `Sedang dilayani`, it cannot be cancelled; if the customer fails to appear, the operator must transition the ticket to `Dilewati`.
  - **Terminal States:** `Selesai`, `Dilewati`, and `Dibatalkan` are strict terminal states. No further status changes can occur from these states.

### ADR-05: Service Quota Calculation & Skipped (`Dilewati`) Tickets Policy
- **Decision:** Daily service quota measures allocated merchant capacity.
- **Specification:**
  - When a customer takes a ticket, capacity is reserved.
  - Tickets in `Menunggu`, `Dipanggil`, and `Sedang dilayani` actively hold quota slots.
  - Completed tickets (`Selesai`) consumed the slot.
  - Skipped tickets (`Dilewati`) consumed the merchant's operational slot (the operator called them and waited; the slot time expired). Therefore, **`Dilewati` tickets CONSUME daily quota**.
  - Only tickets with status `Dibatalkan` release their quota back to the pool.
  - Formula:
    $$\text{Remaining Quota} = \text{Daily Limit} - \text{Count}(\text{status} \neq \text{'dibatalkan'})$$

### ADR-06: Universal Token Generation for All Ticket Sources
- **Decision:** Prevent sequential enumeration vulnerabilities and provide parity between customer channels.
- **Specification:**
  - Both `ONLINE` and `WALK_IN` tickets must have an auto-generated, unguessable, 40-character alphanumeric `access_token` (`Str::random(40)`).
  - Database schema enforces a `UNIQUE` index on `queue_tickets.access_token`.
  - Public lookups are strictly query-scoped by `where('access_token', $token)`. The visible ticket number (e.g. `A-005`) is never used as an endpoint lookup key.

### ADR-07: Three-Tier Status Model, Deactivation Interlock & Emergency Procedure
- **Decision:** Clearly decouple authentication, platform administrative standing, and routine daily merchant operational states, with explicit interlocking rules and emergency procedures.
- **Specification:**
  1. `users.is_active` (`BOOLEAN`): Controls system-wide authentication. If `false`, login is rejected (`401/403`) and active sessions are revoked.
     - **Normal Deactivation Interlock:** An owner account cannot be deactivated while active queue tickets (`menunggu`, `dipanggil`, `sedang_dilayani`) exist for today's session. Any deactivation attempt via Admin or API throws a validation exception: *"Akun tidak dapat dinonaktifkan karena masih terdapat antrean aktif. Selesaikan atau batalkan antrean terlebih dahulu."*
     - **Emergency Handling Procedure:** In cases of urgent legal, security, or fraudulent breach where an Administrator must immediately terminate access, an explicit `emergency_force = true` parameter can be passed. Under this procedure:
       - All active tickets (`menunggu`, `dipanggil`, `sedang_dilayani`) for the merchant are automatically cancelled with `status = 'dibatalkan'`, `cancellation_reason = 'Penutupan darurat oleh administrator'`, and `cancelled_at = NOW()`.
       - The merchant status is set to `businesses.status = 'tutup'`.
       - The user account is deactivated: `users.is_active = false`.
       - An immutable audit trail entry is recorded in `activity_logs` with severity `EMERGENCY`.
  2. `businesses.is_suspended` (`BOOLEAN`): Platform governance action by Admin AntreIn. If `true`:
     - Public page (`/q/{slug}`) renders a polite suspension notice and disables all queue registration.
     - Taking new tickets (`ONLINE` and `WALK_IN`) is blocked (`403 Forbidden`).
     - **In-flight queue completion is preserved:** The merchant can still log in and view their console to call, serve, and complete existing active tickets (`Menunggu`, `Dipanggil`, `Sedang dilayani`) so on-premise customers are not abandoned.
  3. `businesses.status` (`ENUM('buka', 'tutup', 'jeda')`): Merchant's self-managed operational toggle:
     - `'buka'`: Business is open and accepting new online queue tickets.
     - `'jeda'`: Business is temporarily pausing new queue intake. Online ticket intake is blocked. Operator **is permitted** to add a manual walk-in ticket at their discretion (e.g. urgent on-premise arrival) with a UI confirmation prompt. Active tickets can be served.
     - `'tutup'`: Business is closed for the day or after hours. Online ticket intake is blocked. Manual walk-in ticket intake is **prohibited** unless the operator switches status to `Buka`. Active tickets can be served to completion.

### ADR-08: Admin Monitoring Privacy & PII Masking
- **Decision:** Comply with personal data protection principles (UU PDP) in the multi-tenant admin oversight screen.
- **Specification:**
  - The Admin queue monitoring console (`/admin/queues`) displays customer name/label, service name, ticket number, status, and creation time.
  - If a phone number exists, it must be masked by the backend/Blade helper:
    `Str::mask($ticket->customer_phone, '*', 4, -3)` (e.g. `0812****7890`).
  - Raw phone numbers are visible only to the verified merchant operator owning that ticket.

### ADR-09: Single Source of Truth for Daily Sequences & Concurrency Protocol
- **Decision:** Eliminate the redundant `queue_sequences` table; use `daily_queues.current_number` under row-level locking.
- **Specification:**
  - `daily_queues.current_number` (`INT UNSIGNED NOT NULL DEFAULT 0`) is the single authoritative counter for daily queue numbering.
  - Initial creation of `daily_queues` around midnight or opening handles concurrency race conditions cleanly:
    ```php
    DB::transaction(function () use ($business, $todayDate) {
        try {
            $dailyQueue = DailyQueue::firstOrCreate(
                ['business_id' => $business->id, 'queue_date' => $todayDate],
                ['current_number' => 0, 'status' => 'active']
            );
        } catch (\Illuminate\Database\QueryException $e) {
            $dailyQueue = DailyQueue::where('business_id', $business->id)
                ->where('queue_date', $todayDate)
                ->firstOrFail();
        }

        $dailyQueue = DailyQueue::where('id', $dailyQueue->id)->lockForUpdate()->first();
        $dailyQueue->increment('current_number');
        $nextSequence = $dailyQueue->current_number;
        $ticketNumber = sprintf('A-%03d', $nextSequence);
        ...
    });
    ```
  - Physical collision prevention: `UNIQUE KEY uq_daily_ticket (daily_queue_id, ticket_number)`.

### ADR-10: Structured Queue Event-History Model
- **Decision:** Add `event_type` to `queue_status_histories` so repeated recalls and skips are audited without fabricating status changes.
- **Specification:**
  - Schema:
    - `event_type ENUM('status_changed', 'ticket_recalled', 'ticket_skipped', 'ticket_cancelled', 'note_added') NOT NULL DEFAULT 'status_changed'`
    - `from_status VARCHAR(30) NULL`
    - `to_status VARCHAR(30) NOT NULL`
    - `note VARCHAR(255) NULL`
  - In `queue_tickets`, `recalled_at` stores the timestamp of the *most recent* recall for O(1) read performance in Now Serving.
  - Every individual recall event writes an immutable entry into `queue_status_histories` (`event_type = 'ticket_recalled'`, `note = 'Panggilan ke-N'`).

### ADR-11: Database Referential Integrity & Cross-Tenant Prevention
- **Decision:** Prevent accidental deletion of historical records and enforce cross-tenant consistency at the database level.
- **Specification:**
  - Foreign keys from `queue_tickets` to `businesses`, `daily_queues`, and `services` use `ON DELETE RESTRICT` instead of `CASCADE`.
  - To prevent a ticket from accidentally referencing a service or daily queue belonging to another merchant:
    - `daily_queues` defines `UNIQUE KEY uq_dq_composite (id, business_id)`.
    - `services` defines `UNIQUE KEY uq_svc_composite (id, business_id)`.
    - `queue_tickets` enforces composite foreign keys:
      `FOREIGN KEY (daily_queue_id, business_id) REFERENCES daily_queues(id, business_id) ON DELETE RESTRICT`
      `FOREIGN KEY (service_id, business_id) REFERENCES services(id, business_id) ON DELETE RESTRICT`
  - Businesses and services implement `SoftDeletes` (`deleted_at TIMESTAMP NULL`).

### ADR-12: Rate-Limiting & Anti-Abuse Architecture (Idempotency Key Enforcement)
- **Decision:** Multi-tier rate limiting and database-enforced idempotency to prevent double-submissions, race conditions, and denial-of-service while accommodating shared public Wi-Fi networks.
- **Specification:**
  - **Idempotency Key Enforced at DB Level:** Client forms generate a UUID `idempotency_key` (via hidden field or header). This key is persisted in `queue_tickets.idempotency_key` with a database-level constraint `UNIQUE KEY uq_ticket_idempotency (business_id, idempotency_key)`. Double clicks or network retries with identical keys safely return the original ticket record without duplicate sequence allocation.
  - **Active Ticket Lock:** A customer session cannot hold more than one active ticket (`menunggu`, `dipanggil`, `sedang_dilayani`) for the same service.
  - **Session Concurrency Cap:** Max 3 active tickets across all services per customer session.
  - **IP Throttling (Public Wi-Fi Friendly):** 30 requests per 15 minutes per IP (`throttle:30,15`, configurable via `QUEUE_SUBMIT_IP_RATE_LIMIT=30` in `.env`).
  - **Behavior on 429:** Returns HTTP 429 Too Many Requests with `Retry-After` header and localized notice: *"Terlalu banyak permintaan pengambilan antrean dari koneksi internet Anda. Mohon tunggu beberapa saat sebelum mencoba lagi."*

### ADR-13: 30-Day Customer PII Anonymization Policy & Operator Notes Scrubbing
- **Decision:** Permanently retain operational transaction data for analytics while enforcing an automated 30-day anonymization window for all customer personal data, including operator desk notes.
- **Status:** Approved & Implemented (Pre-M18 Security Hardening)
- **Specification:**
  - **Operational Transaction Metrics (Permanent):** `ticket_number`, `sequence_number`, service reference, timestamps (`created_at`, `called_at`, `service_started_at`, `completed_at`, `skipped_at`, `cancelled_at`), and status are retained indefinitely for business reporting and platform analytics.
  - **Customer PII Fields (30-Day Window):** For all *terminal* tickets older than the retention window (`created_at < NOW() - INTERVAL N DAY`, where `N` is configurable via `QUEUE_PII_RETENTION_DAYS`, default 30), an automated scheduled task (`php artisan queues:anonymize-pii`) runs once per day via the Laravel scheduler (`Schedule::daily()` guarded by `withoutOverlapping()`):
    - `customer_phone`: Masked irreversibly to `0812****0000` (retains network prefix for telecom analytics, clears subscriber digits; values shorter than 5 characters are fully masked to `****`; NULL stays NULL).
    - `customer_name`: Replaced with anonymized placeholder `'Pelanggan #' || ticket_number` (e.g. `Pelanggan #A-012`).
    - `notes`: **Completely scrubbed / nullified** (`notes = NULL`), ensuring that free-form operator notes (e.g. vehicle license plates, doctor diagnosis comments, customer requests) do not violate UU PDP privacy rules after the operational window.
    - Public tracking tokens for terminal tickets expire 24 hours after completion/skip/cancellation, returning HTTP 404 or generic expired page.
- **Implementation Notes (Pre-M18 Security Hardening):**
  - Only terminal tickets (`selesai`, `dilewati`, `dibatalkan`) past the retention window are eligible — active tickets are never touched.
  - The command is idempotent (re-runs are no-ops on already-anonymized rows), processes tickets in chunks of 200 inside per-chunk database transactions, skips model events, and never writes customer PII to application logs.
  - A `--dry-run` flag reports the eligible ticket count without modifying the database.
  - The schedule requires an external scheduler runner in the deployment environment (a per-minute cron invoking `php artisan schedule:run`, or a long-running `php artisan schedule:work` process) — the registration alone does not execute the task.

---

## 4. Reconciled Database Schema Blueprint (Final)

```sql
-- 1. Users Table (Admin & Merchant Owners)
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'merchant') NOT NULL DEFAULT 'merchant',
    is_active BOOLEAN NOT NULL DEFAULT TRUE, -- ADR-07: Authentication access
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- 2. Business Categories (Platform Managed)
CREATE TABLE business_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description TEXT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- 3. Businesses (UMKM Profile & Governance)
CREATE TABLE businesses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE, -- ADR-01: 1 User = 1 Business for MVP
    category_id BIGINT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(180) NOT NULL UNIQUE,
    logo_path VARCHAR(255) NULL,
    description TEXT NULL,
    address TEXT NULL,
    phone VARCHAR(30) NULL,
    timezone VARCHAR(50) NOT NULL DEFAULT 'Asia/Makassar', -- ADR-01: Bali pilot default
    status ENUM('buka', 'tutup', 'jeda') NOT NULL DEFAULT 'tutup', -- ADR-07: Merchant daily status
    is_suspended BOOLEAN NOT NULL DEFAULT FALSE, -- ADR-07: Admin governance status
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL, -- ADR-11: Soft delete
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (category_id) REFERENCES business_categories(id) ON DELETE SET NULL
);

-- 4. Business Operating Hours (Weekly Schedule)
CREATE TABLE business_hours (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    day_of_week TINYINT UNSIGNED NOT NULL, -- 0 (Sunday) to 6 (Saturday)
    open_time TIME NULL,
    close_time TIME NULL,
    is_closed BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uq_business_day (business_id, day_of_week),
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
);

-- 5. Services (Catalog & Capacity Constraints)
CREATE TABLE services (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    duration_minutes INT UNSIGNED NOT NULL DEFAULT 15,
    price DECIMAL(12, 2) NULL DEFAULT 0.00,
    daily_quota INT UNSIGNED NULL, -- NULL = Unlimited
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL, -- ADR-11: Soft delete preserves history
    UNIQUE KEY uq_svc_composite (id, business_id), -- ADR-11: Composite foreign key target
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
);

-- 6. Daily Queues (Single Source of Truth for Daily Sequences)
CREATE TABLE daily_queues (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    queue_date DATE NOT NULL,
    current_number INT UNSIGNED NOT NULL DEFAULT 0, -- ADR-09: Single source of truth
    status ENUM('active', 'closed') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uq_business_date (business_id, queue_date),
    UNIQUE KEY uq_dq_composite (id, business_id), -- ADR-11: Composite foreign key target
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE RESTRICT -- ADR-11
);

-- 7. Queue Tickets (Reconciled with Complete Timestamps, Composite FKs & Universal Tokens)
CREATE TABLE queue_tickets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    daily_queue_id BIGINT UNSIGNED NOT NULL,
    service_id BIGINT UNSIGNED NOT NULL,
    ticket_number VARCHAR(20) NOT NULL, -- e.g. "A-001"
    sequence_number INT UNSIGNED NOT NULL, -- Integer for strict FIFO sorting
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(30) NULL, -- Optional (ADR-11)
    source ENUM('online', 'walk_in') NOT NULL DEFAULT 'online',
    status ENUM('menunggu', 'dipanggil', 'sedang_dilayani', 'selesai', 'dilewati', 'dibatalkan') NOT NULL DEFAULT 'menunggu',
    access_token VARCHAR(64) NOT NULL UNIQUE, -- ADR-06: Universal 40-char token
    idempotency_key VARCHAR(64) NULL, -- ADR-12: Client double-submit prevention
    called_at TIMESTAMP NULL,
    recalled_at TIMESTAMP NULL, -- ADR-10: Latest recall timestamp
    service_started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    skipped_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    cancellation_reason VARCHAR(255) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_business_status (business_id, status),
    INDEX idx_daily_sequence (daily_queue_id, sequence_number),
    UNIQUE KEY uq_ticket_idempotency (business_id, idempotency_key), -- Database-level idempotency
    UNIQUE KEY uq_daily_ticket (daily_queue_id, ticket_number), -- Strict collision prevention
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE RESTRICT, -- ADR-11
    FOREIGN KEY (daily_queue_id, business_id) REFERENCES daily_queues(id, business_id) ON DELETE RESTRICT, -- ADR-11
    FOREIGN KEY (service_id, business_id) REFERENCES services(id, business_id) ON DELETE RESTRICT -- ADR-11
);

-- 8. Queue Status Histories (Structured Event-History Model)
CREATE TABLE queue_status_histories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    queue_ticket_id BIGINT UNSIGNED NOT NULL,
    event_type ENUM('status_changed', 'ticket_recalled', 'ticket_skipped', 'ticket_cancelled', 'note_added') NOT NULL DEFAULT 'status_changed', -- ADR-10
    from_status VARCHAR(30) NULL,
    to_status VARCHAR(30) NOT NULL,
    actor_type ENUM('merchant', 'customer', 'system') NOT NULL DEFAULT 'merchant',
    actor_id BIGINT UNSIGNED NULL,
    note VARCHAR(255) NULL, -- Stores reason or "Panggilan ke-N" (ADR-10)
    created_at TIMESTAMP NULL,
    INDEX idx_history_ticket (queue_ticket_id),
    INDEX idx_history_event (event_type),
    FOREIGN KEY (queue_ticket_id) REFERENCES queue_tickets(id) ON DELETE RESTRICT -- ADR-11
);

-- 9. Activity Logs (Platform Audit Trail)
CREATE TABLE activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    business_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    properties JSON NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_business_log (business_id, created_at),
    INDEX idx_user_log (user_id, created_at)
);

-- 10. Business Memberships (ADR-13: Multi-Staff RBAC)
CREATE TABLE business_memberships (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE, -- Single business per user in M12
    role ENUM('owner', 'operator') NOT NULL DEFAULT 'operator',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    joined_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_business_role (business_id, role),
    INDEX idx_business_active (business_id, is_active),
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 11. Staff Invitations (ADR-13: Secure Tokenized Invitations)
CREATE TABLE staff_invitations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    invited_by_user_id BIGINT UNSIGNED NOT NULL,
    email VARCHAR(255) NOT NULL,
    role ENUM('operator') NOT NULL DEFAULT 'operator',
    token_hash VARCHAR(64) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    accepted_at TIMESTAMP NULL,
    revoked_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_invitation_business (business_id),
    INDEX idx_invitation_email (email),
    INDEX idx_invitation_hash (token_hash),
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by_user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 12. Business Counters (ADR-14: Multi-Loket & Queue Assignment)
CREATE TABLE business_counters (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(50) NOT NULL,
    code VARCHAR(10) NOT NULL,
    description VARCHAR(255) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uk_business_counter_code (business_id, code),
    INDEX idx_counter_business_active (business_id, is_active),
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
);

-- 13. Counter Operator Assignments (ADR-14: Pivot Table)
CREATE TABLE counter_operator_assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_counter_id BIGINT UNSIGNED NOT NULL,
    business_membership_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uk_counter_operator (business_counter_id, business_membership_id),
    INDEX idx_assignment_counter (business_counter_id),
    INDEX idx_assignment_membership (business_membership_id),
    FOREIGN KEY (business_counter_id) REFERENCES business_counters(id) ON DELETE CASCADE,
    FOREIGN KEY (business_membership_id) REFERENCES business_memberships(id) ON DELETE CASCADE
);

-- Note on queue_tickets: added counter_id nullable foreign key
-- ALTER TABLE queue_tickets ADD COLUMN counter_id BIGINT UNSIGNED NULL AFTER daily_queue_id;
-- ALTER TABLE queue_tickets ADD CONSTRAINT fk_queue_tickets_counter FOREIGN KEY (counter_id) REFERENCES business_counters(id) ON DELETE SET NULL;
-- ALTER TABLE queue_tickets ADD INDEX idx_tickets_business_counter_status (business_id, counter_id, status);

-- 14. Customer Reviews & Feedback (ADR-18: P1-09)
CREATE TABLE customer_reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    queue_ticket_id BIGINT UNSIGNED NOT NULL UNIQUE,
    service_id BIGINT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL, -- 1 to 5
    comment TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    FOREIGN KEY (queue_ticket_id) REFERENCES queue_tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    INDEX idx_reviews_business_created (business_id, created_at),
    INDEX idx_reviews_business_rating (business_id, rating),
    INDEX idx_reviews_service_rating (service_id, rating)
);
```

---

## ADR-13: Multi-Staff Architecture & Role-Based Access Control (RBAC)

### Status: Approved & Implemented (Milestone 12)

### Context:
MVP AntreIn linked one `User` directly to one `Business` via `businesses.user_id`. In real-world UMKM operations, an owner employs staff operators to call queue tickets, start services, and complete queues, while preserving administrative secrecy over revenue, service management, reports/exports, and business settings.

### Decision:
1. **Decouple Platform Role vs Business Role:**
   - Platform Level (`users.role`): Remains `admin` vs `merchant`.
   - Business Level (`business_memberships.role`): `owner` vs `operator`.
2. **Single Business Constraint (M12 Scope):**
   - In M12, `business_memberships.user_id` has a `UNIQUE` constraint, enforcing that a user account belongs to at most one business unit.
3. **Secure Staff Invitation Workflow:**
   - Pre-hashed single-use invitation tokens (`SHA-256`) with configurable expiration (72h default).
   - No emails are dispatched in M12; instant copyable links are generated for the business owner.
   - Self-service onboarding for invited staff: guests register directly from the invitation link; existing users verify password.
4. **Authorization Middleware & Operator Scope:**
   - Middleware `business.role:owner,operator` secures queue console and operations.
   - Middleware `business.role:owner` strictly gates administrative, financial/reporting, and staff management routes.
   - Ticket cancellation is strictly owner-only.
5. **Single Active Operator Rule & FIFO Preservation:**
   - Multi-staff does not imply multi-counter (P1-03 multi-loket is deferred).
   - Single active ticket rule remains enforced at the business level (`DailyQueue`).

---

## ADR-14: Multi-Counter Architecture & Queue Assignment (P1-03)

### Status: Approved & Implemented (Milestone 13)

### Context:
In Milestone 12, multiple staff members could log in to the queue console, but the business-level lock restricted the entire business to exactly one active ticket (`dipanggil` or `sedang_dilayani`) at any given time. Growing UMKMs (barbershops with multiple chairs, clinics with multiple consultation desks, retail repair counters) require parallel servicing across physical desks/counters while maintaining fairness and order for waiting customers.

### Decision:
1. **Single Global FIFO Queue with Counter-Level Active Concurrency:**
   - One unified daily sequence (`DailyQueue`) remains the single source of truth for queue sequencing. Online and walk-in tickets follow one strict sequential order (`sequence_number`).
   - Waiting tickets (`menunggu`) stay in the global FIFO without a counter (`counter_id = NULL`) until called.
   - The single active ticket constraint changes from **business-wide** to **counter-wide**: multiple counters can serve active tickets in parallel; a single counter can only have at most one active ticket.
2. **Counter Assignment & Operator Access Control:**
   - Business Owners can operate any active counter in their business.
   - Operators can only operate counters assigned to their active membership in `counter_operator_assignments`.
   - Owners manage counters at `/umkm/counters`: CRUD counters, toggle active status, and assign/unassign operator memberships.
   - Safeguards: A counter cannot be deactivated or deleted if it currently holds an active ticket, or if it is the sole active counter in the business.
3. **Pessimistic Concurrency Locking:**
   - Calling next (`QueueService::callNext`) locks `DailyQueue` via `lockForUpdate()` to serialize calls and prevent race conditions when two operators call next simultaneously.
   - The next waiting ticket in strict FIFO sequence is assigned to the calling counter atomically.
4. **Customer Tracking & Privacy Abstraction:**
   - Customer tracking payload displays human-friendly counter name (e.g., `Loket 1`, `Loket 2`) and code (`L-01`), never exposing internal database counter IDs.
   - Real-time customer tracking dynamically highlights the specific desk/counter the customer must proceed to when called.
5. **Seamless Backward Compatibility:**
   - Database migration backfills a default `Loket 1` (`L-01`) for all existing businesses and automatically assigns active memberships.
   - Model lifecycle hooks automatically provision `Loket 1` for new businesses and assign new operators to default active counters.

## ADR-15: Real-Time Broadcasting with Laravel Reverb & WebSocket (P1-04)

### Status: Approved & Implemented (Milestone 14)

### Context:
Prior to Milestone 14, queue status synchronization relied entirely on client-side HTTP polling (5s intervals during `menunggu`/`dipanggil`, 15s during `sedang_dilayani`). While functional and resilient, polling introduces network overhead, latency (up to 5–15 seconds before a customer realizes their number is called), and unnecessary server load. A first-party real-time push mechanism is needed to notify customers immediately when their ticket is called, recalled, served, or assigned to a specific counter, while also giving merchants an instantaneous dashboard experience.

### Decision:
1. **Laravel Reverb as Native First-Party WebSocket Server:**
   - Deployed `laravel/reverb` v1.12 with `pusher/pusher-php-server` and `laravel-echo` / `pusher-js`.
   - Native integration with Laravel's broadcasting architecture without requiring external third-party hosted dependencies (Pusher cloud) or separate Node.js services (socket.io).
2. **Transaction-Safe Event Broadcasting (`ShouldDispatchAfterCommit`):**
   - All queue broadcast events implement `Illuminate\Contracts\Events\ShouldDispatchAfterCommit` and `Illuminate\Contracts\Broadcasting\ShouldBroadcastNow`.
   - Events are only broadcast to WebSocket clients AFTER database transactions commit successfully. Rollbacks produce zero spurious broadcast signals.
3. **Four Dedicated Core Broadcasting Events:**
   - `QueueTicketCreated`: Dispatched when online or walk-in tickets are generated; broadcasts on private merchant channel `business.{businessId}`.
   - `QueueTicketStatusChanged`: Dispatched when ticket lifecycle transitions; broadcasts on merchant channel `business.{businessId}` and private customer channel `ticket.{tokenHash}`.
   - `QueueTicketCounterAssigned`: Dispatched when a ticket is assigned to a specific counter; broadcasts on `business.{businessId}`, `counter.{counterId}`, and `ticket.{tokenHash}`.
   - `CounterStatusChanged`: Dispatched when a counter active status or current serving ticket changes; broadcasts on `business.{businessId}` and `counter.{counterId}`.
4. **Channel Authorization & Privacy Architecture:**
   - **Merchant Channel (`private-business.{businessId}`):** Restricted via `routes/channels.php` to platform admins and active merchant members (owners and operators) belonging to the specified business.
   - **Counter Channel (`private-counter.{counterId}`):** Restricted to owners and operators assigned to operate that counter.
   - **Customer Channel (`private-ticket.{tokenHash}`):** Guest customers access tracking via a secure 40-character access token. The channel name uses `SHA-256(access_token)` to prevent exposing raw tokens over network inspection. Guest authorization is authenticated via a dedicated endpoint `POST /broadcasting/auth/ticket` with IP rate limiting (60 req/min/IP), token matching, ticket existence verification, and Pusher HMAC authorization signatures.
   - **Data Privacy & Sanitization:** Broadcast payloads contain zero PII: customer phone numbers, full email addresses, and database access tokens are completely excluded from payloads.
5. **Connection Resilience & Adaptive Polling Fallback:**
   - WebSockets act as the primary low-latency accelerator.
   - The existing adaptive HTTP polling mechanism remains fully intact as an automatic fallback when WebSocket connections are disconnected, blocked by strict enterprise proxies, or offline.
   - Background tab optimization: WebSocket listeners and timers throttle gracefully when browser tabs are hidden, and refresh instantaneously upon tab visibility.

## ADR-16: Email & Browser Notifications (P1-05)

### Status: Approved & Implemented (Milestone 15)

### Context:
While real-time WebSockets (Milestone 14) provide instant updates for users actively viewing the website, customers waiting in line frequently lock their mobile phones or switch to other applications. Similarly, merchant staff may not constantly look at the dashboard when operating a storefront. To maintain a modern, reliable customer experience without requiring native app installations, AntreIn requires multi-channel notification support:
1. **Background Web Push Notifications** (W3C Push API & Service Worker) for active device alerting when tabs are minimized or closed.
2. **Transactional Email Notifications** for durable receipts and background notifications when push is unavailable or ungranted.

### Decision:
1. **Additive Multi-Channel Hierarchy:**
   - WebSocket (M14) remains the primary instantaneous accelerator for foreground sessions.
   - Web Push Notifications (`public/sw.js`) provide visual and auditory notifications on desktop and mobile browsers even when the tab is closed or the screen is locked.
   - Transactional Email provides a permanent record and reliable fallback for consenting customers and merchant staff.
2. **Customer Consent & Data Privacy (PDPA / GDPR Alignment):**
   - Customer email intake is strictly optional and never mandatory.
   - Explicit affirmative consent is recorded via a dedicated checkbox (`notify_email = 1`), persisting both `customer_email` and `customer_email_consented_at`.
   - Customer email addresses are strictly shielded from public tracking views and API responses (never exposed in JSON payloads or public DOM).
3. **VAPID Specification & Secure Push Storage:**
   - Push notifications adhere to the W3C Push API and IETF RFC 8292 (VAPID) using ECDSA P-256 keypairs (`minishlink/web-push` v11.0).
   - Standardized CLI generator: `php artisan webpush:vapid`.
   - **VAPID Key Handling (Pre-M18 Security Hardening):** VAPID keys are read strictly from the environment (`VAPID_PUBLIC_KEY` / `VAPID_PRIVATE_KEY`) — no fallback key material exists in source control. Without configured keys, non-production environments degrade gracefully (push delivery is silently disabled while pages and email notifications continue to work), whereas production fails fast with an instructional error referencing `php artisan webpush:vapid` whenever a push send is attempted.
   - Client cryptographic secrets (`public_key` and `auth_token`) are encrypted at rest in `push_subscriptions` using Laravel's native `'encrypted'` model casts.
   - Subscriptions are indexed by SHA-256 `endpoint_hash` to support fast lookups and prevent duplicate registrations.
   - Dead subscriptions returning HTTP 404 (Not Found) or 410 (Gone) are automatically deleted by `WebPushService`.
   - Customer ticket push subscriptions are automatically purged when the ticket transitions into a terminal status (`selesai`, `dilewati`, `dibatalkan`).
4. **Idempotent Asynchronous Event Processing:**
   - Notification dispatch is decoupled from core HTTP transactions via `SendQueueNotificationListener` implementing `Illuminate\Contracts\Queue\ShouldQueue`.
   - The `queue_tickets.last_notified_status` column ensures strict idempotency: duplicate event firings or rapid status toggles cannot trigger duplicate customer emails or push notifications.
5. **Granular Merchant Staff Preferences & Tenant Isolation:**
   - Staf and owners control their alert channels independently via `user_notification_preferences` (`email_on_new_online_ticket`, `browser_on_new_online_ticket`).
   - The merchant notification dispatcher queries only verified, active staff members of the specific `business_id`, ensuring strict cross-tenant isolation.
   - In-store walk-in tickets do not dispatch merchant online notifications to prevent alert fatigue.

## ADR-17: Public Queue Display / TV Queue Monitor (P1-08)

### Status: Approved & Implemented (Milestone 16)

### Context:
In physical UMKM waiting rooms (e.g., clinics, salons, barbershops, payment counters), visitors and waiting customers frequently look up at a wall-mounted Smart TV, monitor, or shared counter screen. To eliminate physical overcrowding at counter desks and prevent missed calls, AntreIn requires a dedicated, full-screen public queue display view that dynamically reflects active queue calls in real-time without requiring merchant login or exposing private customer information.

### Decision:
1. **Cryptographic Display Credential Architecture:**
   - Display links are protected by high-entropy, 48-character cryptographically secure random tokens (`bin2hex(random_bytes(24))`).
   - Stored in hashed format (`display_token_hash = hash('sha256', $token)`) on `businesses` for fast, constant-time database lookups without leaking raw credentials in database dumps or query logs.
   - Owner-only lifecycle management (`business.role:owner`):
     - **Activation/Deactivation:** Instantly toggles display visibility.
     - **Token Rotation:** Generates a new credential and immediately invalidates the old link.
     - **Revocation:** Disables the display and clears the token hash completely.
   - Operator and Admin access restrictions: Operators cannot manage or revoke display settings; Admins follow standard read-only monitoring.
2. **Public Read-Only API & Zero-PII Policy:**
   - Endpoints: `GET /display/{token}` (HTML TV mode) and `GET /display/{token}/data` (JSON API).
   - Protected with HTTP headers: `Cache-Control: no-store, no-cache, must-revalidate, max-age=0` to ensure revoked links or cached queue numbers are never served from browser caches or intermediate proxies.
   - Strict Zero-PII Sanitization: The display payload completely excludes customer full names, phone numbers, email addresses, customer tracking access tokens, and internal user/database IDs. Only the ticket number (e.g. `A-001`), service name, counter name/code, status label, and call time are served.
3. **Multi-Counter Responsive Layout & TV Mode:**
   - Designed desktop-first and optimized for 1920×1080 (Full HD TV), 1600×900, 1366×768, and tablets.
   - High-contrast, anti-glare dark slate aesthetic (`bg-slate-950`) with prominent large typography (`text-6xl sm:text-7xl lg:text-8xl font-black font-mono`).
   - Adaptive CSS grid accommodates 1, 2, 3, or 4+ active counters simultaneously.
   - Clean state differentiation:
     - `DIPANGGIL`: Pulsing amber card with call icon.
     - `SEDANG_DILAYANI`: Solid emerald card.
     - Idle / Empty: Standby neutral card (`— Belum ada panggilan`).
   - Interactive Fullscreen API toggle allows operators to mount the display seamlessly on Smart TVs.
4. **Built-in Web Audio API Chime Synthesizer:**
   - Integrates a self-contained double-tone bell chime (523.25 Hz [C5] and 783.99 Hz [G5]) synthesized directly via standard browser `AudioContext`.
   - Requires zero external audio MP3 files, eliminating CDN latency, CORS errors, or missing asset failures.
   - Chimes automatically whenever a counter initiates a new call (`status === 'dipanggil'`). Includes a persistent Mute/Unmute control for staff convenience.
5. **Real-Time WebSocket Integration & Public Hash Channel:**
   - Dispatches `PublicQueueDisplayUpdated` event (`ShouldBroadcastNow`, `ShouldDispatchAfterCommit`) to public channel `display.{tokenHash}`.
   - Listens to ticket creation, status changes, counter assignments, and counter status transitions via `BroadcastToPublicDisplayListener`.
   - Payload contains zero PII (only event action and timestamp), prompting the display client to re-fetch the verified read-only endpoint.
   - Transaction Safety: Events are only broadcast after database transactions commit.
6. **Adaptive Polling Fallback & Resilience:**
   - Operates a 10-second background polling cycle as an automatic failover when WebSocket connections disconnect or drop.
   - Listens to `online` and `window.focus` events to immediately recover and synchronize state when network connectivity is restored.

---

## ADR-18: Customer Reviews & Feedback (P1-09)

### Status: Approved & Implemented (Milestone 17)

### Context:
In service and retail UMKM operations, gathering timely feedback immediately after customer service completion is vital to monitor operational service quality, track staff performance, and identify service bottlenecks. However, review intake must prevent malicious spamming, duplicate voting, unauthorized reviews of incomplete/cancelled queues, and cross-tenant leakage.

### Decision:
1. **Relational Data Model & Integrity:**
   - Dedicated table `customer_reviews` with foreign keys referencing `businesses`, `queue_tickets`, and `services`.
   - Uniqueness constraint: `queue_ticket_id` is marked `UNIQUE`, enforcing strictly at most one review per completed service ticket at the database layer.
   - Cascade deletion: Deleting a ticket or business cleanly removes its associated review records without orphaned data.
2. **Strict Lifecycle Enforcement & Access Security:**
   - Reviews can **ONLY** be submitted for queue tickets in terminal state `selesai` (`QueueTicket::STATUS_SELESAI`).
   - Attempted review submissions for tickets in progress (`menunggu`, `dipanggil`, `sedang_dilayani`) or aborted states (`dilewati`, `dibatalkan`) are strictly rejected with HTTP 422 Unprocessable Entity.
   - Expired tickets (>24 hours post-completion) are rejected with HTTP 410 Gone via `QueueService::isTokenExpired`.
   - Endpoint: `POST /q/track/{token}/review` resolves tickets via the cryptographically secure 40-character `access_token`, eliminating vulnerability to sequential ID guessing.
3. **Anti-XSS & Anti-Abuse Hardening:**
   - Input validation: `rating` required, integer, 1 to 5; `comment` nullable, string, max: 1000 characters.
   - Anti-XSS Sanitization: Raw comment input is cleaned with `strip_tags()` before persisting to prevent stored cross-site scripting attacks.
   - Rate limiting: Protected with `throttle:15,1` per client IP.
   - Full audit logging: Each review submission dispatches an `ActivityLog` entry (`customer_review_submitted`).
4. **Customer Experience on Live Tracking Page:**
   - Integrated into `customer.track`: Upon transitioning to `selesai` (either on load or via live WebSocket update), the review card appears automatically.
   - Interactive 5-star rating selector powered by Alpine.js with live visual feedback and character counter.
   - Idempotency & persistent display: If already reviewed, the form is replaced by a verified review badge displaying the submitted stars, comment, submission timestamp, and an appreciative acknowledgment.
5. **Merchant Review Analytics & Multi-Role Visibility:**
   - Dedicated merchant portal screen: `GET /umkm/reviews` (`merchant.reviews.index`).
   - Accessible to both Owner and Operator (`business.role:owner,operator`) to keep frontline staff aligned on customer sentiment.
   - Real-time KPI summary cards: Rata-rata Rating, Total Ulasan, Tiket Selesai, and Tingkat Respon Ulasan (review percentage).
   - Rating distribution histogram (percentage and counts for 5, 4, 3, 2, 1 stars).
   - Multi-parameter filtering: by star rating, service type (`service_id`), and search text across ticket numbers, customer names, or comment contents.
   - Strict tenant isolation: Query is constrained by `$business->customerReviews()`, preventing cross-merchant data leakage.

---

## ADR-19: Historical Waiting Time Estimation (P1-10)

### Status: Approved & Implemented (Milestone 18)

### Context:
The MVP computed the customer's estimated wait from the configured `duration_minutes` of the selected service multiplied by the number of people ahead. This ignores the business's actual operational pace (staff speed, real service durations), the number of counters serving in parallel, and produces a fabricated "0 minutes" for the next-in-line customer regardless of reality. Spesifikasi Fitur 7.6 mandates service duration as the estimation basis and explicitly forbids misleading estimates; PRD FR-CUS-04 requires a non-binding estimate on the tracking page; and Spesifikasi 11.2 (P1 backlog) requires the estimate to be derived from historical data when its quality suffices. Queue position must always take precedence over the estimate.

### Decision:
1. **Dedicated Read-Only Service:**
   - `App\Services\WaitingTimeEstimationService` (separate from the already-large `QueueService`) is strictly read-only: it never mutates tickets, queue order, counters, quota, or the FIFO engine. Actual waiting time is defined as ticket creation → first call (`created_at → called_at`, written exactly once by `QueueService::callNext`; recalls only update `recalled_at`), deliberately distinct from M9's waiting analytics (`created_at → service_started_at`).
2. **Historical Basis & Privacy:**
   - Samples are the measured actual service durations (`service_started_at → completed_at`) of the business's own terminal `selesai` tickets — an upgrade of Spesifikasi 7.6's "durasi layanan sebagai dasar perkiraan" from the configured value to measured history.
   - Tenant scoping by `business_id` (no cross-business mixing, MVP constraint: one global FIFO per UMKM per day), a 30-day window bounded in the business's local timezone then converted to the application timezone for query scoping (mirroring `MerchantReportService`), capped at the 200 most recent samples, with a non-negative duration validity filter.
   - Only timestamp columns are selected — anonymized tickets (ADR-13) remain valid samples because masking preserves timestamps, and no customer PII ever enters the computation, the response, or logs.
3. **Statistic — Median with Minimum Sample Threshold:**
   - The median (not the mean) is used for outlier resistance: a single abnormally slow service must not distort the estimate. The median must be strictly positive; a zero median cannot express a drain rate.
   - A minimum of 5 valid samples is required before any number is published; below that, a single outlier dominates the statistic and would mislead, which Spesifikasi 7.6 forbids.
4. **Multi-Counter Aware Formula:**
   - `minutes = ceil(effectiveAhead × medianServiceMinutes / activeCounterCount)` where `effectiveAhead = max(0, queuePosition − freeCounterCount)`.
   - Free-counter semantics mirror `QueueService::callNext`: a counter is busy while holding a `dipanggil`/`sedang_dilayani` ticket, and a legacy active ticket without a `counter_id` blocks every counter. Inactive counters never speed up the estimate.
5. **Honest Fallback — Never Zero Substitution:**
   - When data quality is insufficient the estimate is `null` plus an explicit machine-readable reason: `not_waiting` (ticket no longer in `menunggu`), `insufficient_history` (< 5 valid samples or median ≤ 0), or `no_active_counters` (business cannot call any ticket). No fabricated value is ever published.
6. **Presentation & Realtime Behavior:**
   - The estimate renders only inside the `menunggu` status callout on the existing tracking page (`customer/track.blade.php`) — automatically hidden on every other status — as "± X menit" with an explicit "bukan janji waktu pelayanan" disclaimer plus an explanation of its basis (historical service records and active counter count). The queue position remains the primary information.
   - Updates flow through the existing mechanisms only (Laravel Echo `ticket.{tokenHash}` listeners and the adaptive 5s/15s polling fallback) with `estimated_wait_minutes ?? null` / `estimated_wait_reason` handling; polling already stops on terminal statuses, so a stale estimate can never be shown on a terminal ticket.
7. **Integration & Security Posture:**
   - Integrated into `QueueTicketController::store()` and `track()` via constructor DI — no new routes, no schema changes, no new dependencies (basic statistics only). Access-token verification, rate limiting (`track` 60/min/IP, `take-queue` 30/15min/IP), and all M10/M16/Pre-M18 hardening remain untouched. JSON responses carry only the aggregate number and the reason — never other customers' historical data.

---

*Authored by Senior Full-Stack Software Engineer & Technical Architect — AntreIn Project*
