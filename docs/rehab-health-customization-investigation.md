# Rehab Health Summit Platform Customization Investigation

**Status:** Investigation and planning only. No application implementation was performed.  
**Prepared from:** Current AJSC/NIMR repository at commit `80fc50fc` and the 61-page `Rehab Summit Program Book 2026.pdf`.  
**Target described by the source document:** 4th Annual Rehabilitation Summit 2026, organized by Rehab Health with the Ministry of Health and regional partners.

## 1. Executive conclusion

The existing system is a large, capable conference platform, but it is not currently a neutral conference product. AJSC and NIMR assumptions are embedded in configuration, business rules, data structures, routes, emails, PDFs, public assets, the mobile app, deployment scripts, billing integration, and operational shortcuts.

The correct approach is a controlled conversion into a Rehab Health event platform. A simple rebrand would leave incorrect scientific topics, registration categories, payment rules, review criteria, presentation formats, mobile identities, certificate rules, sponsor data, and NIMR-only restrictions underneath a Rehab Health visual layer.

The recommended target is a reusable **Rehab Health Summit platform** with an event-edition layer, rather than another single-edition hard-coded application. The 2026 summit should become the first configured edition. This avoids repeating the same emergency customization cycle for future summits.

The conversion should preserve proven capabilities such as authentication, user accounts, abstract workflow foundations, program delivery, notifications, QR badges, mobile caching, and administrative tooling. It should remove or quarantine live-conference diagnostics, backfills, bypasses, NIMR-only controls, AJSC content, and unconfirmed optional modules.

## 2. Investigation scope and limitations

### Examined

- Laravel application structure, configuration, routing, middleware, controllers, models, services, jobs, commands, mail classes, views, migrations, seeders, and tests.
- Expo/React Native application configuration, navigation, API modules, context providers, screens, assets, store identifiers, deep links, and tests.
- Git history before, during, and after the AJSC conference dates.
- The complete 61-page Rehab Summit program book, including its visual hierarchy and page-by-page extracted content.
- Existing deployment, production, API, and billing documentation.

### Repository scale

- Approximately 61,000 lines under `app/`.
- Approximately 76,000 lines under `resources/`.
- Approximately 25,000 lines in the mobile source.
- 60 model classes and 43 service classes.
- 75 created database tables across 186 migrations.
- Approximately 539 web route declarations and 93 API route declarations in source.
- 335 Blade views and 38 mobile screens.
- 22 backend test files and 16 mobile test files.

### Verification limitations

- PHP dependencies are not installed (`vendor/autoload.php` is absent).
- Root and mobile JavaScript dependencies are not installed.
- Therefore route compilation, backend tests, frontend builds, and mobile tests could not be executed during this read-only investigation.
- No production database, current infrastructure configuration, payment contract, brand guide, or operational policy was supplied.
- The program book is authoritative for target content, but it does not define every product policy. Missing policies are listed in the decision register.

These limitations do not prevent the architectural and fit-gap findings, but runtime behavior must be verified after dependencies are restored in an isolated development environment.

## 3. Target organization and event profile from the program book

### Institutional identity

- Event: 4th Annual Rehabilitation Summit 2026.
- Organizer: Rehab Health.
- Primary public partner: Ministry of Health.
- Regional collaboration is described with ECSA-HC.
- Theme: "Rehabilitation Across the Life Course in Universal Health Coverage (UHC)."
- Dates: 16-18 September 2026.
- Venue: Julius Nyerere International Convention Centre (JNICC), Dar es Salaam.
- Website: `rehabhealth.or.tz`.
- General contact: `info@rehabhealth.or.tz`.
- Abstract contact: `abstract@rehabhealth.or.tz`.
- Public phone numbers and social handles are supplied in the program book.

### Visual identity

The document uses a cream base, mustard/gold accents, dark teal/blue-green headings, orange highlights, rounded portrait treatments, and strong institutional partner placement. The existing NIMR blue visual system is not suitable as the target theme.

Original brand files are still required. Logos and photographs extracted from the PDF should not be treated as production master assets.

### Event structure

The summit is a three-day, multi-room event with at least six named spaces:

- Ruaha
- Mikumi
- Olduvai
- Amboni
- Bagamoyo
- Gombe

The program contains:

