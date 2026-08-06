# Manual Docs sample data

## Small demo (`manual-docs-demo.xml`)

- Categories in `manualdocumentationcategory`: Getting Started, Platform, Releases, Adapter, AIOps
- Three release parents: **goat**, **flamingo**, **hummingbird**
- Identical tree under each (12 docs × 3 = 36 documents)

## Large demo (`manual-docs-demo-1000.xml`) — version switch testing

- **1002 documents** total (334 × goat / flamingo / hummingbird)
- Identical nested tree under each version root
- Content includes a unique marker per version, e.g. `MD-DEMO-GOAT-…` vs `MD-DEMO-FLAMINGO-…`
- Tree per version:

```
{goat|flamingo|hummingbird}/
├── Getting Started|Platform|Adapters|AIOps|Observability|Security|Automation|Integrations|Release Notes/
│   ├── {Chapter}: Overview|Setup|Operations|Troubleshooting/
│   │   └── 8 topics each (Introduction … Examples)
```

### Import (large file ~2.8 MB)

1. **Tools → Import → WordPress** (install importer plugin if prompted)
2. Upload `manual-docs-demo-1000.xml` (raise PHP `upload_max_filesize` / `post_max_size` to ≥ 8M if needed)
3. Assign author to an admin user
4. **Appearance → Manual Docs** → Version root slugs: `goat,flamingo,hummingbird`
5. Open any goat leaf page and use **Release version** to switch — titles/paths match; body marker changes

Regenerate with: `python3 generate-demo-1000.py`

## Pretty URLs vs index.php

Apache “Not Found” on `/documentation/...` means the web server never forwarded the request to WordPress. Themes cannot fix that alone.

On Local subdirectory installs (`/digidocs/`):

1. Copy `htaccess-digidocs.txt` → `digidocs/.htaccess`
2. Appearance → Manual Docs → **Flush + write .htaccess**
3. Switch Documentation URL mode to **Pretty** (or **Restore pretty URLs**)
4. If it still 404s, Local is ignoring `.htaccess` (nginx / AllowOverride) — keep **index.php URLs**

## Download links (from branch)

- Theme zip: `https://raw.githubusercontent.com/vilokshah/vilokshah/cursor/manual-docs-theme-e7d3/manual-docs-theme.zip`
- Large import: `https://raw.githubusercontent.com/vilokshah/vilokshah/cursor/manual-docs-theme-e7d3/manual-docs/sample-data/manual-docs-demo-1000.xml`
- Small import: `https://raw.githubusercontent.com/vilokshah/vilokshah/cursor/manual-docs-theme-e7d3/manual-docs/sample-data/manual-docs-demo.xml`
