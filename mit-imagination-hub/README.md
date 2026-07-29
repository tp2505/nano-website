# MIT Imagination Hub (theme)

A block theme (FSE) that provides the **presentation** for the MIT Imagination
Hub. It is deliberately presentation-only: every content type, field, and
data-driven block lives in the companion **`nano-imagination-hub-core`** plugin,
so content survives a theme switch. Design is a black/near-black editorial
palette on white, a self-hosted grotesk (Space Grotesk, in `assets/fonts/` — no
Google Fonts / CDN / analytics calls), and a recurring "bracket" motif (line +
riser + 45° diagonal to a coloured accent dot).

**Requirements:** WordPress FSE; the companion plugin; ACF Pro (for the plugin's
fields). No other dependencies.

## Where things live

- `templates/`, `parts/` — block templates and header/footer parts. Single
  templates (`single-event.html`, `single-class.html`, `single-person.html`, …)
  compose the plugin's blocks.
- `patterns/` — PHP patterns evaluated per request: `hero`, `mission`,
  `newsletter`, `copyright`.
- `assets/css/app.css` — layout/components beyond `theme.json`.
- `assets/js/nano.js` — the only script: lazy-play looping videos, the hero
  logic, the Archive filters, and the event-gallery click-to-play.
- `inc/fields.php` — the ACF isolation accessor (`nano_field` / `nano_media`).
- `inc/customizer.php` — the editable hero (below).

## The site name is one setting

The name is **not** hardcoded. The header logo, footer logo, and footer
copyright all read the **Site Title** (Settings → General). Change it there — to
add "nano" or anything else — and every instance updates; the copyright year is
automatic. (The one hand-written mention is the Mission statement's sentence,
left as authored editorial copy.)

## The hero video is editable, and cheap on mobile

- **Editable:** Appearance → **Customize → Hero** sets the hero video (MP4) and
  poster image (`inc/customizer.php`, `WP_Customize_Media_Control`). Both fall
  back to the bundled `assets/video/` files, so the theme works out of the box —
  no code change / review round needed to swap them.
- **Poster-first:** the `<source>` ships detached (`data-nano-src`, no `src`) with
  `preload="none"` and no `autoplay`, so nothing downloads on its own — the
  optimised poster paints instantly.
- **Mobile / data-saver / reduced-motion:** `assets/js/nano.js` only attaches and
  plays the clip on non-phone, non-Save-Data, non-reduced-motion loads. On phones
  the poster stands in and **zero** video bytes are fetched (kinder on CampusPress
  bandwidth). All client-side, so it's cache-safe (no server UA sniffing).

## Accessibility notes

One `<h1>` per page; section labels are `<h2>` so heading order never skips.
Decorative accent dots and background video are `aria-hidden`; icon buttons have
`aria-label`s; the constrained Archive Initiative filter is conveyed with
`aria-disabled` + a note; empty states are real text. Images get their `alt` from
the media library; reduced-motion is respected (no autoplay anywhere).

## Development

The local dev workflow (`@wordpress/env`, seeding, verification tooling) lives
in a separate development repository; this repo contains only the deployable
theme and plugin.
