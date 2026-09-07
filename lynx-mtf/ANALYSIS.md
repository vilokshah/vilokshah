# LYNX MTF — analysis of the original script

This is only about the 3 Aug `LYNX MTF` code you pasted. Nothing else.

## What the original script does (bar by bar)

1. **Refuses** any chart that is not 15 / 60 / 240 minutes.
2. Maps confirm TF: 15m→5m, 1H→15m, 4H→1H.
3. On each closed chart bar, checks `[1]` (and `[2]`/`[3]` for multi-candle) for hammer, shooting star, engulfing, morning/evening star, tweezer.
4. Keeps the pattern only if that bar is the **lowest low / highest high of the last `swingLen+1` bars** (default 6).
5. Stores pattern high/low, shows a dashed box, sets wait-for-LTF.
6. Confirms when any of the last three **completed** lower-TF candles is in-direction **and** closes beyond pattern high (buy) or low (sell).
7. Draws entry, SL, TP1–TP8, midlines. One live trade at a time.
8. Greys TPs as price tags them. SL after TP1 counts as a **win**. Close back through 50% of the last TP zone also counts as a win (drawings stay). SL first = loss (drawings wiped).
9. Dashboard: status, today’s signals/wins/losses, distance to the plotted BSL/SSL.

That flow (pattern → wait LTF → one trade → TP ladder) is solid and is kept in the enhanced file.

## Where it does not match the intended rules

### 1. “The move” is the pattern box, not the swing that led into it

Intended example: 200-pip sell-off, hammer at the bottom → TP **200 pips** the other way, SL **100 pips**.

Original math:

```
range = patternHigh - patternLow
BUY  entry = patternHigh
BUY  SL    = patternHigh - range * 0.5   // midpoint of the hammer
BUY  TP1   = patternHigh + range         // one hammer-height, not 200 pips
```

A hammer after a 200-pip drop might be 15–25 pips tall. Then TP1 is ~20 pips and SL sits **inside** the pattern, not 100 pips below the wick. R:R prints 1:2 every time because `range / (0.5 * range)` is always 2. It does not measure the impulse.

**Enhanced:** `move = highest high over lookback (excluding the pattern) − pattern low` (mirror for sells). TP1 = entry ± 1×move. SL = pattern extreme ± 0.5×move.

### 2. Daily BSL / SSL is not the day’s liquidity

Original:

```
request.security(..., "D", ta.highest(high, 20))
request.security(..., "D", ta.lowest(low, 20))
```

That is the **20-day** high/low, labelled BSL/SSL. It is not previous-day high (buy-side liquidity) / previous-day low (sell-side liquidity). Patterns are **not** required to form there; the dashboard only shows distance.

**Enhanced:** BSL = previous day high, SSL = previous day low, optional current-day high/low. Toggle: pattern must print near SSL (buy) or BSL (sell).

### 3. “Clear” patterns are still quite loose

- Hammer/star: wick 1.5× body, opposite wick ≤ 0.6× body. **No** rule that close sits in the top/bottom of the candle. A long lower wick with a close near the low still counts.
- “Top or bottom of the move” = local 6-bar extreme only. A tiny dip in the middle of a range still qualifies.
- Tweezers only need matching lows/highs within 20% of average body.

**Enhanced:** close must be in the outer 40% of the candle; wick default 2.0×; prior move must be ≥ N×ATR; optional liquidity proximity.

### 4. Pending setups never expire

Wait flags stay true until LTF confirms or an opposite pattern appears. A box from yesterday can still arm a trade today.

**Enhanced:** cancel after `i_waitBars` (default 12).

### 5. Smaller issues in the original

| Issue | Effect |
|-------|--------|
| Pattern box left edge `bar_index - 2` for a 1-candle hammer | Box covers an extra bar |
| `v_tf + "m"` on a 4H chart | Dashboard shows `240m / 60m` |
| Prices `math.round(..., 2)` | Wrong for JPY (2 dp of 150.123 is OK-ish) and for gold/metals with 3 dp; mintick is safer |
| Daily `request.security` without `lookahead_off` | Can leak the current daily bar |
| `max_boxes_count=5` | Easy to hit the cap |
| Confirm blocked while `s_live` | Intended, but a live trade that never hits SL and never invalidates (grind without tagging TP then reversing only a little) can block new signals for a long time |

## What was already in good shape (kept)

- Closed-bar pattern checks (`[1]`, `[2]`, `[3]`) so the shape does not repaint after the signal bar.
- LTF confirm uses `lookahead_off` and completed LTF candles.
- Entry = break of pattern high/low, not a market order in the middle of the candle.
- BUY/SELL labels, pattern box, TP ladder, grey-on-hit, “TP then SL = win”.
- One live setup; new confirm clears previous drawings.
- Session counters ignore historical days via `v_isToday`.

## Enhanced defaults (you can loosen them)

If the chart goes quiet, turn **off** “Pattern must be near daily SSL/BSL”, lower **Min move size (ATR)** toward `1.0`, or raise wait bars. Tweezers can be disabled if they still look noisy.
