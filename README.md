# Rehab Summit Conference Portal

Conference management for the Rehabilitation Summit organised by Rehab Health: registration and payment, abstract submission and double-blind review, programme building, invitation letters, badges and check-in, session attendance for CPD, certificates and the programme and abstract book.

- **Web portal:** Laravel 12 (PHP 8.2+), Blade, Tailwind and Alpine, built with Vite.
- **Staff app:** `conference-mobile/`, an Expo app that ushers use to scan badges at the entrance and at session doors.

## Requirements

- PHP 8.2+ with `gd`, `mbstring`, `pdo_mysql` (or `pdo_sqlite`), `zip`, `bcmath`
- MySQL 8 in production (SQLite works for development)
- Node 18+ for building assets
- A queue worker, because PDF generation (abstract book, certificates) and email run as queued jobs

## Setup

```bash
cp .env.example .env
php artisan key:generate
composer install
npm install && npm run build
php artisan migrate
php artisan db:seed --class=DeploymentSeeder
php artisan storage:link
```

`DeploymentSeeder` creates the roles and the first administrator from `ADMIN_EMAIL` (and optionally `ADMIN_PASSWORD`). If no password is set, it generates one and prints it once.

For development, `composer run dev` starts the web server, queue worker, log viewer and Vite together.

## Configuring an edition

Everything specific to one edition lives in config and `.env`, not in code:

| What | Where |
| --- | --- |
| Name, edition, dates, venue, organiser, contacts, topics | `config/conference.php` (`CONFERENCE_*`) |
| Registration fees, bank accounts, mobile money numbers | `config/payments.php` (`FEE_*`, `PAYMENT_*`) |
| Invitation letter signatory and contact | `config/conference.php` (`INVITATION_*`) |
| Abstract book foreword and committees | `config/abstract_book.php` |
| Badge and certificate artwork, cover PDFs | `config/print_design.php`, files under `public/images/brand/` |

The badge and certificate artwork in `public/images/brand/` is a placeholder. Replace it with the approved designs; the text positions are set in `config/print_design.php`.

## Payments

Participants pay by bank transfer or mobile money and upload proof. The payment reference is generated per participant (for example `RH-U-000123`). Finance officers verify or reject each transaction in the finance dashboard. The mobile money gateway contract (`App\Payments\Contracts\MobileMoneyGateway`) is ready for a provider integration.

## Staff app

See `conference-mobile/README.md`. Staff sign in with a registration officer or admin account, pick the entrance or a session and scan badges. Session scans are recorded in `session_attendances` for CPD.

## Tests

```bash
php artisan test
./vendor/bin/pint        # code style
cd conference-mobile && npx jest
```

## Change workflow

- Create a focused branch for every change.
- Open a pull request with a summary, scope, validation and deployment notes.
- Pull requests must pass the `Pull Request Validation` workflow before merging.
