# Realtime Alerts + How to Improve Results

## Phone / realtime entry alerts

Yes. Use **TradingView push notifications** — the indicator already fires alerts with entry / SL / TP.

### Phone setup (2 minutes)

1. Add `Impulse_Fibonacci_Golden_Zone.pine` to the chart (e.g. XAUUSD 15m).
2. **Alerts** (clock) → **Create alert**.
3. Condition → this indicator → **Any alert() function call**  
   (or `Bullish Fib 0.5-0.618 Triggered` / `Bearish...` / `Any GZ Entry Triggered`).
4. Enable **Notifications → Push** (optional: Email / SMS / Webhook).
5. Install the **TradingView mobile app**, same account, allow notifications.

When a GZ entry triggers on bar close, your phone gets a push. The `alert()` payload is JSON:

```json
{"side":"BUY","ticker":"XAUUSD","tf":"15","entry":...,"sl":...,"tp":...,"gz_low":...,"gz_high":...,"msg":"GZ Buy — Long in Golden Zone"}
```

### Other channels

| Channel | How |
|---|---|
| Phone push | TradingView app + Push on alert |
| Email / SMS | Toggle on the alert (SMS needs a paid plan) |
| Telegram / Discord | Alert → Webhook URL → bot/bridge |
| Desktop popup | Enable Popup on the alert |

**Tip:** Create the alert **after** you finish settings — TradingView freezes the settings used when the alert was created. Prefer bar-close alerts (script uses `once_per_bar_close`).

---

## What improves results (evidence-based)

There is no permanent “max profit” switch. On a ~2‑month GC=F 15m sample, these changes helped most:

| Change | Effect in sample |
|---|---|
| **Min 5 candles + ~$5 move + EMA 200** | Cut noise; baseline improved vs loose 3-candle |
| **Rejection candle** (green close for longs / red for shorts) | Best add-on: ~+40% net, PF ~1.42 with max-wait |
| **Max 40 bars after impulse** | Drops stale pullbacks |
| **TP = 1.5R** | Better than always targeting Fib 0.000 in several runs |
| **Session / HTF / “close must be in GZ”** | Mixed — often fewer trades, not always better; keep optional |

### Recommended preset (now the script defaults)

| Setting | Value |
|---|---|
| Min candles | **5** |
| Min move | **50** (pip size `0.1` ≈ **$5** on gold) |
| EMA 200 filter | **ON** |
| Rejection candle | **ON** |
| Max wait bars | **40** |
| TP (strategy) | **1.5R** |
| One trade / impulse | **ON** |
| HTF / Session / Close-in-zone | **OFF** (turn on only if your forward test improves) |

### Process tips that matter as much as filters

1. Risk **0.5–1%** per trade (not oversized “demo” risk).
2. Skip major news (CPI / NFP / FOMC) even if GZ fires.
3. Prefer **15m or 1H**; very low TFs overtrade this logic.
4. Validate on **your** broker’s XAUUSD in Strategy Tester, then paper trade.
5. Don’t stack every filter at once — overfiltering killed edge in some tests.

### Sample comparison (GC=F 15m, ~60 days — approximate)

| Preset | Trades | Win % | Net % | PF | Max DD |
|---|---:|---:|---:|---:|---:|
| Loose 3c + EMA + TP impulse end | 186 | 57% | ~+4% | ~1.01 | -56% |
| 5c + $5 + EMA + 1.5R | 47 | 51% | ~+24% | 1.16 | -28% |
| **+ rejection + max 40 bars** | **33** | **55%** | **~+40%** | **1.42** | **-22%** |

Use TradingView Strategy Tester for final numbers on live XAUUSD data.
