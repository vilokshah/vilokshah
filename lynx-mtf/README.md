# LYNX MTF

Standalone TradingView indicator. The strategy is the **3 Aug working setup**, unchanged.

Paste `LYNX_MTF.pine` into the Pine Editor (v6). Use only on **15m, 1H, or 4H**. Same signals as the 3 Aug setup; SL hit still clears leftover levels.

| File | What it is |
|------|------------|
| `LYNX_MTF.pine` | The live indicator — same rules as the 3 Aug script |
| `LYNX_MTF_original.pine` | Snapshot of that script |
| `ANALYSIS.md` | How the setup works (no rule changes) |

No other scripts are required.

## How to load

1. TradingView → Pine Editor → open `LYNX_MTF.pine`
2. Add to chart
3. Chart timeframe must be 15m, 1H, or 4H

Confirm timeframe:

- 15m chart → **5m**
- 1H chart → **15m**
- 4H chart → **1H**

## Trade levels (locked)

Pattern box = pattern high − pattern low (`range`).

**Buy**

- Entry = pattern high
- SL = `entry − range × 0.5` (midpoint of the box)
- TP1 = `entry + range`, TP2 = `entry + 2×range`, … TP8

**Sell**

- Entry = pattern low
- SL = `entry + range × 0.5` (midpoint of the box)
- TP1 = `entry − range`, then the same steps down

Entry is armed only after a lower-TF candle closes through the pattern high (buy) or low (sell).
