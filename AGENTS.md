# CLAUDE.md

Guidance for Claude Code and other agents working in this repository.

## Project

The Rehab Health Events Portal is the system for the annual Rehabilitation Summit run by Rehab Health, starting with the 2027 edition. It is a fresh codebase. The previous portal at `../Rehab` is reference material only.

- `docs/BUILD_PLAN.md` holds the decisions, the data model and the phases. Read it before starting a feature.
- `docs/DESIGN_SYSTEM.md` and `design/` hold the approved design system: tokens in `design/theme.css`, plus static previews of the components and the sign-in screen.

## PHP on this machine

The app needs PHP 8.4. This machine has two PHPs:

| Command | Version | Use |
|---|---|---|
| `php84`, `composer84` | 8.4 (`C:\php\8.4`) | This project |
| `php`, `composer` | 8.2 (XAMPP) | Other projects in htdocs. Do not use it here. |

```bash
composer84 run dev       # server, queue, Mailpit and Vite together (all on PHP 8.4)
php84 artisan serve      # server only: http://127.0.0.1:8100 (SERVER_PORT in .env)
php84 artisan test
php84 vendor/bin/pint    # vendor/bin/pint alone runs on PHP 8.2 and fails
php84 artisan migrate:fresh --seed
npm run build
```

Local email goes to Mailpit: http://127.0.0.1:8025. `composer84 run dev` starts it; with `artisan serve` alone, start `mailpit` yourself or emails fail.

## Demo data

The portal doubles as a demonstration of the complete product. **Every link must lead to a working screen; no "coming soon" pages.**

- `database/seeders/DemoSeeder.php` builds a sample 2027 summit: settings, staff for every role, about 60 participants at every payment stage, about 36 abstracts through review, and the programme. It refuses to run in production. `migrate:fresh --seed` runs it locally (it takes about 30 seconds because it renders sample bank slips).
- Demo accounts (password `rehab2027`) are listed in `DemoSeeder::ACCOUNTS`. With `APP_DEMO=true` the sign-in page shows them as one-click buttons.
- The sample bank and mobile money numbers live in `.env.example` and are marked as samples.
- Only code and structure go into git. The database, uploaded proofs and `.env` stay local, so production starts empty: `php artisan migrate`, then `php artisan db:seed --class=DeploymentSeeder` with `ADMIN_EMAIL` set.

## Code map

- `app/Support/Summit.php`: the current edition, as views see it (`$summit` in every view). It falls back to `config/summit.php` when no edition exists.
- `app/Services/RegistrationService.php`: registration and payments (locks, transactions, emails). `AbstractService.php`: abstracts, double-blind assignment, reviews, decisions and conference codes. `DocumentService.php`: badge and invitation letter PDFs.
- `app/Support/Navigation.php`: the sidebar, one section per role.
- `resources/views/components`: the component library (button, card, status, stat, page-header, empty, form fields, icon).

Port 8100 avoids the old portal, which often runs on 8000. Do not open this app through XAMPP's Apache (`localhost/rehab-events/public`), because that runs PHP 8.2.

Do not upgrade or reconfigure XAMPP's PHP. Other projects depend on it.

## Rules

- **Nothing edition-specific in code.** Dates, venue, fees and deadlines live in the `editions` table, edited by admins. Contact details and payment accounts go in config or `.env`.
- **Light mode only.** Use the design tokens (`brand`, `ink`, `ember`, `coral`, `olive`, `sun`, `canvas`), not raw Tailwind colours. The exception is status: use emerald, amber and red.
- Sign-in is email and password only.
- Payments are high-risk. Every change there needs tests.

## Workflow

- **Never `git push` without explicit approval from the user.** Commit locally, show what changed, and wait.
- Never combine commit and push in one command.