- Opening and closing ceremonies
- Panel discussions and fireside chats
- Scientific presentations
- Workshops
- Health forums
- A multi-day emergency and critical care workshop
- Wellness activities
- Registration, breaks, meals, networking, entertainment, awards, booth visits, and protocol items

This is broader than the current application's principal oral/poster scientific-session model.

### Nine summit topics

1. Home-based rehabilitation
2. Technology and AI in rehabilitation
3. Financing rehabilitation services
4. Occupational health and workplace rehabilitation
5. Rehabilitation leadership and advocacy
6. Rehabilitation across the life course
7. Rehabilitation and non-communicable diseases
8. Women, disability and inclusive development
9. Research and innovation

These replace the eight AJSC scientific subthemes and their AJSC conference-code prefixes.

### Abstract requirements in the source document

- Submission opens: 10 August 2026.
- Submission deadline: 24 August 2026.
- Outcome notification: 3 September 2026.
- Participation confirmation: 7 September 2026.
- Presentation upload: 11 September 2026.
- Maximum abstract length: 300 words.
- Required structured headings:
  - Background
  - Purpose
  - Methods
  - Results
  - Conclusions
  - Implications
- Up to three keywords.
- Funding acknowledgement where applicable.
- One mandatory primary topic and optional second and third topics.
- Ethics approval information where applicable.
- Presenting-author details and biography up to 200 words.
- Co-author details.
- Prior presentation/publication information up to 50 words.
- Preferred presentation format.
- The book describes oral presentation; it does not establish a poster track.
- Selection criteria are significance, methodological rigor, appropriate interpretation, and clarity/logic.

### Committees and content

The book includes program, abstract, and organizing committees; masters of ceremony; many moderators, chairs, panelists, facilitators, and responsible organizations; dozens of sponsors and partners; long speaker biographies; and a student sponsorship feature.

This requires proper content management and session-person relationships. It should not be implemented as static fallback arrays or page-specific markup.

## 4. Source-document issues requiring confirmation before import

The program book should not be imported blindly. It contains editorial and scheduling ambiguities:

- Several noon entries use `12:00 AM` where `12:00 PM` is almost certainly intended.
- The Bagamoyo workshop appears on both Day 2 and Day 3 with substantially repeated content.
- "Health Forum 5" appears more than once.
- Program columns mix chairs, moderators, panelists, presenters, organizations, and responsible persons.
- Some biographies and names contain typographical or encoding errors.
- The source says abstracts are submitted by email, while the requested platform presumably replaces email submission with an online workflow. This must be explicitly approved.
- Registration fees, attendee categories, payment provider, attendance policy, certificate rules, award rules, exhibition application rules, and mobile-app scope are not specified.

A cleaned spreadsheet or approved content workbook should be the import source. The PDF should remain the reference for appearance and human verification.

## 5. Current AJSC/NIMR platform baseline

### Backend

- Laravel 12 and PHP 8.2+.
- Blade-first web UI with Alpine, Tailwind, Vite, Chart.js, GSAP, and a small React/Three.js visual area.
- Sanctum API authentication for the mobile app.
- Service layer for reviews, decisions, revisions, billing, notifications, conference programming, proceedings, PDFs, attendance, and analytics.
- Queued email and PDF generation.
- Scheduled review automation, reminders, and notification cleanup.

### Mobile app

- Expo 54 and React Native.
- TanStack Query plus context providers.
- Six-tab navigation with home, schedule, speakers, feed, abstracts, and more.
- Push notifications, deep links, offline caching, favorites, Q&A, networking, staff scanning, gamification, sponsor visits, notes, and social feed.

### Major current domains

- Authentication and multi-role access
- Individual and group registration
- Student verification
- Abstract submission, double-blind review, revisions, and decisions
- Symposium proposals and members
- Exhibition applications and booths
- Sponsor billing
- Conference programming and template import
- Speakers and presenters
- Presentation upload/download
- Attendance, walk-ins, badges, and QR scanning
- Certificates and public verification
- Notifications, campaigns, and email logs
- Feedback surveys
- Rapporteur and chief-rapporteur reporting
- Awards and voting
- Networking, social feed, Q&A, ratings, and gamification
- Abstract books, proceedings, invitation letters, and reports

The breadth is valuable, but much of it is not confirmed by the Rehab Summit document.

