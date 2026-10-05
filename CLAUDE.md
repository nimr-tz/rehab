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
php84 artisan serve      # http://127.0.0.1:8100 (SERVER_PORT in .env)
composer84 run dev       # server, queue, logs and Vite together
php84 artisan test
composer84 install
npm run build
```

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
