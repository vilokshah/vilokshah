# LYNX MTF — how the working setup behaves

The 3 Aug script is the strategy. Entry / SL (0.5× pattern **inside** the box) / TP multiples are unchanged.

Runtime fixes in `LYNX_MTF.pine` (not rule changes):

1. **SL wipe** — levels stayed on the chart after SL because (a) TP-run invalidation set `s_live=false` and then **stopped checking SL**, so a later SL tag did nothing, and (b) deleted line IDs were not reset to `na`. SL now always clears entry/SL/TP drawings and returns status to **Scanning**.
2. **Missed reversals** — a new pattern could not confirm while a trade was live (`not s_live`). Same-bar tick re-arm could also skip or flash a trade. Confirms are once per bar; a new confirmed reversal **replaces** old levels. Swing high/low is judged on the **completed** pattern bar so the current wick cannot cancel it.
3. **Dashboard** — trades/wins/losses incremented **on every tick** of the confirm/SL bar, and the calendar day mixed UTC `timenow` with exchange bar dates. Counts are once per event, using the chart timezone. Wins are not counted twice if SL is tagged after a TP-run close.

## Flow

1. Chart must be 15 / 60 / 240 minutes.
2. Confirm TF: 15m→5m, 1H→15m, 4H→1H.
3. On closed bars, detect hammer, shooting star, engulfing, morning/evening star, tweezer.
4. Pattern must be the lowest low / highest high of the last `swingLen+1` bars (default 6).
5. Box the pattern, wait for lower TF.
6. Confirm when any of the last three **completed** lower-TF candles is in-direction and closes beyond pattern high (buy) or low (sell).
7. One live trade. Grey TPs as they are tagged. SL after TP1 = win. Close back through 50% of the last TP zone = win (drawings stay). SL first = loss (drawings wiped).
8. Dashboard: status, today’s signals/wins/losses, distance to plotted BSL/SSL.

## Trade math (intentional)

```
range = patternHigh - patternLow

BUY  entry = patternHigh
BUY  SL    = patternHigh - range * 0.5    // inside the box
BUY  TPn   = patternHigh + range * n

SELL entry = patternLow
SELL SL    = patternLow + range * 0.5     // inside the box
SELL TPn   = patternLow - range * n
```

R:R to TP1 is 1:2 because `range / (0.5 × range) = 2`.

## BSL / SSL

Daily series: 20-bar highest high / lowest low, drawn as BSL / SSL. Distance is shown on the dashboard. Patterns are not required to touch those lines.

## Do not change

- SL inside the pattern box at 0.5× range
- TP ladder as multiples of that same range
- Pattern definitions and swing lookback
- Lower-TF confirmation
- Win/loss rules on SL vs TP
