# Manual Docs — WordPress Theme (v2)

Drop-in replacement theme for sites already using the Manual documentation stack (`manual_documentation` CPT + `manualdocumentationcategory`).

## What’s different in v2

- **Keeps your data** — does not invent a new category taxonomy; uses `manualdocumentationcategory`
- **Release versions = parent pages** — e.g. `goat`, `flamingo`, `hummingbird` (same tree, different content)
- **Docs chrome like product docs** — left search + tree only; content title + updated/PDF/edit meta; release version switcher; right “On this page” TOC
- **Appearance → Manual Docs** — full settings for colors, branding, version roots, login gate, chrome toggles
- **AJAX browsing** — tree / pager / search / version switch without full reloads

## Install (theme swap)

1. Upload / unzip `manual-docs` into `wp-content/themes/`
2. Activate **Manual Docs** (deactivate the old Manual theme)
3. Settings → Permalinks → Save
4. Appearance → **Manual Docs**:
   - Set **Version root slugs** to your parent docs, e.g. `goat,flamingo,hummingbird`
   - Adjust brand colors (defaults are a darker docs palette)
   - Confirm login requirement

Your existing Documentation Categories (`manualdocumentationcategory`) and documents stay intact. The theme only registers the CPT/taxonomy if they are missing.

### Static homepage

`front-page.php` respects **Settings → Reading → A static page**. Assign any page as the homepage. To use the DigiDocs search portal layout, edit that page and set Template = **Docs Portal**.

### Hide /docs archive

Enabled by default (**Hide /docs archive page**). Visiting `/docs/` redirects to the default release. Link users to specific version URLs (or homepage CTAs) instead.

### Gutenberg categories & parent

The theme forces `show_in_rest` + `hierarchical` + `page-attributes` on the Manual CPT/taxonomy so the block editor shows **Categories** and **Parent**. After activating, save Permalinks once. If categories still do not appear, deactivate conflicting Manual plugins temporarily and re-save the document.

### Demo content

Import `sample-data/manual-docs-demo.xml` via **Tools → Import → WordPress** to load goat / flamingo / hummingbird trees with nested children. See `sample-data/README.md`.

### PDF download

PDF uses the document permalink with `?manual_docs_pdf=1` (and optional `autoprint=1`). Print view includes document images (absolute URLs, lazy-load unwrapped), a **Digitate Docs** watermark, and repeating header/footer on each page.

## Version switching model

```
goat/                      ← version root
  ├── installing-platform
  └── business-health-monitoring
flamingo/                  ← version root (same titles / structure)
  ├── installing-platform
  └── business-health-monitoring
```

Switcher finds the matching doc under another root by relative slug path, then title.

## Admin settings

**Appearance → Manual Docs**

- Brand name, home hero copy, footer text
- **Dark + light logos** — separate uploads that swap with the theme toggle (fallback: Site Identity logo)
- **Heading / body font families** — curated Google Fonts + System UI
- Colors: separate **Dark theme colors** and **Light theme colors** pickers (accents, backgrounds, text)
- Version roots (slugs or IDs), switcher label, default version
- Require login, TOC/PDF/updated/edit toggles, community CTA
- Light/dark toggle on the front-end header (both themes are fully styled)
- Collapsible documentation tree (‹ arrow) to widen the content column
- TOC Hide/Show expands the content column (same idea as the tree toggle)
- Docs tree chrome: favicon left, panel toggle + search icons right; search opens a centered modal so long titles stay readable
- **Docs Stats** (Appearance → Docs Stats) — top visited documents + frequent searches
- Homepage Docs Portal — brand + search only (no category/version grids)
- **Version diff** (optional) — Compare versions summary-first across goat/flamingo/hummingbird; one admin toggle
- **Large libraries** — tree defaults to active version only + lazy-load children; reading-order cache for prev/next
- **Customizable footer** — 4 widget columns (Contacts / Company / Support / Stay Connected) + copyright bar

## Footer setup

Appearance → **Widgets**:

1. **Footer Column 1 — Contacts** → add widget **Manual Docs: Contacts** (address + social URLs)
2. **Footer Column 2 — Company** → Navigation Menu (or Custom HTML / app badge image)
3. **Footer Column 3 — Support** → Navigation Menu (policies / support links)
4. **Footer Column 4 — Stay Connected** → **Manual Docs: Newsletter** (set Mailchimp/ESP form action URL + privacy link)