## 6. AJSC/NIMR coupling inventory

The search found AJSC text in approximately 277 files and NIMR text in approximately 137 files. Direct domain references occur in at least 12 files. Conference dates occur in application and mobile source as hard-coded defaults.

Coupling exists in:

- `config/conference.php`
- `config/billing.php`
- `config/print_design.php`
- the `User` model fee rules
- `PaymentAccessService` and `NimrAffiliation`
- conference code assignment and topic detection
- registration and payment controllers
- the landing page and web layouts
- more than 50 mail/notification artifacts
- badge, invitation, certificate, abstract-book, proceedings, and program templates
- public PDFs, images, logos, video, and QR files
- seeds and default accounts
- deployment documentation
- the mobile manifest, source, theme, screens, assets, links, and store metadata
- static sponsor data and gamification IDs

This is why a search-and-replace exercise would be unsafe.

## 7. Conference-time and emergency-change investigation

### Git evidence

The last commit before the AJSC event day was `5ca4775b` on 8 June 2026. From 9-18 June there were 87 commits:

- 9 June: 11 commits
- 10 June: 14 commits
- 11 June: 15 commits
- 12 June: 26 commits
- 13 June: 10 commits
- 15 June: 6 commits
- 17 June: 2 commits
- 18 June: 3 commits

The week immediately before the event, 2-8 June, contained another 165 commits. From the pre-event baseline to the current head, 272 files changed with about 15,631 additions and 3,442 deletions.

This confirms that the live system evolved under operational pressure and should not be cloned as-is.

### Definite removal or quarantine candidates

1. **Live inspection APIs**
   - Public symposium and live inspection routes expose operational, contact, phone, payment, and program data.
   - The attendance endpoint uses a shared key that has a non-empty default embedded in source.
   - Comments identify it as temporary.
   - Remove these from the target product. Rebuild required diagnostics as authenticated admin reports with audit logging.

2. **Attendance reconstruction commands**
   - `BackfillAttendance`, `FixAttendanceHeadcount`, and `CleanupHeadcountWalkins` were added during or after the event to reconstruct under-recorded attendance.
   - They encode historical AJSC recovery assumptions and must not become ordinary target operations.
   - Preserve only as archived migration history if legally required; do not expose them in the Rehab Health deployment.

3. **Certificate emergency rules**
   - Live changes treated badge possession, payment, or waiver as full attendance, released certificates immediately, and added name-based public claims.
   - `User::canDownloadCertificate()` still returns `true` unconditionally.
   - Target certificate rules must be defined from first principles and enforced in one policy service.

4. **Post-conference navigation and landing-state patches**
   - The dashboard, sidebar, and landing hero were trimmed or switched to wind-down mode during the event.
   - The target needs explicit event phases: pre-event, live, post-event, and archived. It should not depend on one-off route/view rewrites.

5. **Public preview and test routes**
   - Email previews, test pages, a curtain test, certificate preview login, and basic diagnostic routes exist in the main web route file.
   - Development-only utilities must be isolated by environment and authorization; obsolete ones should be deleted.

6. **Hard-coded app distribution fallbacks**
   - AJSC iOS IDs, a Google Drive Android fallback, and server APK distribution were introduced at event start.
   - Rehab Health requires new app identifiers, signing, EAS ownership, store records, legal text, and release links.

7. **Live content fallbacks**
   - The landing page contains hard-coded speaker arrays and venue content.
   - The sponsor API contains a hard-coded list of 30 organizations, including unnamed `Partner` entries.
   - Replace with managed database content.

8. **NIMR-only payment restrictions**
   - A live switch restricts new individual/group payments based on NIMR affiliation text and email domains.
   - It is invalid for Rehab Health and should not be generalized by renaming NIMR to Rehab Health. The target payment eligibility policy must be explicit.

### Changes that may be retained after review

Not every live-period commit is bad. The following concepts are reusable if covered by tests and cleaned of event-specific behavior:

- Graceful 419/session-expiry recovery and no-cache HTML behavior
- Background generation of large downloads
- Staff scanner support
- Badge print auditing
- Public badge verification with privacy-limited fields
- Feedback collection as a configurable module
- Rapporteur workflows, but only if Rehab Health confirms that role
- Program and abstract-book rendering fixes
- API bug fixes, sorting fixes, and mobile layout fixes

