# LYNX MTF — how the working setup behaves

The 3 Aug script is the strategy. Pattern detection, LTF confirm, entry, and SL = 0.5× pattern **inside** the box are unchanged.

`LYNX_MTF.pine` is that script plus two runtime-only patches:

1. **Completed runner no longer blocks the chart** — if price tags TP8, or a new reversal prints after TP1+, the old entry/SL/TP lines are removed and `s_live` is released so the next setup can confirm. That is why XAUUSD 15m stayed on the first SELL (Active SL 4474) while later shooting stars only showed “Wait 5m”.
3. **Wait 5m never became a trade** — confirm only looked at 5m `close[1]`/`[2]`/`[3]`, not the latest completed 5m bar, so the breakout candle was skipped. Confirm now uses that bar **and** a 15m close through the pattern high.

Do not change swing lookback, wait flags, or `not s_live` on confirm — those are what print the signals you already use.
