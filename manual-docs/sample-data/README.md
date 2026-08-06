# Manual Docs sample data

## Small demo (`manual-docs-demo.xml`)

36 documents (12 × goat / flamingo / hummingbird).

## Large demo — version switch testing

**1002 documents** (334 × goat / flamingo / hummingbird) with identical trees and titles.

### Important: import order + theme version

WordPress Importer skips same titles by default. **Manual Docs v2.6.1+** includes an import filter so flamingo/hummingbird can share titles with goat.

1. Install/update the theme zip **first**
2. Import goat, then flamingo, then hummingbird (separate files are more reliable than the combined XML)

- Goat: https://raw.githubusercontent.com/vilokshah/vilokshah/cursor/manual-docs-theme-e7d3/manual-docs/sample-data/manual-docs-demo-goat.xml
- Flamingo: https://raw.githubusercontent.com/vilokshah/vilokshah/cursor/manual-docs-theme-e7d3/manual-docs/sample-data/manual-docs-demo-flamingo.xml
- Hummingbird: https://raw.githubusercontent.com/vilokshah/vilokshah/cursor/manual-docs-theme-e7d3/manual-docs/sample-data/manual-docs-demo-hummingbird.xml

Then set version root slugs to `goat,flamingo,hummingbird`.

Regenerate: `python3 generate-demo-1000.py`
