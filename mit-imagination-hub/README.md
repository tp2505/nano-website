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

## The hero video lives on Vimeo, is editable, and cheap on mobile

- **Editable:** Appearance → **Customize → Hero** takes a **Vimeo URL or ID**
  (uploads to CampusPress are capped at 50 MB, so the long hero clip is hosted
  on Vimeo) plus the poster image. Accepts `https://vimeo.com/123456789`,
  unlisted links (`…/123456789/abcdef` or `?h=` URLs), or just the number
  (`inc/customizer.php` → `nano_vimeo_embed_url()`). With no Vimeo set it falls
  back to a previously-uploaded MP4 or the bundled `assets/video/` files, so
  the theme still works out of the box.
- **Background, not a player:** the embed uses `background=1` (plus the
  `controls=0&title=0&byline=0&portrait=0` fallback params for plans where
  `background` isn't honoured) and the frame is `pointer-events: none` — no
  controls, no Vimeo chrome, nothing on hover. Note `background=1` needs the
  video to sit on a **paid Vimeo plan**; on a free plan the fallback params
  still hide the title/byline but controls may remain.
- **Poster-first:** the markup ships only the poster `<img>` (instant first
  paint). `assets/js/nano.js` builds the player iframe after load and fades it
  in over the poster once playback actually starts (Vimeo's postMessage `play`
  event, with a post-load timeout as backstop).
- **Mobile / data-saver / reduced-motion:** the iframe is only ever created on
  non-phone, non-Save-Data, non-reduced-motion loads. On phones the poster
  stands in and **zero** video bytes are fetched. All client-side, so it's
  cache-safe (no server UA sniffing).

## Accessibility notes

One `<h1>` per page; section labels are `<h2>` so heading order never skips.
Decorative accent dots and background video are `aria-hidden`; icon buttons have
`aria-label`s; the constrained Archive Initiative filter is conveyed with
`aria-disabled` + a note; empty states are real text. Images get their `alt` from
the media library; reduced-motion is respected (no autoplay anywhere).

## Development (not shipped)

`../bin/`, `.wp-env.json`, and `package.json` at the repo root are the local
`@wordpress/env` dev workflow; they are not part of the theme zip.
