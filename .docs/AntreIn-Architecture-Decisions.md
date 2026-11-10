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
- **Specification:**
  - **Operational Transaction Metrics (Permanent):** `ticket_number`, `sequence_number`, service reference, timestamps (`created_at`, `called_at`, `service_started_at`, `completed_at`, `skipped_at`, `cancelled_at`), and status are retained indefinitely for business reporting and platform analytics.
  - **Customer PII Fields (30-Day Window):** For all tickets older than 30 days (`created_at < NOW() - INTERVAL 30 DAY`), an automated scheduled task (`php artisan queues:anonymize-pii`) runs nightly at 02:00:
    - `customer_phone`: Masked irreversibly to `0812****0000` (retains network prefix for telecom analytics, clears subscriber digits).
    - `customer_name`: Replaced with anonymized placeholder `'Pelanggan #' || ticket_number` (e.g. `Pelanggan #A-012`).
    - `notes`: **Completely scrubbed / nullified** (`notes = NULL`), ensuring that free-form operator notes (e.g. vehicle license plates, doctor diagnosis comments, customer requests) do not violate UU PDP privacy rules after the operational window.
    - Public tracking tokens for terminal tickets expire 24 hours after completion/skip/cancellation, returning HTTP 404 or generic expired page.

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

*Authored by Senior Full-Stack Software Engineer & Technical Architect — AntreIn Project*
