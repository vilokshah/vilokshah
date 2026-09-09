# LYNX MTF — how the working setup behaves

The 3 Aug script is the strategy. Pattern detection, LTF confirm, entry, and SL = 0.5× pattern **inside** the box are unchanged.

`LYNX_MTF.pine` is that script plus two runtime-only patches:

1. **SL wipe** — if a TP run already set `s_live=false` and left lines on the chart, tagging SL still deletes all trade drawings.
2. **Dashboard** — trade/win/loss counters increment on **bar close** (`barstate.isconfirmed`), not every tick.

Do not change swing lookback, wait flags, or `not s_live` on confirm — those are what print the signals you already use.
