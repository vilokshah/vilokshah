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

### Gutenberg categories & parent

The theme forces `show_in_rest` + `hierarchical` + `page-attributes` on the Manual CPT/taxonomy so the block editor shows **Categories** and **Parent**. After activating, save Permalinks once. If categories still do not appear, deactivate conflicting Manual plugins temporarily and re-save the document.

### Demo content

Import `sample-data/manual-docs-demo.xml` via **Tools → Import → WordPress** to load goat / flamingo / hummingbird trees with nested children. See `sample-data/README.md`.

### PDF download

PDF uses the document permalink with `?manual_docs_pdf=1` (and optional `autoprint=1`). No special rewrite slug required.

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
- Colors: primary, accent, links, PDF, active tree bar, header/sidebar/content backgrounds
- Version roots (slugs or IDs), switcher label, default version
- Require login, TOC/PDF/updated/edit toggles, community CTA
- Light/dark toggle on the front-end header
- Collapsible documentation tree (‹ arrow) to widen the content column
- TOC Hide/Show on the right “On this page” panel

## Live search

Configured as a REST endpoint that queries `manual_documentation` posts (optional version root scope), with an AJAX fallback.

- Endpoint: `GET /wp-json/manual-docs/v1/search?q=…&version={slug|id}`
- Used on the homepage hero, docs sidebar, and anywhere via shortcode:

```
[manual_docs_search]
[manual_docs_search placeholder="Search docs…"]
```

Also aliased as `[manual_docs_live_search]`.

## Features

- Live search (REST + AJAX) + shortcode
- AJAX document loading with History API
- PDF print/download view
- Role-based category access (Allowed Roles on category edit)
- bbPress community templates + CTA
- Security hardening

## REST

- `GET /wp-json/manual-docs/v1/search?q=…&version=goat`
- `GET /wp-json/manual-docs/v1/doc/{id}`

## License

GPL v2 or later.
