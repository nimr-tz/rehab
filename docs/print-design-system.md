# Print Design System

This project now treats printable badges and certificates as a shared print system rather than unrelated Blade designs.

## Current Structure

- Shared print config:
  - `config/print_design.php`
- Shared badge print partial:
  - `resources/views/registration/badge/partials/single-print.blade.php`
- Shared certificate base layout:
  - `resources/views/certificates/partials/base.blade.php`
- Screen preview aligned to the same badge config:
  - `resources/views/components/badge-preview.blade.php`

## Design Ownership Split

The fixed design should live outside business logic:

- background assets
- signature assets
- approved logos
- variant styling values
- print-safe coordinates

The application should only inject:

- attendee/presenter name
- title
- affiliation/institution
- QR code
- certificate number
- issue date
- abstract title/session metadata

## Where To Change Things

### Global badge branding

Edit:

- `config('print_design.badge.logos.left')`
- `config('print_design.badge.logos.right')`
- `config('print_design.badge.conference_title')`
- `config('print_design.badge.host_badge_prefix')`
- `config('print_design.badge.scan_label')`

### Certificate templates (badge-style overlay)

Certificates work exactly like the ID badge: the variant's background image
**is** the entire design (frames, logos, headings, signatures, static wording
all baked into the artwork). The app only stamps dynamic text on top:

- participant name (auto-shrinks for long names)
- optional body lines (attended dates, abstract title, poster number)
- verification QR code
- certificate number + issue date

Edit:

- `config('print_design.certificate.template')` — page size in mm; must match
  the artwork's aspect ratio (e.g. `297 x 210` for A4-landscape artwork)
- `config('print_design.certificate.placeholders')` — default text positions
  (left/top/width as % of page) and font styling
- `config('print_design.certificate.variants.<variant>.background')` — the
  artwork PNG for each of `attendance_full`, `attendance_partial`, `oral`,
  `poster`
- `config('print_design.certificate.variants.<variant>.placeholders')` —
  per-variant placeholder overrides (deep-merged over the defaults)

To tune positions, render samples with `php tmp/render_cert_test.php` (writes
sample PDFs to `tmp/`) or use the admin-only `/certificate/preview` route.

## Working Rule

If design team feedback comes later:

1. replace the background/signature/logo assets if needed
2. adjust config values
3. only touch shared partials if the layout structure itself must change

Avoid adding new one-off Blade templates unless the printable object is genuinely a new class of document.
