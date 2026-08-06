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
   - Adjust brand colors to match digitate / your brand
   - Confirm login requirement

Your existing Documentation Categories (`manualdocumentationcategory`) and documents stay intact. The theme only registers the CPT/taxonomy if they are missing.

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
- Colors: primary, accent, links, PDF, active tree bar, header/sidebar/content backgrounds
- Version roots (slugs or IDs), switcher label, default version
- Require login, TOC/PDF/updated/edit toggles, community CTA

## Features

- Live search (REST + AJAX)
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