The implementation phase should use a commit-by-commit salvage register, not a blanket revert to 8 June. Reverting wholesale would also discard legitimate fixes and later schema evolution.

## 8. Immediate safety findings before any target deployment

These are planning findings, not proof that a production server is currently exploitable. They require verification and remediation before a Rehab Health launch.

### Critical/high priority

- A default inspect secret is embedded in source, and several other inspection endpoints have no visible route middleware.
- Public QR lookup returns attendee email, phone, affiliation, country, payment status, and QR token.
- Public API routes can mark check-in and badge printing without the protected staff middleware used by the newer staff scanner endpoints.
- Billing callbacks accept state-changing payment data without a signature, API key, replay guard, or visible IP allowlist.
- Deployment seeders create `admin@ajsc.com` with password `password` and announce the credentials.
- The certificate eligibility method has an unconditional allow result.
- Public preview/test routes and environment-specific shortcuts remain in production route source.
- Generated QR images and event documents are present under `public/` and in source control.

### Structural priority

- `routes/web.php` is very large and contains legacy and overlapping route generations. Several help and notification routes are declared more than once in the same authenticated area.
- Business policy is split among config, models, controllers, services, views, and emergency database settings.
- Status vocabularies contain legacy aliases and multiple revision concepts.
- Controllers and views contain old, backup, modern, premium, and replacement variants, making the active path difficult to verify.
- The application has relatively few backend tests for its size and risk surface.
- Dependencies are absent locally, so no current green build or test baseline is available.

## 9. Detailed fit-gap analysis

| Capability | Current AJSC state | Rehab Health requirement | Planned disposition |
|---|---|---|---|
| Organization/event identity | Partly configurable, widely hard-coded | Rehab Health, MoH, ECSA-HC, JNICC, September dates | Centralize organization and event-edition configuration; replace all hard-coded fallbacks |
| Brand/theme | NIMR blue, AJSC assets and copy | Cream/gold/teal Rehab Summit identity | New design system and approved asset pack |
| Landing site | Rich but AJSC-specific with hard-coded speakers/venue/video | Summit welcome, formats, topics, program, partners, contacts | Rebuild as managed content sections |
| Event reuse | Single active conference in global config | Institute-owned annual summit platform | Add event-edition boundary and event-scoped content |
| Registration | Four AJSC local/international professional/student categories | Not specified | Keep engine; redesign categories and fees only after approval |
| Payments | NIMR billing/GePG assumptions and affiliation gate | Not specified | Disable by default; integrate only approved provider and signed callbacks |
| Abstract form | One free-text description, one subtheme, free keywords, oral/poster | Six structured fields, 300 words, up to 3 topics, ethics, funding, bio, publication history, oral preference | Redesign schema, form, validation, import/export, and review display |
| Review model | Double-blind, two reviewers, scoring thresholds, auto-routing/decisions | Four stated selection criteria; reviewer count/blinding/decision policy unknown | Preserve framework but configure only after policy workshop; no automatic decision assumption |
| Topics | Eight AJSC health-research subthemes with code prefixes | Nine rehabilitation topics | Replace data and topic matching; use normalized topic records, not config keys |
| Program | Powerful session builder; AJSC oral/poster/symposium assumptions | Panels, chats, scientific talks, workshops, forums, wellness, ceremonies across six rooms | Extend session types and participant roles; import approved schedule |
| Session people | One speaker relation plus text chair/rapporteur/panelists | Multiple ordered moderators, chairs, panelists, presenters, facilitators, organizations, MCs | Add many-to-many session-person-role structure |
| Speakers | CRUD and bios available | Many long biographies and portraits | Retain and extend; add consent, role, country, organization, and event scope |
| Sponsors/partners | Hard-coded mobile/API list; separate finance records | Dozens of sponsors/partners in book | Create sponsor/partner CMS with tier, logo, URL, order, and event relationship |
| Committees | No clear first-class committee content model | Program, abstract, organizing committees and MCs | Add managed committees and memberships or a structured content module |
| Exhibition | Full application/booth/payment workflow | Only booth visit appears in program | Disable application workflow unless Rehab Health confirms it |
| Symposium proposals | Full proposal/member/payment workflow | Not specified as a submission product | Disable unless confirmed; sessions can still be configured as panels/forums |
| Awards | Voting/nominations system | Awards ceremony appears, rules absent | Disable voting until award categories and eligibility are supplied |
| Rapporteurs | Extensive reporting system | Not identified in book | Disable unless organizational policy confirms it |
| Feedback | AJSC-specific questionnaire, modified live | No survey specified | Replace with configurable post-event survey if requested |
| Badges/check-in | Feature-rich but contains public legacy mutations and backfills | Not specified | Retain only if required; secure staff APIs and create Rehab templates |
| Certificates | Template engine exists; policy was altered live | Not specified | Keep rendering engine, replace eligibility and artwork after approval |
| Abstract book/proceedings | Strong generation tooling | Program book includes abstract guidance, but publication policy is unclear | Retain as optional; define whether accepted abstracts are published |
| Invitations/visa | Available | Not specified | Optional feature flag |
| Networking/social/Q&A | Available in mobile and API | Not specified | Off by default; enable only after moderation/privacy decisions |
| Gamification/sponsor passport | Available | Not specified | Off by default |
| Mobile app | Full AJSC app with NIMR store identity | Rehab Health identity and approved feature set | Separate release project, bundle IDs, push credentials, links, content, privacy text |
| Analytics/reports | Broad but AJSC status/category assumptions | Institute reporting requirements unknown | Retain engine; rebuild dimensions after approved data model |
| Email/notifications | Extensive AJSC templates | Rehab Health sender, copy, contacts, dates, links | Central event-aware template system and full content review |

