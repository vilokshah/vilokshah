# Manual Docs sample data

File: `manual-docs-demo.xml`

## What it contains

- Categories in `manualdocumentationcategory`: Getting Started, Platform, Releases, Adapter, AIOps
- Three release parents: **goat**, **flamingo**, **hummingbird**
- Identical tree under each (12 docs × 3 = 36 documents):

```
{goat|flamingo|hummingbird}/
├── Getting Started/
│   ├── Installation/
│   │   ├── System Requirements
│   │   └── Quick Install
│   └── First Login
├── Platform Guides/
│   ├── Business Health Monitoring/
│   │   ├── Overview
│   │   └── Key Features
│   └── Intelligent Command Center
└── Release Notes
```

## Import steps

1. Install the WordPress Importer plugin if needed (**Tools → Import → WordPress**)
2. Upload `manual-docs/sample-data/manual-docs-demo.xml`
3. Assign imported author to an admin user
4. **Appearance → Manual Docs** → Version root slugs: `goat,flamingo,hummingbird`
5. **Settings → Permalinks → Save**
6. Open any goat document and use **Release version** to switch to flamingo / hummingbird