Copyright text and Subscribe button color are under Appearance → Manual Docs.

## Gutenberg vs Classic Editor

**Recommendation:** use the **block editor (Gutenberg)** for documentation when you can.

| Element | Best tool |
| --- | --- |
| Tables (add/remove columns) | Core **Table** block |
| Images, galleries, embeds | Core media blocks |
| Headings, lists, quotes | Core blocks |
| Code (line numbers + Copy on front) | Core **Code** block — theme enhances display |
| Accordion / Tabs / Callouts | **Content Elements** sidebar panel (inserts shortcodes) |

The theme already enables REST for `manual_documentation`. If Gutenberg previously failed (“Updating failed” / invalid JSON), update to this theme, flush permalinks, and temporarily disable conflicting Manual plugins. If you still prefer Classic Editor, keep it — the **Content Elements** panel works there too (TinyMCE “Elements” button focuses the panel).

## Content Elements panel

On every documentation edit screen (sidebar):

- Callout builder
- Accordion builder (add items)
- Tabs builder
- Code block inserter
- Table starter (columns × rows + headers)

Front-end automatically adds **line numbers + Copy** to code blocks and horizontal scroll wrappers for wide tables.

Configured as a REST endpoint that queries `manual_documentation` posts (optional version root scope), with an AJAX fallback.

- Endpoint: `GET /wp-json/manual-docs/v1/search?q=…&version={slug|id}`
- Used on the homepage hero, docs search modal, and anywhere via shortcode:

```
[manual_docs_search]
[manual_docs_search placeholder="Search docs…"]
```

Also aliased as `[manual_docs_live_search]`.

## Scaling to 20,000+ documents

Recommended settings (Appearance → Manual Docs):

1. **Tree scope = Active release only** — never render goat+flamingo+hummingbird trees at once
2. **Lazy-load tree children** — collapsed branches fetch children on expand via `/wp-json/manual-docs/v1/nav-children`
3. Use the **release version switcher** + **centered search** to jump across long titles
4. Keep object/page caching (host Redis/Varnish) and a MySQL host sized for your post volume

Prev/Next reading order is cached per version root (12h, busted on doc save).

## Security (production)

Theme controls (complement your magic-link login plugin; do not replace WP hardening):

| Control | Status |
| --- | --- |
| Docs login gate | Yes (`Require login`) |
| Category Allowed Roles | Yes (checkbox UI) |
| REST/AJAX access checks + nonces | Yes |
| Hide WP generator / XML-RPC off on front | Yes |
| `nosniff`, `SAMEORIGIN`, referrer policy | Yes |
| CSP / HSTS / WAF / brute-force | **Not in theme** — use host + security plugin |
| File upload / auth / updates | **WordPress core + your plugins** |

**Production-ready with:** login plugin + HTTPS + keep WP/plugins updated + security plugin (rate limit / firewall) + `Require login` on. Theme alone is not a full security stack.

## Features

- Live search (REST + AJAX) + shortcode
- AJAX document loading with History API
- PDF print/download view
- Role-based category access (Allowed Roles checkboxes on category edit)
- bbPress community templates + CTA (native `forum` / `topic` / `reply` CPTs)
- Content shortcodes: tabs, accordion, callouts (`[md_tabs]`, `[md_accordion]`, `[md_note]`…)
- Docs Stats admin + kitchen-sink sample installer
- Security hardening

## bbPress / community (`forum`, `topic`, `reply`)

Yes — this is bbPress-friendly. Unlike `manual_documentation`, the theme does **not** re-register community post types.

1. Install and activate **bbPress** (plus any companion plugins you already use).
2. Your existing `post_type=forum|topic|reply` data is used as-is by bbPress.
3. Save **Settings → Permalinks** once after activating.
4. The theme provides `bbpress.php` chrome + `assets/css/bbpress.css` styling for light and dark; optional Community menu + Community sidebar widgets.

You do **not** need a Manual-style CPT migration for forums. If a page looks unstyled, flush permalinks and confirm bbPress is active.

## REST

- `GET /wp-json/manual-docs/v1/search?q=…&version=goat`
- `GET /wp-json/manual-docs/v1/doc/{id}`

## License

GPL v2 or later.
