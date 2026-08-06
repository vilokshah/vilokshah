# Manual Docs sample data

## Small demo (`manual-docs-demo.xml`)

36 documents (12 × goat / flamingo / hummingbird).

## Large demo — version switch testing

**1002 documents** (334 × goat / flamingo / hummingbird) with identical trees.

If the combined file times out in WP Importer, import the three smaller files **in order**:

1. `manual-docs-demo-goat.xml`
2. `manual-docs-demo-flamingo.xml`
3. `manual-docs-demo-hummingbird.xml`

Or the combined file: `manual-docs-demo-1000.xml`

### Download

- Goat only: https://raw.githubusercontent.com/vilokshah/vilokshah/cursor/manual-docs-theme-e7d3/manual-docs/sample-data/manual-docs-demo-goat.xml
- Flamingo only: https://raw.githubusercontent.com/vilokshah/vilokshah/cursor/manual-docs-theme-e7d3/manual-docs/sample-data/manual-docs-demo-flamingo.xml
- Hummingbird only: https://raw.githubusercontent.com/vilokshah/vilokshah/cursor/manual-docs-theme-e7d3/manual-docs/sample-data/manual-docs-demo-hummingbird.xml
- Combined (~2.8 MB): https://raw.githubusercontent.com/vilokshah/vilokshah/cursor/manual-docs-theme-e7d3/manual-docs/sample-data/manual-docs-demo-1000.xml

### After import

1. Appearance → Manual Docs → Version root slugs: `goat,flamingo,hummingbird`
2. Left tree should list **Goat**, **Flamingo**, and **Hummingbird** as top-level roots
3. Open a goat leaf → Release version switcher (right of title) → Flamingo — body marker changes to `MD-DEMO-FLAMINGO-…`
4. Previous/Next walks **depth-first** (first child before next sibling)

Regenerate: `python3 generate-demo-1000.py`
