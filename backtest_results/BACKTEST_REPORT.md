# Impulse Fib Golden Zone — Backtest Results

**Data:** GC=F (XAUUSD proxy), 15m, 2026-05-26 → 2026-08-05 (4596 bars)

> Approximate offline backtest mirroring the Pine strategy. For live Strategy Tester stats, use `Impulse_Fibonacci_Golden_Zone_Strategy.pine` on TradingView.

## Summary by Configuration

| Config | Trades | Win Rate | Net PnL | Net % | Profit Factor | Max DD % | Avg Trade |
|---|---:|---:|---:|---:|---:|---:|---:|
| default_ema_tp_impulse | 186 | 56.99% | 163.75 | 1.64% | 1.003 | -55.69% | 0.88 |
| default_ema_tp_1R | 187 | 62.03% | -1965.32 | -19.65% | 0.952 | -53.82% | -10.51 |
| default_ema_tp_2R | 153 | 45.1% | 5214.6 | 52.15% | 1.085 | -52.35% | 34.08 |
| no_ema_tp_impulse | 288 | 52.78% | -8989.71 | -89.9% | 0.71 | -91.22% | -31.21 |
| stricter_5c_5usd | 47 | 51.06% | 1853.14 | 18.53% | 1.126 | -27.69% | 39.43 |
| noisy_3c_no_minmove | 187 | 62.03% | -1965.32 | -19.65% | 0.952 | -53.82% | -10.51 |

## Suggested Starting Settings (XAUUSD 15m)

- Minimum Consecutive Candles: **3–5**
- Require Minimum Price Move: **ON**, ~**$3–$5** (30–50 with pip size 0.1)
- EMA Trend Filter: **ON** (200)
- Take Profit: start with **1R or 1.5R** (impulse-end targets can be optimistic)
- One Trade Per Impulse: **ON**

## How to read the chart markings

| Marking | Meaning |
|---|---|
| **Bullish Impulse** | A completed run of consecutive green candles; Fib levels drawn from it |
| **Bearish Impulse** | Same for consecutive red candles |
| **Golden Zone / GZ** | Price band between Fib **0.500** and **0.618** |
| **GZ Buy** | First revisit of that band after a bullish impulse → consider long |
| **GZ Sell** | First revisit after a bearish impulse → consider short |
| **1.000 line** | Impulse origin — logical stop / invalidation |
| **0.000 line** | Impulse extreme — default take-profit target |
