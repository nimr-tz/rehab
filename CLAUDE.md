# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

The Rehab Summit conference portal, a conference management system for the Rehabilitation Summit (organised by Rehab Health). It is built with Laravel 12 (PHP 8.2+), and the web UI uses Blade + TailwindCSS + AlpineJS, built with Vite. `conference-mobile/` is an Expo staff app for badge and session-door attendance scanning.

## Commands

### Laravel Backend

```bash
# Start all dev services concurrently (server + queue + logs + Vite)
composer run dev

# Individual services
php artisan serve
php artisan queue:listen
php artisan pail --timeout=0   # log streaming

# Testing
php artisan test
php artisan test --filter=PaymentFlow            # single test class
php artisan test tests/Feature/SessionAttendanceApiTest.php

# Linting
./vendor/bin/pint

# Database
php artisan migrate
php artisan db:seed --class=DeploymentSeeder   # roles + first admin from ADMIN_EMAIL
```

### Staff App (`conference-mobile/`)

```bash
npx expo start
npx expo start --tunnel   # for device testing outside LAN
eas build --platform android --profile preview   # build APK
npx jest   # run tests
```

### Frontend Assets

```bash
npm run dev     # Vite dev server (included in composer run dev)
npm run build   # production asset build (public/build is committed)
```

## Architecture

### Configuration

Everything edition-specific is config-driven. Never hard-code conference names, dates, venues, topics or people in code or views.
- `config/conference.php`: identity, dates, venue, organiser, contacts, topics (`subtheme_prefixes`), code prefixes, halls, invitation letter.
- `config/payments.php`: fees, bank accounts, mobile money providers.
- `config/abstract_book.php`: foreword and committees.
- `config/print_design.php`: badge and certificate artwork and text positions, and cover PDFs.
- `App\Support\ConferenceTopics`: helper for topic names, descriptions and colours.

### Backend Structure

**Multi-role RBAC**: admin, scientific_admin, reviewer, author, chair, rapporteur, chief_rapporteur, finance_officer, registration_officer. Roles are seeded by `DeploymentSeeder`.

**Services layer** (`app/Services/`): business logic. Key ones:
- `BlindReviewService`, `ReviewAssignmentService`: the double-blind abstract review workflow.
- `AbstractStatusService`: abstract status transitions and decisions.
- `ConferenceCodeAssignmentService` + `SessionTopicDetectionService`: conference codes (e.g. `OR-HBR-01`) derived from the abstract's topic.
- `EmailNotificationService`: all transactional emails and campaigns.

**Payments** (`app/Payments/`): provider-neutral. `PaymentService` records `payment_transactions` (morph `payable`: User, GroupRegistration, SponsorPayment) for bank transfers and mobile money. Proofs go on the private `local` disk, and finance officers verify them. `Contracts\MobileMoneyGateway` is the hook for a future provider API. Treat this path as high-risk.

**Attendance**: badge QR check-in at the entrance, plus `session_attendances` recorded by the staff app at session doors (`/api/staff/*`). CPD point rules are not implemented yet.

**Async jobs** (`app/Jobs/`): PDF generation (abstract book, certificates) and email run via the queue. Always run `php artisan queue:listen` in dev.

**API layer** (`routes/api.php`): the staff app only. It covers app config, login, programme and sessions, logout and the staff scanner endpoints (`auth:sanctum`).

### Database

SQLite in development and test (in-memory for tests), MySQL in production. Migrations live in `database/migrations/`. Test config is in `phpunit.xml`.

### Key Integrations

- **QR Codes**: `chillerlan/php-qrcode` generates badge QR codes. The staff app scans them.
- **PDF**: `barryvdh/laravel-dompdf` + FPDI for certificates, letters, the programme and the abstract book.

## Workflow Rules

- **Never `git push` without explicit user approval.** Always commit locally, show the user what changed, and wait for them to say "push" or "looks good, push it" before running `git push`.
- **Never combine commit + push in a single command** (`git commit && git push` or chained). Always separate them so the user can inspect the commit first.
