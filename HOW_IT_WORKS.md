# Impulse Fibonacci Golden Zone — How It Works

## Why the old chart looked messy

The first version **plotted Fib lines for every historical impulse**, which stacked into a “staircase” of overlapping lines. That made it hard to see which level was live.

**Fixed:** the indicator now draws **only the latest valid impulse** (one set of lines + one Golden Zone box). When a new impulse completes, the previous drawings are replaced.

---

## Glossary (what the markings mean)

| Marking on chart | Meaning |
|---|---|
| **Bullish Impulse** | A finished run of consecutive **green** candles (close > open). Levels are measured from that move. |
| **Bearish Impulse** | A finished run of consecutive **red** candles (close < open). |
| **Golden Zone (GZ)** | The price band between Fib **0.500** and **0.618**. This is the expected pullback area. |
| **GZ Buy / Enter Long** | Price has come back down into the GZ after a **bullish** impulse. |
| **GZ Sell / Enter Short** | Price has come back up into the GZ after a **bearish** impulse. |
| **0.000** | Impulse end (extreme of the move). Default **take-profit** area. |
| **1.000** | Impulse start (origin). Logical **stop-loss / invalidation** level. |
| Teal/red background on a candle group | The candles that formed the active impulse. |

---

## Does it take all swings?

**No — not classic swing highs/lows.**

It only detects **consecutive same-color candle streaks**:

1. Count green candles in a row (no red/doji in between).
2. When a red (or doji) appears, that green streak is “closed.”
3. If the streak has ≥ **Minimum Consecutive Candles** (default 3) **and** passes the optional **minimum price move** filter, it becomes the new **active impulse**.
4. A new valid impulse **replaces** the previous one.

So small 3-candle runs can still create setups unless you tighten filters. For gold (XAUUSD), recommended starting filters:

- Min candles: **3–5**
- Min move: **ON**, about **$3–$5** (with Custom Pip Size `0.1`, that is 30–50 “pips”)
- EMA 200 filter: **ON** (buys only above EMA, sells only below)

---

## Step-by-step logic

### 1) Detect impulse

**Bullish example**

```
Candle 1 green, 2 green, 3 green, 4 green → then a red candle
```

- Impulse Start = **low of candle 1**
- Impulse End   = **high of candle 4** (last green before the red)

**Bearish** is the mirror (high of first red → low of last red).

### 2) Build Fibonacci levels

For a **bullish** impulse (price ran up, we expect a pullback down into GZ):

| Level | Formula |
|---|---|
| 0.000 | Impulse End |
| 0.382 | End − 0.382 × (End − Start) |
| 0.500 | End − 0.500 × (End − Start) |
| 0.618 | End − 0.618 × (End − Start) |
| 0.786 | End − 0.786 × (End − Start) |
| 1.000 | Impulse Start |

Bearish impulses measure the same ratios **upward** from the impulse end.

### 3) Wait for retracement into the Golden Zone

After the impulse is complete, do **nothing** until price **touches or enters** the 0.500–0.618 band.

- That touch = **GZ Buy** (bullish impulse) or **GZ Sell** (bearish impulse)
- By default: **one signal per impulse** (avoids spam while price chops inside the zone)

### 4) Invalidation

If price closes beyond **1.000** (breaks the impulse origin), the setup is cancelled and drawings are cleared.

---

## How to plan entries (practical playbook)

### Long setup

1. See a strong green streak (≥3 candles, decent size).
2. Confirm **Bullish Impulse** label + teal Golden Zone box appears.
3. Wait for price to pull back into the teal GZ (between blue 0.5 and orange 0.618).
4. **Entry:** at/near GZ (market or limit inside the band).
5. **Stop:** below **1.000** (plus a small buffer).
6. **Target:** **0.000** (impulse high), or scale out at 1R / 1.5R / 2R.
7. Optional: only take if price is **above 200 EMA**.

### Short setup

Mirror of the above after a red streak; sell into the red GZ; stop above 1.000; target 0.000.

### Risk tips

- Prefer impulses that are **obvious** (big body range), not tiny 3-candle noise.
- Skip GZ touches that happen immediately on the first opposite candle if the move looks like a full reversal, not a pullback.
- If a new opposite impulse forms before your target, treat the old idea as done.

---

## Files in this repo

| File | Purpose |
|---|---|
| `Impulse_Fibonacci_Golden_Zone.pine` | Clean **indicator** (alerts + visuals, active impulse only) |
| `Impulse_Fibonacci_Golden_Zone_Strategy.pine` | **Strategy** for TradingView Strategy Tester (entries, SL, TP) |
| `backtest_impulse_fib.py` | Offline Python backtest script |
| `backtest_results/BACKTEST_REPORT.md` | Sample results on gold futures 15m |

### Backtest on TradingView

1. Open Pine Editor → paste `Impulse_Fibonacci_Golden_Zone_Strategy.pine`
2. Add to chart (XAUUSD / gold, 15m is a reasonable start)
3. Open **Strategy Tester** tab for win rate, profit factor, drawdown, trade list
4. Tune: min candles, min move, EMA filter, TP mode (0.000 / 1R / 1.5R / 2R)
