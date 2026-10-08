# Rehab Health Events Portal: build plan

The new portal for the annual Rehabilitation Summit run by Rehab Health, starting with the 2027 edition. This is a fresh codebase. The previous portal (`../Rehab`) is reference material only: we read it to understand the workflows, and we copy nothing from it without reason.

## Decisions so far

| Topic | Decision |
|---|---|
| Scope | One summit a year. Accounts persist from year to year, and each year's registrations, abstracts, payments and attendance belong to that year's edition. |
| Codebase | Completely new. |
| Sign-in | Email and password only. No usernames and no SSO. |
| Look | Light mode only. The client's login mockup is the design reference, since there are no brand guidelines. |
| Brand | The Rehab Health logo, "2027 Events Portal", and the colours sampled from the logo (see `DESIGN_SYSTEM.md`). |
| Dates and venue | Pending. They are edited in the admin settings, not hard-coded. |
| Payments | Bank transfer and mobile money, confirmed inside the system by finance officers. No GePG. Automated mobile money gateways plug in later. |
| Mobile app | Staff-only scanner for badge check-in and session attendance. |
| CPD point rules | Fixed points per programme session, set by the committee. A badge scanned while a session runs earns its points, once. |
| Presentations | Oral only for now: no posters anywhere in the portal. `ABSTRACT_POSTERS=true` brings posters back (October 2026). |

## Stack

- **Laravel 13** on **PHP 8.4**. Locally, PHP 8.4 is a separate install (`php84`, `composer84`), and XAMPP keeps PHP 8.2 for the other projects. Production needs PHP 8.3 or newer.
- **Blade** with Blade components, **Tailwind CSS v4** and **Alpine.js**, built with Vite.
- **Laravel Fortify** for authentication (login, registration, email verification, password reset and rate limiting), with our own Blade screens.
- **spatie/laravel-permission** for roles.
- **dompdf** and FPDI for badges, letters, certificates and the abstract book. **chillerlan/php-qrcode** for badge QR codes.
- Database: SQLite for development and tests, MySQL in production. The queue uses the database driver.
- Font: **Plus Jakarta Sans**, self-hosted.

## Data model (core)

```
users                 one account per person, kept across years
editions              year, name, theme, dates, venue, deadlines, fees, status, is_current
registrations         user × edition: category, fee snapshot, status, badge token, checked_in_at
group_registrations   a leader registers and pays for several people
payments              payable = registration | group_registration | sponsorship
topics                per edition: name, code (e.g. HBR) and colour
abstracts             per edition: author, topic, type, title, body, status, conference code
abstract_authors      ordered co-authors
review_assignments    abstract × reviewer, double-blind
reviews               scores, recommendation, comments
sessions              per edition: hall, time, type, chair, rapporteur
session_items         abstracts scheduled into sessions
presentation_files    uploads per scheduled abstract
session_attendances   registration × session, recorded by the staff app
certificates          per registration: attendance or presenter certificate, with a serial number
invitation_letters    per registration
sponsors, sponsorships
announcements, email_logs, feedback
```

## Phases

Each phase ends with working, tested features. The order follows the summit calendar: registration and abstracts open first, then review, the programme, the event days, and the close-out.

## Status (October 2026): working demo

The portal is a complete, working demonstration with sample data (`DemoSeeder`). Built and tested end to end:

- Public site: home page and programme, both driven by the summit settings.
- Accounts: registration, sign-in, email verification, password reset and profile.
- Registration and payment: categories and fees, bank and mobile money instructions, proof upload, finance verification or rejection, emails, and badge and invitation letter PDFs.
- Abstracts: drafts, submission with a word limit, co-authors and presenter, withdrawal, and feedback after the decision.
- Review: double-blind assignment (authors and co-authors are excluded), scoring, recommendations, decisions, and conference codes.
- Staff: role-based navigation and dashboards, the finance queue, the scientific committee, reviewer workload, the registration desk check-in, the admin overview, participants, users and roles, and summit settings.

Role dashboards follow the client's Dashboards design: the participant journey, scientific readiness with a decision queue, the executive summary, the reviewer desk, the finance operations desk and the registration desk with batch badge printing. There are also in-app notifications, role-aware search, a CSV export of payments and a registration target.

Photo gallery: photographers have their own accounts, upload into albums by day or programme session, and publish their own photos (no approval step). The public gallery shows albums by day with highlights, a lightbox and full-resolution downloads. Anyone in a photo can ask for it to be removed; admins decide and the person is emailed. Photos are stored on the server's own disk for now (`GALLERY_DISK`).