## 10. Recommended target product design

### 10.1 Product boundary

Create one institute platform with reusable event editions:

`Rehab Health` -> `Rehabilitation Summit` -> `2026 edition` -> content, registrations, submissions, sessions, people, sponsors, communications, and reports.

This is not multi-tenant SaaS. It is a single-institute product with clean annual-event separation.

### 10.2 Core entities to introduce or normalize

- Organization profile
- Event series
- Event edition
- Brand/theme settings
- Venues and rooms
- Topics/tracks
- Session formats
- Sessions and schedule slots
- People/speakers
- Session participants with role and display order
- Sponsors/partners and tiers
- Committees and memberships
- Configurable registration types and prices
- Structured abstract sections
- Abstract-topic selections with primary/secondary/tertiary order
- Presenter profile and biography
- Review criteria and reviewer responses
- Event feature flags
- Content pages and navigation
- Communication templates

### 10.3 Feature flags

Every optional module should be explicitly enabled per event:

- Registration
- Payments
- Abstract submissions
- Peer review
- Revisions
- Presentation uploads
- Exhibition applications
- Symposium proposals
- Awards/voting
- Rapporteur reports
- Feedback
- Badges/check-in
- Certificates
- Networking
- Social feed
- Q&A
- Gamification
- Mobile app
- Proceedings/abstract book

Unconfirmed features should be disabled, not deleted, until the institute decides.

### 10.4 Event lifecycle

Replace ad hoc landing/dashboard edits with controlled phases:

1. Draft
2. Registration open
3. Submission open
4. Review
5. Program published
6. Live event
7. Post-event
8. Archived

Each phase determines available actions and public content. Administrators may override dates through audited settings.

## 11. Detailed workstreams

### A. Stabilization and clean-room branch

- Create an isolated customization branch/worktree from current head.
- Record the current production behavior before removal.
- Restore dependencies from lock files.
- Establish a green baseline for backend, web build, and mobile tests.
- Generate a route inventory and identify actual collisions after Laravel boots.
- Classify post-8 June commits as retain, refactor, disable, remove, or archive.
- Remove tracked runtime artifacts and separate generated files from source.
- Move one-off root scripts into an archived operations area or delete after review.

**Exit gate:** reproducible local environment, green baseline, approved salvage register.

### B. Security and operational cleanup

- Remove temporary inspection and public debug routes.
- Protect all check-in, badge-print, and operational endpoints with staff authorization.
- Limit public badge verification to minimum non-sensitive fields.
- Replace callback trust with signed requests, timestamps, replay protection, and provider-specific validation.
- Remove default credentials and require secure admin provisioning/password reset.
- Fix certificate eligibility and consolidate rules.
- Add rate limits to authentication, sensitive lookups, and public submissions.
- Audit file uploads, stored filenames, access controls, and retention.
- Add audit logs for finance, attendance, role, and decision changes.
- Rotate all inherited keys and credentials during deployment.

