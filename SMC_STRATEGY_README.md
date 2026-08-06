# SMC Professional Strategy (Pine Script v6)

Professional TradingView **strategy** (not indicator) based on Smart Money Concepts, CPR / Market Profile pivots, VWAP, and multi-confirmation structure.

**File:** `SMC_Professional_Strategy.pine`

## Non-repainting rules

- `process_orders_on_close=true`
- `calc_on_every_tick=false`
- Entries only when `barstate.isconfirmed`
- HTF / daily data via `request.security(..., lookahead=barmerge.lookahead_off)`
- Historical entries do not change after bar close

## Confirmations (each toggleable)

| Module | Buy bias | Sell bias |
|--------|----------|-----------|
| HTF EMA 50/200 | Fast > Slow & price > Fast | Fast < Slow & price < Fast |
| CPR | Bullish (+ optional narrow) | Bearish (+ optional narrow) |
| VWAP | Close above VWAP | Close below VWAP |
| Liquidity sweep | Sweep swing low + reclaim | Sweep swing high + reclaim |
| Breakout / Retest | Break or retest resistance | Break or retest support |
| Volume | Volume > MA × mult | same |
| Risk:Reward | T2 RR ≥ minimum (default 1:2) | same |
| ATR filter | Reject abnormal range candles | same |
| Sideways filter | ADX + EMA spread trending | same |

Minimum confirmation count is configurable (default 5). Disabled modules auto-pass.

## Dynamic stop loss (priority)

1. Liquidity sweep low / high  
2. Swing low / high  
3. CPR BC / TC (with pivot)  
4. ATR buffer from entry  

SL is forced to the invalidating side of the trade idea.

## Dynamic targets (no fixed points)

Targets prefer nearest structure:

- Liquidity (prior swings)
- Previous day high / low
- CPR / pivot levels
- Breakout levels
- RR floors as fallback (T1 / T2 / T3)

Position scales out via `strategy.exit` qty percents (T1 / T2 / remainder T3).

## Chart drawings

- Buy / Sell arrows  
- Entry, Stop, T1, T2, T3 lines (extend while trade is open)  
- Exit marks  
- Historical drawings kept in arrays (capped for TradingView limits)

## Dashboard

Total trades, wins, losses, SL hits, target hits, win rate, profit factor, net profit, max drawdown, live confirmation counts.

## Alerts

- Buy / Sell (with SL + targets in message)
- Stop Loss hit
- Target hit  

Plus matching `alertcondition()` hooks for the Create Alert UI.

## How to use on TradingView

1. Open TradingView → Pine Editor  
2. Paste contents of `SMC_Professional_Strategy.pine`  
3. Click **Add to chart** (Strategy Tester opens)  
4. Tune module toggles and HTF / CPR timeframe to your market  
5. Create alerts from the strategy or alertcondition list  

## Suggested starting settings

- Intraday indices / FX: HTF = 60, CPR = D, min confirmations = 5–6  
- Crypto: VWAP Session or Week, slightly wider ATR abnormal mult  
- If few signals: disable Liquidity Sweep **or** lower min confirmations  
- If too many signals: require Narrow CPR + Volume + RR  

## Code quality

- Pine Script Version 6  
- Modular helper functions (commented)  
- No duplicate variable / function declarations  
- `strategy.entry` / `strategy.exit` / confirmed closes only  