Awards: the scientific committee sets up each summit's awards (a suggested set comes from `config/awards.php`: Best Oral Presentation, Best Poster, Student Research Award, Innovation in Rehabilitation, Rehabilitation Champion and Distinguished Service). Presentation awards shortlist accepted abstracts, and judges (role `judge`) score the finalists at the summit on four criteria; nobody scores an abstract they wrote or co-wrote. Honours take nominations from participants or a committee choice. The committee picks up to three places from the ranking and announces them: winners appear on the public `/awards` page, are emailed, and download an award certificate. Attendee voting is deliberately not included.

Deployment: pushing to `main` deploys through John Mduda's GitOps pipeline (see CLAUDE.md).

Still to build for the live 2027 summit: group registration, sponsors, session chair and rapporteur applications, presentation uploads, scheduling abstracts into sessions in the portal (the demo schedule is seeded), certificates, the abstract book, the staff scanning app itself (its API is in `docs/STAFF_APP_API.md`), and email campaigns.

### Phase 0: Foundation and design system

Done so far: the Laravel 13 app, design tokens, public home page, sign-in screens (Fortify: sign-in, registration, email verification and password reset), roles and the admin seeder, and the portal layout with a first dashboard. Remaining: the `editions` table with admin settings (it replaces `config/summit.php`), the rest of the component library, and role-based navigation.
- Laravel 13 project, tooling (Pint, PHPUnit), CI, and README and CLAUDE.md.
- Design tokens and a component library: button, field, select, card, table, badge, alert, modal, sidebar and page header.
- Layouts: public, sign-in, and the portal with role-based navigation.
- Screens: sign-in, registration, forgot and reset password, and email verification, all built from the mockup.
- Public home page, from the approved preview `design/home.html`, with every edition detail (dates, venue, theme, fees, topics, key dates) read from the edition settings. Details not yet set show "To be announced".
- `editions` table with admin settings for dates, venue, deadlines and fees. Roles seeded, and the first admin created from `.env`.

### Phase 1: Accounts and registration
- Profile: title, names, phone, country, institution, profession, professional board and registration number.
- Registration for the current edition: the participant picks a category, and the fee comes from the edition settings.
- Student status verified with an uploaded document.
- Participant dashboard showing the next step to take.

### Phase 2: Payments, invitation letters and sponsors
- Bank transfer and mobile money instructions, payment reference, and proof upload to private storage.
- Finance officer queue to verify or reject payments, a full audit trail, and PDF receipts.
- Group registration and payment.
- Invitation and visa letters as PDFs. These are needed early because of visa lead times.
- Sponsors and sponsorship payments.
- **Payments are the riskiest area of the system, so they get the most thorough tests.**

### Phase 3: Abstracts
- Submission (topic, type and co-authors) that opens and closes on the edition's deadline.
- Scientific admin assigns reviewers, with conflict-of-interest exclusions. Reviews are double-blind and scored.
- Decisions, revision requests, notification emails, and conference codes (e.g. `OR-HBR-01`).

### Phase 4: Programme
- Halls, sessions, and scheduling accepted abstracts into sessions.
- Session chairs and rapporteurs, and rapporteur reports.
- Presentation uploads with a deadline.
- Public programme page and a PDF programme.

### Phase 5: On-site
- Badge PDFs with QR codes, and badge printing for registration officers.
- Entrance check-in and on-site registration.
- Staff API for the mobile app, which moves into this repository, and session attendance scanning.

### Phase 6: Close-out
- Certificates based on recorded attendance and on presenting.
- Abstract book and proceedings as PDFs.
- Feedback survey.

### Phase 7: Communications and reporting
- Email campaigns and announcements.
- Admin dashboards and exports: registrations, payments, abstracts and attendance.

## Deliberately left out

The old portal had about 30 services. Many were extras from the earlier AJSC conference: executive analytics, auto-routing, reviewer performance tracking, incident alerting and revision comparison. We build only what the phases above need, and add more when the organisers ask for it.

## Open questions

1. Production hosting: which server it runs on, and whether it can run PHP 8.4. If it uses the Docker image, the base image changes to `php:8.4-fpm`.
2. Vector logo (SVG, AI or EPS) from the client, for print quality on badges and certificates.
3. The 2027 dates, venue and fees.
