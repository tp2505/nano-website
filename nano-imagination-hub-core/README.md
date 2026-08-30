# Nano — Imagination Hub Core (companion plugin)

The **data + logic** half of the MIT Imagination Hub site. It owns every content
type, custom field, and the server-rendered blocks that present them. The
companion **`mit-imagination-hub`** theme is presentation-only — all content
lives here so it survives a theme switch, which is also why this is a plugin and
not theme code.

**Only external dependency:** Advanced Custom Fields **Pro** (confirmed available
on CampusPress). Fields are read through an isolation accessor (`nano_field()` in
the theme's `inc/fields.php`) that falls back to native post meta, so nothing
breaks hard if ACF is ever unavailable. No database, filesystem, or network
access; no super-admin requirements.

## Content types (`inc/cpt.php`)

| Type | Slug | Purpose | Key fields (ACF, `inc/fields-acf.php`) |
|---|---|---|---|
| **News** | `news` | Editorial feed | `nano_date`, the standard media slot (below), `nano_initiative`, `nano_related`, `nano_people`; `news_category` taxonomy |
| **Event** | `event` | A happening under one Initiative | `nano_date`, the standard media slot **+ Vimeo** (below), `nano_description`, `nano_gallery` (repeater: media + poster + **caption**), `nano_initiative`, `nano_related`, `nano_people` |
| **Class** | `class` | A course (Pedagogies only) | `nano_term` (**required**, "Fall 2026"), `nano_department`, `nano_instructor`, `nano_ta`, `nano_level`, `nano_credits`, the standard media slot (below), `nano_description`, `nano_syllabus_file` (PDF) / `nano_syllabus_link`, `nano_related`, `nano_people` |
| **Initiative** | `initiative` | The four strands | `nano_descriptor`, `nano_intro`, the standard media slot (below); display order via **menu_order** (below) |
| **Person** | `person` | Everyone — Hub team **and** participants | `nano_role`, `nano_photo`, `nano_bio`; `people_group` taxonomy (About-page groups); display order via **menu_order** (below) |

### One media rule

Every media slot takes a **still image or a short looping clip** (uploaded
MP4/WebM with an optional poster still): `nano_media_type` / `nano_image` /
`nano_video` / `nano_poster`, read through `nano_media()` and rendered by
`nano_render_media()` in the theme's `inc/fields.php`, so the markup lives in
exactly one place. Clips autoplay muted, loop, and are lazy-managed by the
front-end script; gallery items stay click-to-play behind their poster still.

The one exception: the **event top slot** also accepts a **Vimeo URL**
(`nano_media_type = vimeo` + `nano_vimeo`) for long-form video — e.g.
Resonances lecture recordings, which exceed CampusPress's 50 MB upload cap. It
renders as a watchable 16:9 player with native controls but the Vimeo chrome
(title/byline/portrait) hidden, `dnt=1`, no autoplay. Unlisted links work.

On the single event page the slot sits below the date, **separate from the
Featured image** (which stays the cropped card thumbnail in listings), and
renders only when explicitly filled. Stills — announcement posters are
landscape — fill the column at their own ratio, never cropped; an unusually
tall upload is height-capped and letterboxed on pure white. Gallery media gets
the same fit-not-crop treatment inside its fixed 16:10 tiles; cards and the
class banner keep cropping (`cover`) so grids and banners stay even. The class
banner falls back to the featured image when the slot is empty.

### Manual display order (person, initiative, facility)

These three types order by WordPress's native **menu_order** — the **Page
Attributes → Order** box on the edit screen, plus a sortable "Order" column in
their admin lists (`inc/cpt.php`). People lists (About groups, Participants)
show explicitly-ordered people first (1, 2, 3…), then everyone left at 0
alphabetically by last name — so a director can be pinned first without having
to number the whole roster (`inc/people.php` → `nano_sort_people()`). The
homepage/archive initiative sequence follows the same box; the old ACF
`nano_order` field is gone and existing values were migrated by
`inc/upgrade.php`.

### Relationships & the reverse-lookups

- **Related content is two-way:** `nano_related` (Events ↔ News ↔ Classes) uses
  ACF's bidirectional setting — linking A → B also writes B → A, so the Related
  section works from both sides. Links that predate this were made two-way once
  by `inc/upgrade.php` (version-gated on `nano_core_upgraded`, runs on the first
  wp-admin load after update; idempotent).
- **Content → people:** Events / News / Classes point at the people involved via
  `nano_people` (relationship → `person`).
- **Person → content (reverse):** a Person page lists everything that references
  it, and the Participants page lists everyone referenced by anything. These are
  computed in PHP (`inc/people.php` → `nano_person_related_content()`,
  `nano_participant_person_ids()`) by reading each item's `nano_people` array —
  not a serialized-meta `LIKE` query — so they're correct whether the value was
  written by ACF (string IDs) or the seeder (int IDs).
- **General news:** a News item with **no** `nano_initiative` is "General" — it
  shows on the homepage and in the Archive but on no Initiative page.
- **Classes → Pedagogies:** classes carry no initiative field; they belong to
  Pedagogies by design. `inc/classes.php` derives a sortable key from the term
  string ("Fall 2026" → `20263`) that drives both the current-vs-past split on
  the Pedagogies page and the term grouping in the Archive.

## Blocks (`blocks/*`, server-rendered)

`news-feed`, `initiatives-list`, `initiative-page`, `initiatives-archive`,
`project-page` (single News), `event-page`, `class-page`, `person-page`, `about`,
`participants-list`, `facilities-grid`, `page-heading`, `related`. Each is a
`block.json` + `render.php`; they run `WP_Query` against the post types so pages
are data-driven, never hardcoded. The `related` block is reused by single News,
Event, and Class pages.

## The Archive filter (`blocks/initiatives-archive` + theme `assets/js/nano.js`)

One page, three combinable, URL-settable filters (`?ftype=`, `?finit=`,
`?fyear=` — namespaced so they don't collide with WordPress' own `initiative`
and `year` query vars):

- **Type** — All / News / Events / **Classes**
- **Initiative** — All / the four / General
- **Year**

Filtering is client-side (AND logic); the server sets the dropdowns' initial
state from the URL so links deep-link into a pre-filtered view. "View all" links
on the Initiative pages point here (e.g. `?ftype=events&finit=resonances`).

**The Classes → Pedagogies constraint:** because classes are Pedagogies-only,
selecting **Type = Classes** would make any other Initiative return nothing. So
the JS *disables* the Initiative dropdown (native `disabled` + `aria-disabled` +
an explanatory note) and pins it to Pedagogies — the impossible combination is
prevented, not just tolerated. In Classes mode the results render **grouped by
term, current → past**. Any empty combination shows a real, screen-reader-
readable empty-state message.

## Seeding (dev only, not shipped)

`../bin/seed-media/_seed.php` (run via `wp eval-file`) creates demo content. It
is a development tool and is **not** part of the plugin zip.
