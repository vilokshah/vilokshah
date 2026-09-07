# LYNX MTF

Standalone TradingView indicator. Paste `LYNX_MTF.pine` into the Pine Editor (v6). Use only on **15m, 1H, or 4H**.

| File | What it is |
|------|------------|
| `LYNX_MTF.pine` | **Use this.** Enhanced indicator. |
| `LYNX_MTF_original.pine` | Your exact 3 Aug script, unchanged. |
| `ANALYSIS.md` | How the original script behaved vs the intended rules. |

No other scripts are required.

## How to load

1. TradingView → Pine Editor → Open `LYNX_MTF.pine`
2. Add to chart on EURUSD, XAUUSD, or another FX/commodity symbol
3. Chart timeframe must be 15m, 1H, or 4H (otherwise it errors on purpose)

Confirm timeframe is automatic:

- 15m chart → waits for **5m** close beyond the pattern
- 1H chart → waits for **15m**
- 4H chart → waits for **1H**

## What a valid trade looks like

1. Daily **SSL** (previous day low) or **BSL** (previous day high) is marked.
2. A **clear** reversal prints at a local swing low/high after a real prior move (ATR-filtered): hammer, engulfing, morning/evening star, or tweezer.
3. Dashed box + **BUY/SELL … Wait 5m/15m/1H**.
4. Lower-TF candle **closes** through the pattern high (buy) or low (sell).
5. **ENTRY** = that break. **SL** = half the prior **move** beyond the pattern wick. **TP1** = one full move. **TP2+** = the same length again.

If lower TF never confirms within N bars (default 12), the pending box is cancelled.