**Exit gate:** security review passes before any target data is loaded.

### C. Platform configuration and event scoping

- Introduce organization/event-edition records.
- Move dates, identity, theme, location, contacts, links, and feature availability out of scattered source defaults.
- Scope program, topics, speakers, sponsors, submissions, announcements, feedback, and reports to an event edition.
- Define behavior for legacy rows during transition.
- Keep one active-edition shortcut for simple admin use.

**Exit gate:** changing edition data no longer requires source edits.

### D. Rehab Health brand and public website

- Obtain approved logo suite, fonts, colors, photo rights, venue media, partner logos, and brand guide.
- Build the Rehab Health design tokens.
- Replace NIMR/AJSC headers, footers, copy, metadata, URLs, social links, and legal text.
- Create managed landing sections for welcome, formats, topics, schedule, speakers/panelists, committees, sponsors, venue, call for abstracts, downloads, and contact details.
- Add accessible color contrast, keyboard behavior, alt text, and responsive verification.
- Remove hard-coded fallback people and venue text.

**Exit gate:** institute signs off desktop and mobile public-site designs.

### E. Registration and profile workflow

- Define attendee categories, eligibility, required profile data, consent, privacy notice, fees, currencies, complimentary categories, group registration, and student evidence requirements.
- Remove NIMR affiliation detection and AJSC fee labels.
- Determine whether presenters must separately register and pay.
- Define invitation/visa support.
- Implement event-specific registration statuses rather than embedding all status in the global user record.

**Exit gate:** approved registration policy and end-to-end acceptance tests.

### F. Abstract submission and review

- Implement the six required structured sections and 300-word aggregate limit.
- Add primary, secondary, and tertiary topic relationships.
- Enforce no more than three keywords.
- Add funding, ethics, presenter biography, co-author details, prior presentation/publication, and preferred format.
- Decide whether only oral submissions are allowed.
- Define reviewer count, blind-review policy, scoring scale, conflicts, recommendation options, revision rounds, and final authority.
- Map the four published selection criteria into a configurable review form.
- Do not carry AJSC automatic thresholds into production without written approval.
- Rebuild confirmation, reviewer, decision, revision, and presentation-upload emails.

**Exit gate:** sample submissions pass author, reviewer, committee, and administrator UAT.

### G. Program, people, committees, and venue

- Add normalized session formats and ordered session participants.
- Support moderator, chair, panelist, presenter, facilitator, responsible organization, MC, protocol, and guest roles.
- Support parallel rooms, multi-day workshops, breaks, ceremonies, and wellness events.
- Import a cleaned program workbook rather than parsing the PDF directly.
- Add conflict detection for people, rooms, and overlapping sessions.
- Model program, abstract, and organizing committees.
- Generate web, mobile, printable, and downloadable views from the same records.

**Exit gate:** generated program reconciles line-by-line with the approved master schedule.

### H. Sponsors and partners

- Replace static sponsor arrays with database-backed content.
- Store name, tier/type, logo, description, URL, booth, sort order, and visibility.
- Separate event partners from paid sponsors and exhibitors.
- Decide whether sponsor visits/gamification are required.
- Obtain explicit permission for every logo.

**Exit gate:** sponsor/partner pages match the approved partner register.

### I. Payments and finance

- Confirm whether Rehab Health uses GePG, bank transfer, card/mobile money, invoices, waivers, or manual verification.
- Disable NIMR billing until a target contract is known.
- Externalize fee and revenue-source mapping.
- Define idempotency, signed callbacks, amount/currency verification, partial/overpayment handling, refunds, receipts, reconciliation, and finance audit logs.
- Verify local and international payment flows separately.

**Exit gate:** finance owner signs off sandbox reconciliation and failure scenarios.

### J. Onsite operations

- Decide whether badges, QR check-in, badge print tracking, walk-ins, daily attendance, and staff mobile scanning are in scope.
- Create Rehab Health badge artwork and printer specifications.
- Remove historical backfill behavior from normal operation.
- Test offline/degraded connectivity procedures and reconciliation.
- Apply least-privilege access to staff devices.

**Exit gate:** timed rehearsal at expected registration volume.

