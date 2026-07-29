# MIT Imagination Hub — WordPress theme + companion plugin

Custom WordPress site for the **MIT Imagination Hub** (STUDIO.nano): a block
theme that owns all presentation, and a companion plugin that owns all content
types and data. This repo contains exactly the two deployables plus this
README — no dev tooling.

## What's in this repo

| Folder | What it is |
|---|---|
| [`mit-imagination-hub/`](mit-imagination-hub/) | **THEME.** Presentation-only block theme (FSE), derived from Twenty Twenty-Four. Templates, template parts, patterns, `theme.json` design tokens, stylesheet, self-hosted fonts (Space Grotesk, bundled woff2 — no external font calls), and the hero section with a Customizer-editable video/poster. Contains no content logic. |
| [`nano-imagination-hub-core/`](nano-imagination-hub-core/) | **PLUGIN** (companion). Registers the entire content model: post types `news`, `event`, `class`, `person`, `initiative` (plus `facility`); the News-category and People-group taxonomies; the ACF field groups (registered in code); and the server-rendered blocks that query them (news feed, initiatives list, archive, single Event/Class/Person pages, About, Participants, Facilities grid, Related section, card renderer). Kept separate from the theme so content survives a theme switch. |

Each folder has its own README with full details:
[theme README](mit-imagination-hub/README.md) ·
[plugin README](nano-imagination-hub-core/README.md).

## Dependencies

- **ACF Pro** (Advanced Custom Fields) — must be active; the plugin registers
  its field groups in code (`nano-imagination-hub-core/inc/fields-acf.php`) and
  reads every custom field through a single accessor (`nano_field()`), so no
  ACF admin import is needed.
- WordPress ≥ 6.5, PHP ≥ 7.4.

## Install order

1. Activate **ACF Pro** (already available on CampusPress).
2. Activate the companion plugin **`nano-imagination-hub-core`**.
3. Activate the theme **`mit-imagination-hub`**.

Content (news, events, classes, people, initiatives, facilities) is then
managed entirely in wp-admin; editorial copy on the homepage (hero tagline,
mission statement, newsletter intro), the hero video/poster, the contact
email, and the social links are managed under **Appearance → Customize**.

## CampusPress compliance notes

- **Presentation-only theme + self-contained plugin** — clean separation of
  concerns; the theme renders, the plugin models.
- **No external calls at runtime** — fonts are self-hosted; no CDNs, trackers,
  or third-party embeds.
- **No direct database or filesystem access** — everything goes through
  WordPress APIs (`WP_Query`, options, post meta via the ACF accessor).
- **Accessible** — semantic landmarks, skip navigation, focus states, reduced-
  motion guards on all animation/hover effects, WCAG-checked contrast.
- **Lightweight** — one stylesheet, one small vanilla-JS file, inline SVG line
  art; no build step and no bundled frameworks.
