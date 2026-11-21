# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Two sides carry equal weight; when a design trade-off conflicts, neither automatically wins.

- **Pelanggan (customers):** people visiting a UMKM (barbershop, salon, clinic, café, workshop) who want a queue number without standing in line. They can browse stores and services without an account, but taking a ticket requires signing in with a customer account; they then watch their position and come back when called.
- **Owner UMKM:** runs one or more branches; configures services, counters, staff, hours, and reads reports.
- **Operator loket:** staff at a counter calling, serving, skipping, and adding walk-in customers through the day.
- **Admin:** platform administrator approving businesses, managing categories and users. Internal, low volume.

## Product Purpose

AntreIn lets small Indonesian businesses run one fair, transparent queue for both online and walk-in customers. Success means customers spend less time waiting on site, know where they stand, and merchants serve people in a predictable order without extra hardware.

## Positioning

Online and walk-in tickets merge into a single FIFO queue per service, tracked live by both sides. Customers need no app download (web link or QR code), and merchants need no dedicated hardware beyond a phone, laptop, or optional TV display.

## Operating Context

- Customers reach a store through the customer portal, a search/category browse, or a QR code at the store; the ticket is tracked at a tokenized URL.
- Merchants work from a dashboard during service hours; a public TV display shows the current number and announces calls.
- Real-time updates come through Laravel Reverb (WebSocket), with HTTP polling as fallback.
- Notifications go out via email and browser web push.
- Seed and demo data are set in Bali (timezone Asia/Makassar / WITA).
- This is an academic project built and evaluated with User Centered Design: questionnaires gathered critique from the prototype stage through the final design (8 respondents so far). The UI was designed in Figma first.

## Capabilities and Constraints

- Stack: Laravel 13, Blade components, Tailwind CSS v4, Alpine.js (via Livewire), Vite, MySQL, Chart.js.
- Roles: customer, merchant (owner/operator per branch, RBAC), admin.
- Features: multi-branch organizations, multi-counter assignment, services with daily quota and optional photo, business hours and open/pause/closed status, waiting-time estimation from history, customer reviews, CSV/PDF reports, PII anonymization.
- Browsing stores (`/pelanggan`, `/pelanggan/cari`, `/q/{slug}`) and tracking a ticket by token are public; taking, cancelling and reviewing a ticket, plus "Antrean Saya", history and profile, require a signed-in customer account.
- Dev-only tools (role switcher, quick login, `/preview/*` testbeds) render only when `APP_ENV` is `local` or `testing`.
- **Language: all UI copy is Bahasa Indonesia only. No English words mixed into labels, headings, or feature names.**
- Not yet built: order notes, takeaway/dine-in choice.

## Brand Commitments

- Name: **AntreIn** (logo component `x-brand-logo`, assets in `public/images/figma`).
- Tagline in use: "Antrean Lebih Teratur, Pelayanan Lebih Lancar."
- Footer credit: "Powered by AntreIn — Platform Antrean Digital UMKM".

## Evidence on Hand

- Figma design file for the customer portal (key `BJC7dsBY2j49Ui568UOqvz`).
- UCD questionnaire responses from 8 respondents (not stored in this repo).
- Demo businesses and services from database seeders; store photos under `public/images`.
- No real customer testimonials, usage metrics, partner logos, or pricing exist. Do not fabricate them.

## Product Principles

1. **Fairness is visible.** Every screen that shows a queue makes the order and the customer's place in it obvious.
2. **Low friction to join.** A customer can get a number in a few taps with no app install; the one-time sign-in is the only gate.
3. **Glanceable status.** Waiting customers and busy operators read the state (who is called, how long, what next) at a glance.
4. **Both sides, one truth.** Customer and merchant views show the same live queue; nothing is promised to one side that the other cannot see.
5. **Validated by users.** Design changes should be traceable to user feedback from the UCD evaluation, not to taste alone.

## Accessibility & Inclusion

- Single language: Bahasa Indonesia.
- Responsive across phone and desktop browsers; the customer portal is mobile-first.
