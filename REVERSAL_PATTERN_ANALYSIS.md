# Reversal Pattern Indicator — Prompt Analysis

This note is the starting point for enhancing the **reversal-pattern** TradingView indicator (15m / 1H / 4H, forex + commodities).

**Status:** the Pine you described is **not in this repository yet**. `main` currently holds an **Impulse Fibonacci Golden Zone** indicator, which is a different idea (consecutive same-color candles + Fib 0.5–0.618 pullback). Do not mix those rules while we enhance the reversal script.

Paste the current Pine in the next message. This document is the checklist we will score your code against.

---

## 1. What the original prompt asks for

Broken into independent requirements:

| # | Requirement | Intent |
|---|-------------|--------|
| R1 | Markets | Forex pairs and commodities (gold, oil, etc.). Pip size must be correct per symbol. |
| R2 | Timeframes | Designed for **15m, 1H, 4H** (not tick scalping, not daily-only). |
| R3 | Daily BSL / SSL | Mark **Buy-Side Liquidity** and **Sell-Side Liquidity** for the **day**. |
| R4 | Location filter | Patterns only count at the **top or bottom of a move** (not mid-range noise). |
| R5 | Patterns | **Hammer** (single), **engulfing** (2-candle), **3-candle reversal** at extremes. |
| R6 | Quality | **Clear** reversals only — reject weak / in-between candles. |
| R7 | Pattern box | Mark **high and low of the pattern** (rectangle or lines). |
| R8 | Entry mark | Draw entry as a rectangle or lines, plus the next target. |
| R9 | Measured-move TP1 | Target length = **exact length of the prior move**. Example: 200-pip sell-off, hammer at bottom → TP **200 pips above the hammer**. |
| R10 | Stop | SL = **half** of that move (example: 100 pips). Wording also mentions “start of the move” — see ambiguities. |
| R11 | Signals | Obvious **BUY** / **SELL** labels. |
| R12 | TP2 (extension) | Next target = previous TP plus the **same measured length** again (example: another 200 pips beyond TP1). |

---

## 2. Formal trading model (how we will implement it)

Until your Pine says otherwise, this is the unambiguous reading of the prompt.

### 2.1 The “move”

A **bearish move into a bottom reversal** (BUY):

```
Move start  = last swing high before the decline (or daily BSL / local high)
Move end    = low of the reversal pattern (hammer low / engulfing low / 3-candle low)
Move length = start − end          e.g. 200 pips
```

A **bullish move into a top reversal** (SELL) is the mirror.

**“At top or bottom”** means the pattern’s extreme is within a small tolerance of that swing extreme (and preferably after a liquidity sweep of daily SSL/BSL).

### 2.2 Liquidity (daily BSL / SSL)

SMC convention:

- **BSL (Buy-Side Liquidity)** — resting buys above equal highs / **previous (or current) day high**. Price sweeping BSL then reversing = **sell** candidate.
- **SSL (Sell-Side Liquidity)** — resting sells below equal lows / **previous (or current) day low**. Price sweeping SSL then reversing = **buy** candidate.

On 15m / 1H / 4H, plot at least:

- Previous day high / low (PDH / PDL)
- Optional: current day high / low so far
- Optional: equal highs / equal lows (session or lookback)

A **clear** setup is stronger if the reversal pattern forms **after** a wick through PDH (sell) or PDL (buy), then closes back inside.

### 2.3 Pattern definitions (strict, so they stay “clear”)

All body/wick ratios below should be inputs so we can tune per pair.

**Hammer (bullish, at bottom)**

- Small body near the **top** of the range
- Lower wick ≥ `hammerWickMult` × body (typical 2.0)
- Upper wick small (≤ body, or ≤ 25% of full range)
- Close in the upper third of the candle
- Prefer close ≥ open (or at least not a wide red body)

**Shooting star (bearish, at top)** — mirror of hammer.

**Bullish engulfing (at bottom)**

- Candle 1 bearish
- Candle 2 bullish
- Candle 2 body **fully covers** candle 1 body (open/close, not just wicks)
- Optional strict mode: candle 2 high ≥ candle 1 high and low ≤ candle 1 low

**Bearish engulfing (at top)** — mirror.

**3-candle bullish (at bottom)** — Morning star family, strict:

- C1: strong bear (body ≥ `strongBodyPct` of range)
- C2: small body / indecision (doji or spinning top), often gaps or trades into C1 low
- C3: strong bull that closes **into or above** the midpoint of C1 (strict: closes above C1 open)

**3-candle bearish (at top)** — Evening star, mirror.

Rejected as not “clear”: small hammers inside ranges, engulfing that only covers 50% of the prior body, 3-candle with mixed tiny bodies.

