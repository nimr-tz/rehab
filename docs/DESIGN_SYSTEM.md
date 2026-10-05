# Design system

Light mode only. The source is the client's login mockup and the Rehab Health logo, since there are no brand guidelines. The live reference is `design/index.html` (components) and `design/login.html` (sign-in screen). Open them at `http://localhost/rehab-events/design/`.

## Principles

- **Calm and caring.** Use generous white space, soft shadows and rounded shapes. Nothing shouts.
- **Decoration on the outside only.** Waves, glowing orbs and the animated logo appear on the sign-in and public pages. Inside the portal, screens stay plain, dense and fast to scan.
- **One brand.** There are no per-role colour themes. Roles differ by what they see, not by colour.
- **Colour carries meaning once.** Teal is for actions and navigation. The four figure colours mark categories such as topics and tracks. Status uses its own standard green, amber and red.

## Colour

| Token | Base | Source | Use |
|---|---|---|---|
| `brand` | `brand-700` `#024f6d` | logo hand and crescent | Primary actions, links, navigation, focus |
| `ink` | `ink-800` `#303440` | logo wordmark | Text: 900 headings, 700 body, 500 secondary, 400 placeholder |
| `ember` | `ember-500` `#df670d` | orange figure | Category accent |
| `coral` | `coral-400` `#fd9a8f` | pink figure | Category accent |
| `olive` | `olive-700` `#45582e` | green figure | Category accent |
| `sun` | `sun-400` `#f2b302` | yellow figure | Category accent |
| `canvas` | `#f3f8fb` | mockup background | Page background |

Contrast on white: brand-700 9.0, olive-700 7.8, ember-500 3.4, coral-400 2.1, sun-400 1.9.
- **Coral and sun never carry text.** Use them as fills and dots, with ink-900 text on top.
- **Ember-500 is for large text only.** For small accent text, use ember-700.
- **Category chips** use the `-50` tint as background and the `-700` shade for text.
- **Status** uses Tailwind's emerald (success), amber (warning) and red (danger). Info uses brand.

Full ramps (50–950) are in the `@theme` block in `design/theme.css`, which becomes `resources/css/app.css`.

## Type

**Plus Jakarta Sans**, self-hosted in the app (Google Fonts in the static preview).

| Role | Class |
|---|---|
| Display (sign-in, public hero) | `text-4xl/5xl font-extrabold tracking-tight` |
| Page title | `text-2xl font-bold text-ink-900` |
| Section title | `text-lg font-semibold text-ink-900` |
| Body | `text-sm text-ink-700` (dense screens) or `text-base` (public pages) |
| Label | `text-sm font-medium text-ink-800` |
| Eyebrow | `text-xs font-semibold uppercase tracking-[0.2em] text-brand-700` |

## Shape and depth

| Token | Value | Use |
|---|---|---|
| `rounded-card` | 1.5rem | Cards, the sign-in panel, modals |
| `rounded-control` | 0.75rem | Inputs, buttons, selects |
| `rounded-full` | | Chips, avatars, status pills |
| `shadow-soft` | Teal-tinted, low | Cards at rest |
| `shadow-lift` | Teal-tinted, deep | The sign-in card, modals, popovers |

Borders are `ink-200`. Controls are 44–48px tall, with a `brand-500` focus ring at 20% opacity.

## Components (Phase 0)

Button (primary, secondary, ghost, danger; sm, md, lg) · Field (label, leading icon, hint, error) · Password field with show/hide · Select · Checkbox · Card · Stat card · Status pill · Topic chip · Alert · Table · Modal · Page header · Sidebar navigation · Empty state.

## Logo usage

- `logo-mark.png` (trimmed, transparent) goes in the portal header next to the live-text wordmark "Rehab Health" and "Events Portal".
- `logo-2027-animated.webp` appears on the sign-in and public hero only, sized at 400px or smaller. It plays once over the still image `logo-2027-still.webp`. With reduced motion, only the still shows.
- The client's files are raster images. A vector logo is still needed for printed badges and certificates.