### K. Certificates, feedback, and post-event content

- Define certificate types and eligibility in writing.
- Create approved templates and signatory data.
- Require auditable issuance and revocation.
- Decide whether feedback gates certificate access.
- Replace the AJSC questionnaire with approved summit questions.
- Define post-event publication of presentations, recordings, abstracts, and proceedings.

**Exit gate:** every certificate can be explained from recorded policy and attendance/presentation evidence.

### L. Mobile application

- Decide whether a mobile app is required for the first Rehab Health release.
- Create new Expo/EAS project ownership under the institute or agreed publisher.
- Use new Android package and iOS bundle identifiers.
- Create new deep-link scheme, push credentials, store listings, screenshots, privacy policy, support URL, and release process.
- Rebrand all screens and remove `About NIMR` and AJSC copy/assets.
- Align mobile features with enabled web modules.
- Preserve offline program/speaker access and favorites if mobile remains in scope.
- Do not reuse the NIMR Apple application record or current bundle IDs.

**Exit gate:** signed test builds pass institute UAT and store/privacy checks.

### M. Communications

- Inventory every mail class, email Blade view, push template, announcement, SMS possibility, and in-app notification.
- Centralize sender identity, reply-to, organizer name, contact details, event dates, links, and signature.
- Create an approval matrix for transactional versus campaign messages.
- Test queues, retry behavior, duplicate prevention, unsubscribe/legal requirements, and delivery logs.

**Exit gate:** approved content matrix and successful staging delivery tests.

### N. Reporting, privacy, and retention

- Define institute reporting requirements and owners.
- Remove operational inspection endpoints in favor of authorized reports.
- Establish privacy notice, consent basis, speaker/photo consent, data retention, deletion, export, and breach procedures.
- Keep AJSC/NIMR personal data out of the target environment unless a documented lawful migration is approved.
- Define backup, restore, audit retention, and disaster recovery.

**Exit gate:** privacy and reporting sign-off.

## 12. Data migration recommendation

Use a clean target database.

### Do not migrate by default

- AJSC users and credentials
- AJSC abstracts and reviews
- payment and billing identifiers
- attendance and badge tokens
- NIMR speakers, sponsors, committees, and announcements
- social-feed and networking data
- certificates and feedback
- generated QR images and private documents

### Import only approved target data

- Rehab Health organization profile
- 2026 event edition
- nine summit topics
- approved rooms and schedule
- approved speakers, moderators, panelists, facilitators, and biographies
- committees
- partners and sponsors
- abstract dates and guidance
- approved registration/payment rules

Every import should be repeatable, validated, and produce a reconciliation report.

## 13. Testing strategy

### Required automated coverage

- Authentication, verification, password reset, and role authorization
- Event scoping and feature flags
- Registration categories and payment eligibility
- Abstract word count and structured-field validation
- Topic selection limits
- Reviewer assignment, conflicts, review criteria, and decisions
- Program participant ordering and schedule conflicts
- Public API privacy boundaries
- Billing signature, replay, idempotency, amount, and currency validation
- QR staff authorization and attendance uniqueness
- Certificate eligibility and revocation
- Email rendering and queue behavior
- Mobile API compatibility, offline cache, auth expiry, and deep links

### Manual/UAT scenarios

- New attendee
- Local and international registrant
- Presenter and co-author
- Reviewer and committee decision maker
- Program administrator
- Finance officer
- Registration desk operator
- Walk-in attendee, if allowed
- Speaker/panelist with multiple sessions
- Sponsor/partner administrator
- Mobile visitor and authenticated attendee
- Pre-event, live-event, post-event, and archived phases

### Non-functional tests

- Accessibility
- Mobile network degradation
- queue and mail failure recovery
- concurrent check-in
- large program and abstract exports
- backup restore
- security testing
- privacy review
- load testing around registration, deadline, program launch, and live check-in

## 14. Recommended delivery phases

### Phase 0 - Decisions and source pack

Collect policies, assets, cleaned content, target domain, infrastructure ownership, and account ownership.

### Phase 1 - Stabilize and secure

Restore dependencies, establish tests, remove temporary diagnostics/bypasses, protect operational APIs, and create the salvage register.

### Phase 2 - Platform foundations

Add event-edition boundaries, centralized settings, feature flags, and clean target seeding.