### 2.4 Entry, SL, TP (measured move)

Using the 200-pip hammer-at-bottom example:

| Level | Formula | Example |
|-------|---------|---------|
| Pattern high / low | Box around the 1/2/3 candles | Hammer low = swing low |
| **Entry** | Conservative: break of **pattern high** (buy) / **pattern low** (sell). Aggressive: close of the pattern candle. | Buy stop above hammer high |
| **SL** | Half the move beyond the pattern extreme | 100 pips below hammer low |
| **TP1** | Pattern extreme + full move in trade direction | Hammer high/low region + 200 pips |
| **TP2** | TP1 + same move length | + another 200 pips |

Prompt wording for SL is slightly mixed; recommended default:

- **SL distance = 0.5 × move length**, placed beyond the pattern (buy: below pattern low; sell: above pattern high).
- Optional mode: SL at **move origin** (full 1R against a 1:2 target). That is stricter and usually worse R:R than half-move SL.

Risk:reward with half-move SL and full-move TP1 is **1 : 2** (plus spread). TP2 is **1 : 4** if SL is half-move.

### 2.5 Visuals

- Daily BSL / SSL lines (labeled)
- Pattern rectangle (high–low of the pattern bars)
- Entry line / small box
- SL, TP1, TP2 lines (extend until hit or invalidated)
- Large **BUY** / **SELL** labels (not tiny plotshapes only)
- Alerts on confirmed signal (closed bar only — no repaint)

---

## 3. Ambiguities we will resolve from your Pine (or default as above)

1. **What is “the move”?** Last N-bar swing, last impulse of consecutive candles, or distance from PDH/PDL?
2. **SL = half the move vs start of the move.** Your example uses 100 pips (half). “Start of the move” would be ~200 pips. Default: half.
3. **Where is TP anchored?** “200 pips above that hammer” — from hammer **low**, hammer **close**, or hammer **high** (entry)? Default: from **pattern extreme** (low for buys, high for sells) so the measured move is clean.
4. **Entry trigger:** immediate on pattern close vs wait for break of pattern high/low.
5. **Which 3-candle set?** Morning/evening star vs three soldiers/crows vs custom.
6. **BSL/SSL:** previous day H/L only, or also equal highs/lows and session liquidity?
7. **One signal at a time** vs overlapping patterns on every swing.
8. **Doji / equal open-close** handling on forex (many “dojis” from spread).

---

## 4. Gap vs what is already in this GitHub repo

| In repo today | vs this prompt |
|---------------|----------------|
| `Impulse_Fibonacci_Golden_Zone.pine` | Impulse = consecutive same-color candles. **No** hammer / engulfing / 3-candle. **No** daily BSL/SSL. Fib GZ pullback entries, not measured-move reversal. |
| `Impulse_Fibonacci_Golden_Zone_Strategy.pine` | Same impulse model + strategy tester. |
| `cursor/smc-professional-strategy-a8c9` (other branch) | SMC confirmations, swing sweep, CPR, VWAP. **No** candlestick reversal set, **no** 1:1 measured-move TP from the impulse into the pattern. |

So the reversal indicator is a **new script**, not a small patch on Impulse Fib.

---

## 5. Enhancement backlog (after we have your Pine)

Priority order once the code is in the branch:

1. **Match the spec** — BSL/SSL, strict patterns at swing extremes, measured-move TP1/TP2, half-move SL, BUY/SELL + boxes.
2. **No repaint** — detect on `barstate.isconfirmed`; HTF/daily via `request.security(..., lookahead_off)`.
3. **Pip engine** — auto pip for FX (JPY 0.01 / others 0.0001), gold/oil via mintick or custom pip input (same lesson as the Fib script).
4. **Quality filters** — min move (pips), ATR-relative pattern size, require SSL/BSL sweep, optional HTF trend (e.g. 4H bias on 15m).
5. **Trade management drawings** — cancel box if SL hit or opposite extreme breaks; keep last N setups (TradingView line/box limits).
6. **Alerts** — BUY / SELL / TP1 / TP2 / SL with prices in the message.
7. **Optional strategy() twin** — same rules for Strategy Tester (win rate on 15m/1H/4H FX and XAU).

---

## 6. What to send next

Paste the full Pine (`//@version=...` through the last line). Also note, if you remember:

- TradingView ticker(s) you tested (e.g. `FX:EURUSD`, `OANDA:XAUUSD`)
- Chart timeframe
- What looks wrong today (too many signals, missing BSL, wrong TP, etc.)

We will then map each function to R1–R12, list exact gaps, and implement the enhancements on this branch.
