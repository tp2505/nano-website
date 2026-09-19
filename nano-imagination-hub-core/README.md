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

Event, news, class, and initiative use the **classic editor** (per-type filter in
`inc/cpt.php`): their structure lives in ACF groups, and the classic screen
keeps those groups — including the event Headline group placed directly after
the title — reachable at any content length. Pages and everything else keep
the block editor.

| Type | Slug | Purpose | Key fields (ACF, `inc/fields-acf.php`) |
|---|---|---|---|
| **News** | `news` | Editorial feed — same page structure as an event (title → subtitle → participants → date/venue → page image → body → sponsors → People/Related; no gallery) | Headline group: `nano_subtitle` + `nano_authors` (the credit line — who the work is by; inherits from the linked event). Details: `nano_event` (**linked event — the inheritance source**: any blank field below shows the event's value on the news page, a filled field overrides it; the date/venue line renders the event's "when" as a unit and links to it), `nano_date` (**required** — the feed's display/sort date; also the start date when unlinked), `nano_date_end`/`nano_time_start`/`nano_time_end`/`nano_venue` (for unlinked news), `nano_page_image` (free ratio), the media slot (card thumbnail; a clip also plays on the page), `nano_sponsors`, `nano_attachments` (Documents, inherits from the linked event), `nano_initiative`, `nano_related`; `news_category` taxonomy. Body = post content (blank + linked → the event's description + long-form; excerpt is the last resort) |
| **Event** | `event` | A happening under one Initiative | Headline group (after the title on the classic edit screen): `nano_subtitle` + `nano_authors` (the credit line under the subtitle — who the work is BY: the speaker, the artists). `nano_people` (details group) is everyone ELSE involved, shown with photos/roles in the People section; both count toward person pages and the Participants list. Details: `nano_date` (start, **required**), `nano_date_end`, `nano_time_start`/`nano_time_end` (daily times; `nano_event_when()` renders US-formatted, range-collapsed strings like "May 28 – 30, 2026, 9:00 am – 5:00 pm"), `nano_venue`, `nano_page_image` (**free-ratio** page-top image at body width — deliberately not the 16:9 listing frame), the media slot (image = card thumbnail; clip/Vimeo also play on the page), `nano_description` (card/listing teaser only — never renders on the page), post content editor (the page body), `nano_sponsors` (same repeater as Support-us, logos `contain` below the body), `nano_attachments` (**Documents**: repeatable PDF + optional label + optional thumbnail, shown above the gallery — thumbnails are manually uploaded only (no automatic first-page render — long single-page PDFs would produce unusable strips); without one the document is a plain labelled download link; free-ratio thumbnails at consistent height), `nano_strip` (**vertical image strip**: one horizontally-scrolling row of 9:16 panels below the body — originally Correlations' twelve-channel corridor screen, any count; a partial panel peeks at the right edge so the scroll is discoverable, and clicking opens the site's shared lightbox: Esc/arrows/Tab-trap/swipe, reusable via the `[data-nano-lightbox]` markup contract), `nano_gallery` (ACF Pro Gallery: ordered attachment IDs; captions/alt on the attachment, per-video poster via the attachment nano_poster field), `nano_initiative`, `nano_related` |
| **Class** | `class` | A course (Pedagogies only) | Headline group (after the title on the classic edit screen): `nano_subtitle` — same treatment as events/news, no Authors (instructors are the credit). Details: `nano_term` (**required**, "Fall 2026"), post content editor (the page body, optional), `nano_department`, `nano_instructor` (multiple — co-instructors render comma-separated), `nano_ta` (plain text — TAs don't get People pages; legacy references were migrated to names), `nano_level`, `nano_credits`, the standard media slot (banner), `nano_description` (card/listing teaser only — never renders on the page), `nano_syllabus_link` (external — renders as the first row of the Documents band, labelled "Syllabus", link-only style; uploaded syllabus PDFs live in `nano_attachments` — migrated by `inc/upgrade.php` 0.6.0), `nano_attachments` (Documents), `nano_gallery` (the shared two-up documentation gallery — same field and rendering as events, click-to-play videos included, above People/Related), `nano_related`, `nano_people`. Page order: title → subtitle → banner → facts → instructors/TA → body → documents → gallery → People/Related |
| **Initiative** | `initiative` | The four strands | `nano_descriptor`, `nano_intro` (homepage-row teaser only — never renders on the initiative page), post content editor (the page body), the standard media slot (below); display order via **menu_order** (below) |
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
Featured image** (which stays the card thumbnail in listings), and renders
only when explicitly filled. Every editorial image container sitewide — the
event slot, gallery tiles, cards, the class banner, the news feature,
facility and initiative tiles — is a **fixed 16:9 frame with the media
filling it (`object-fit: cover`)**, so layouts stay stable regardless of
what's uploaded. Person photos (4:5) and the About banners are the deliberate
exceptions. The class banner falls back to the featured image when the slot
is empty.

### Manual display order (person, initiative, facility)

These three types order by WordPress's native **menu_order** — the **Page
Attributes → Order** box on the edit screen, plus a sortable "Order" column in
their admin lists (`inc/cpt.php`). The About-page groups
show explicitly-ordered people first (1, 2, 3…), then everyone left at 0
alphabetically by last name — so a director can be pinned first without having
to number the whole roster (`inc/people.php` → `nano_sort_people()`); the Participants directory is purely
alphabetical by last name. The
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