### Phase 3 - Brand and public content

Deliver the Rehab Health public site, design system, topics, people, committees, partners, venue, and contact content.

### Phase 4 - Core submission workflow

Deliver registration, structured abstracts, approved review policy, decisions, and communications.

### Phase 5 - Program and publication

Deliver multi-format program management, participant roles, schedule import, public/mobile program, and approved downloads.

### Phase 6 - Optional operations

Add only approved payments, check-in, badges, certificates, feedback, networking, social, gamification, exhibitions, or awards.

### Phase 7 - Mobile release

Rebrand, reduce to approved features, provision new identities/credentials, test, and publish.

### Phase 8 - Rehearsal and launch

Migrate approved data, run full UAT, perform a registration-desk rehearsal, test backup/restore, freeze changes, and use a documented incident process.

## 15. Indicative effort

The following is an order-of-magnitude estimate, not a fixed quote:

- Visual rebrand only, retaining incorrect policies: 3-5 weeks. Not recommended.
- Safe single-edition conversion with core web workflows: 8-12 weeks.
- Recommended reusable platform conversion, full hardening, program/content migration, and optional mobile release: 12-18 weeks.

Assumptions:

- One experienced full-stack engineer with part-time design and QA support.
- Prompt access to decision makers and approved assets.
- No major payment-provider procurement delay.
- Clean target database.
- Mobile store accounts are available.

Parallel engineering, design, content, and QA can shorten elapsed time, but should not remove security, policy, or UAT gates.

## 16. Decision register for Rehab Health

The following must be answered before implementation is finalized:

### Product ownership

1. Is this a one-time 2026 clone or a reusable annual Rehab Health platform?
2. Who owns the domain, hosting, email sender, source repository, backups, mobile store accounts, and credentials?
3. Which organization name must be primary in the product: Rehab Health, Ministry of Health, or a co-branded arrangement?

### Registration and finance

4. What attendee categories, fees, currencies, discounts, waivers, and group rules apply?
5. Which payment provider and invoice/receipt process will be used?
6. Are presenters required to register and pay?
7. Is student verification required?

### Abstracts and review

8. Will the online platform replace submission by email?
9. Is oral the only presentation format?
10. How many reviewers are required, and is review blind?
11. What scoring scale and decision authority apply to the four selection criteria?
12. Are revisions allowed?
13. Are accepted abstracts published in an abstract book or proceedings?

### Event operations

14. Are badges, QR attendance, walk-ins, daily attendance, and certificates required?
15. Are session rapporteurs required?
16. Are exhibition applications, sponsor billing, award voting, CPD, and invitation letters required?
17. Are networking, social feed, Q&A, session ratings, and gamification required?
18. Is a mobile app required for the first release?

### Content and privacy

19. Can the institute provide original logos, portraits, sponsor assets, and a cleaned program spreadsheet?
20. Has consent been obtained to publish speaker biographies and photographs?
21. What data-retention and deletion policy applies?
22. Which program-book errors or schedule changes are officially approved?

## 17. Recommended first implementation backlog after approval

1. Restore and verify the development environment.
2. Create the clean customization branch and change register.
3. Remove temporary inspection/debug surfaces and credential defaults.
4. Secure staff check-in and billing callbacks.
5. Fix certificate-policy bypasses.
6. Add organization, event-edition, and feature-flag foundations.
7. Create clean Rehab Health seed data.
8. Build the new design system and public shell.
9. Replace topics and abstract fields.
10. Add session-participant roles and sponsor/committee management.
11. Import and reconcile the approved schedule and people.
12. Rebuild emails and generated artifacts.
13. Configure only the approved registration/payment/onsite modules.
14. Rebrand and reduce the mobile app, if approved.
15. Complete automated tests, UAT, load tests, security review, rehearsal, and launch checklist.

## 18. Final recommendation

Proceed with the reusable Rehab Health platform approach and a clean target database. Do not fork from the pre-conference commit and do not simply rename AJSC/NIMR. Use the current head as a source of reusable capabilities, while explicitly removing or disabling emergency operational behavior.

The first formal checkpoint should be a requirements and policy workshop based on the decision register. Once those decisions and original assets are supplied, the implementation can be broken into signed-off increments with no live-event shortcuts entering the permanent design.
