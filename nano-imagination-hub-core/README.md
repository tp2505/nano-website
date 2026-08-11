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
| **News** | `news` | Editorial feed | `nano_date`, media (`nano_media_type`/`nano_image`/`nano_video`/`nano_poster`), `nano_initiative`, `nano_related`, `nano_people`; `news_category` taxonomy |
| **Event** | `event` | A happening under one Initiative | `nano_date`, `nano_description`, `nano_gallery` (ACF Pro Gallery: images + videos; caption/alt and the per-video **poster** live on the attachment, edited in the Gallery sidebar), `nano_initiative`, `nano_related`, `nano_people` |
| **Class** | `class` | A course (Pedagogies only) | `nano_term` (**required**, "Fall 2026"), `nano_department`, `nano_instructor`, `nano_ta`, `nano_level`, `nano_credits`, `nano_description`, `nano_syllabus_file` (PDF) / `nano_syllabus_link`, `nano_related`, `nano_people` |
| **Initiative** | `initiative` | The four strands | `nano_descriptor`, `nano_order`, `nano_intro`, media |
| **Person** | `person` | Everyone — Hub team **and** participants | `nano_role`, `nano_photo`, `nano_bio`; `people_group` taxonomy (About-page groups) |

### Relationships & the reverse-lookups

- **Content → people:** Events / News / Classes point at the people involved via
  `nano_people` (relationship → `person`).
- **Person → content (reverse):** a Person page lists everything that references
  it, and the Participants page lists everyone referenced by anything. These are
  computed in PHP (`inc/people.php` → `nano_person_related_content()`,
  `nano_participant_person_ids()`) by reading each item's `nano_people` array —
  not a serialized-meta `LIKE` query — so they're correct whether the value was
  written by ACF (string IDs) or programmatically (int IDs).
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

`assets/editor.js` registers the same blocks with the block editor's JavaScript
registry (with a live server-side-rendered preview) — without it the editor
reports them as unsupported even though the front end renders them fine. The
block list is injected from the PHP registry, so `block.json` remains the
single source of truth and no build step is needed.

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

## Seeding

Demo content is created by a WP-CLI seeder that lives in the separate
development repository; it is a dev tool and is **not** part of this plugin.
