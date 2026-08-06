# Manual Docs — WordPress Theme

A secure, lightweight documentation theme inspired by Manual. Built for knowledge bases that need versioned docs, live search, PDF export, role-based category access, and bbPress community forums.

## Features

- **`manual_documentation` CPT** — hierarchical docs with archive at `/docs/`
- **Categories** — `doc_category` taxonomy with optional per-category role access
- **Versions** — `doc_version` taxonomy + version group keys for cross-version switching
- **Live search** — REST + AJAX search with keyboard navigation (`/` to focus)
- **PDF download** — print-optimized view per document (`/docs/{slug}/pdf/`)
- **Login gate** — guests redirected to WordPress login before viewing docs (Customizer toggle)
- **Role-based access** — restrict categories to specific roles
- **bbPress ready** — community templates, sidebar, and in-doc forum CTA
- **Security hardening** — headers, XML-RPC off by default, author enumeration block, sanitized AJAX/REST

## Requirements

- WordPress 6.0+
- PHP 7.4+
- Optional: [bbPress](https://bbpress.org/) for forums

## Installation

1. Copy the `manual-docs` folder into `wp-content/themes/`
2. Activate **Manual Docs** under Appearance → Themes
3. Visit Settings → Permalinks and click Save (flushes rewrite rules)
4. Optionally install and activate bbPress

## Setup guide

### 1. Create documentation

- Go to **Documentation → Add New**
- Assign **Categories** and **Versions**
- Set **Version Group Key** (Document Settings meta box) to the same value across versions of the same article so the version switcher can link them
- Mark Featured to highlight on the home page

### 2. Categories & roles

Edit a Doc Category and set **Allowed Roles** (comma-separated slugs), e.g. `subscriber,contributor`.

- Empty = all logged-in users (when login is required)
- Admins always have access

### 3. Login requirement

Appearance → Customize → **Manual Docs Options**:

- Require login to view documentation
- Hero title / subtitle
- Accent color
- Community CTA toggle

### 4. Menus

Assign menus to:

- Primary
- Documentation Sidebar
- Footer
- Community

### 5. PDF downloads

On each document, use **Download PDF**. That opens a print view; choose “Save as PDF” in the browser (or use `?autoprint=1`).

### 6. bbPress

Activate bbPress, create forums, and assign the Community menu. The theme styles forums and shows a “Visit Forums” CTA on documents.

## Theme structure

```
manual-docs/
├── assets/css|js
├── bbpress.php
├── front-page.php
├── single-manual_documentation.php
├── archive-manual_documentation.php
├── taxonomy-doc_category.php
├── taxonomy-doc_version.php
├── inc/
│   ├── cpt.php
│   ├── access-control.php
│   ├── versioning.php
│   ├── live-search.php
│   ├── pdf-download.php
│   ├── bbpress.php
│   ├── security.php
│   ├── customizer.php
│   └── helpers.php
└── template-parts/
```

## REST API

`GET /wp-json/manual-docs/v1/search?q=keyword&version=1-0`

Respects login and category role restrictions.

## License

GPL v2 or later.