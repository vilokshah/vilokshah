#!/usr/bin/env python3
"""
Impulse Fibonacci Golden Zone — offline backtest (mirrors Pine strategy logic).

Default: XAUUSD (GC=F) 15-minute, last ~60 days via yfinance.
"""

from __future__ import annotations

import json
from dataclasses import dataclass, asdict
from pathlib import Path

import numpy as np
import pandas as pd
import yfinance as yf

# ── parameters (aligned with Pine defaults) ───────────────────────────────────
MIN_CANDLES = 3
USE_MIN_MOVE = True
MIN_MOVE = 3.0          # price units; with pip_size=0.1 and 30 pips → $3 on gold
PIP_SIZE = 0.1
MIN_MOVE_PRICE = MIN_MOVE  # already in price for gold backtest clarity ($3)
# Recompute: 30 pips * 0.1 = $3.0
MIN_MOVE_PRICE = 30.0 * PIP_SIZE

USE_EMA_FILTER = True
EMA_LEN = 200
SL_BUFFER = 5.0 * PIP_SIZE   # $0.50
TP_MODE = "impulse_end"      # impulse_end | 1R | 1.5R | 2R
ONE_PER_IMPULSE = True
INITIAL_CAPITAL = 10_000.0
RISK_PCT = 0.05              # 5% equity notional (approx; simplified)
COMMISSION_PCT = 0.0002      # 0.02% per side


@dataclass
class Trade:
    direction: str
    entry_time: str
    exit_time: str
    entry: float
    exit: float
    sl: float
    tp: float
    pnl: float
    pnl_pct: float
    reason: str


def fetch_xauusd_15m(period: str = "60d") -> pd.DataFrame:
    # GC=F = Gold futures proxy (spot XAUUSD not always available intraday on yfinance)
    ticker = yf.Ticker("GC=F")
    df = ticker.history(period=period, interval="15m", auto_adjust=True)
    if df.empty:
        raise RuntimeError("No data returned from yfinance for GC=F 15m")
    df = df.rename(columns={"Open": "open", "High": "high", "Low": "low", "Close": "close"})
    df = df[["open", "high", "low", "close"]].dropna()
    df.index = pd.to_datetime(df.index, utc=True)
    return df


def ema(series: pd.Series, length: int) -> pd.Series:
    return series.ewm(span=length, adjust=False).mean()


def run_backtest(df: pd.DataFrame) -> dict:
    o = df["open"].values
    h = df["high"].values
    l = df["low"].values
    c = df["close"].values
    times = df.index
    n = len(df)
    ema200 = ema(df["close"], EMA_LEN).values

    bull_count = 0
    bull_start_low = np.nan
    bull_end_high = np.nan
    bull_start_i = -1

    bear_count = 0
    bear_start_high = np.nan
    bear_end_low = np.nan
    bear_start_i = -1

    active_dir = 0  # 1 bull, -1 bear
    imp_start = np.nan
    imp_end = np.nan
    imp_end_i = -1
    traded = False

    position = 0  # 1 long, -1 short
    entry_price = np.nan
    sl = np.nan
    tp = np.nan
    entry_i = -1

    trades: list[Trade] = []
    equity = INITIAL_CAPITAL
    equity_curve = [equity]

    def move_ok(s, e):
        if not USE_MIN_MOVE:
            return True
        return abs(e - s) >= MIN_MOVE_PRICE

    def finalize_bull(i):
        nonlocal active_dir, imp_start, imp_end, imp_end_i, traded
        if bull_count >= MIN_CANDLES and move_ok(bull_start_low, bull_end_high):
            active_dir = 1
            imp_start = bull_start_low
            imp_end = bull_end_high
            imp_end_i = i - 1
            traded = False
            return True
        return False

    def finalize_bear(i):
        nonlocal active_dir, imp_start, imp_end, imp_end_i, traded
        if bear_count >= MIN_CANDLES and move_ok(bear_start_high, bear_end_low):
            active_dir = -1
            imp_start = bear_start_high
            imp_end = bear_end_low
            imp_end_i = i - 1
            traded = False
            return True
        return False

    def levels():
        if active_dir == 1:
            r = imp_end - imp_start
            return imp_end, imp_end - 0.5 * r, imp_end - 0.618 * r, imp_start
        if active_dir == -1:
            r = imp_start - imp_end
            return imp_end, imp_end + 0.5 * r, imp_end + 0.618 * r, imp_start
        return (np.nan,) * 4

    def close_trade(i, price, reason):
        nonlocal position, equity, entry_price, sl, tp, entry_i
        direction = "long" if position == 1 else "short"
        raw = (price - entry_price) * position
        # simplified: risk 5% of equity notionally scaled by $1 move per unit
        # position size: risk_amount / (entry-sl distance)
        risk_amt = equity * RISK_PCT
        risk_dist = abs(entry_price - sl)
        size = risk_amt / risk_dist if risk_dist > 0 else 0
        pnl = size * raw
        fee = abs(size * entry_price) * COMMISSION_PCT + abs(size * price) * COMMISSION_PCT
        pnl -= fee
        equity += pnl
        trades.append(
            Trade(
                direction=direction,
                entry_time=str(times[entry_i]),
                exit_time=str(times[i]),
                entry=float(entry_price),
                exit=float(price),
                sl=float(sl),
                tp=float(tp),
                pnl=float(pnl),
                pnl_pct=float(pnl / INITIAL_CAPITAL * 100),
                reason=reason,
            )
        )
        position = 0
        entry_price = sl = tp = np.nan
        entry_i = -1

    for i in range(n):
        is_bull = c[i] > o[i]
        is_bear = c[i] < o[i]

        # manage open trade first (intrabar: check SL then TP conservatively)
        if position != 0:
            hit_sl = (position == 1 and l[i] <= sl) or (position == -1 and h[i] >= sl)
            hit_tp = (position == 1 and h[i] >= tp) or (position == -1 and l[i] <= tp)
            if hit_sl and hit_tp:
                # worst case: assume SL first
                close_trade(i, sl, "SL (ambiguous)")
            elif hit_sl:
                close_trade(i, sl, "SL")
            elif hit_tp:
                close_trade(i, tp, "TP")

        # impulse detection
        if is_bull:
            if bull_count == 0:
                bull_start_low = l[i]
                bull_start_i = i
                bull_count = 1
            else:
                bull_count += 1
            bull_end_high = h[i]
            finalize_bear(i)
            bear_count = 0
            bear_start_high = bear_end_low = np.nan
            bear_start_i = -1
        elif is_bear:
            if bear_count == 0:
                bear_start_high = h[i]
                bear_start_i = i
                bear_count = 1
            else:
                bear_count += 1
            bear_end_low = l[i]
            finalize_bull(i)
            bull_count = 0
            bull_start_low = bull_end_high = np.nan
            bull_start_i = -1
        else:
            finalize_bull(i) or finalize_bear(i)
            bull_count = bear_count = 0
            bull_start_low = bull_end_high = np.nan
            bear_start_high = bear_end_low = np.nan

        lvl000, lvl500, lvl618, lvl1000 = levels()
        has = active_dir != 0 and not np.isnan(lvl500)
        if has:
            # invalidate
            if (active_dir == 1 and c[i] < lvl1000) or (active_dir == -1 and c[i] > lvl1000):
                active_dir = 0
                has = False

        # entries
        if position == 0 and has and i > imp_end_i:
            zone_hi = max(lvl500, lvl618)
            zone_lo = min(lvl500, lvl618)
            in_zone = h[i] >= zone_lo and l[i] <= zone_hi
            can = (not ONE_PER_IMPULSE) or (not traded)

            if active_dir == 1 and in_zone and can:
                if (not USE_EMA_FILTER) or c[i] > ema200[i]:
                    entry_price = min(c[i], zone_hi)
                    sl = lvl1000 - SL_BUFFER
                    risk = entry_price - sl
                    if risk > 0:
                        if TP_MODE == "1R":
                            tp = entry_price + risk
                        elif TP_MODE == "1.5R":
                            tp = entry_price + 1.5 * risk
                        elif TP_MODE == "2R":
                            tp = entry_price + 2.0 * risk
                        else:
                            tp = lvl000
                        if tp > entry_price:
                            position = 1
                            entry_i = i
                            traded = True

            elif active_dir == -1 and in_zone and can:
                if (not USE_EMA_FILTER) or c[i] < ema200[i]:
                    entry_price = max(c[i], zone_lo)
                    sl = lvl1000 + SL_BUFFER
                    risk = sl - entry_price
                    if risk > 0:
                        if TP_MODE == "1R":
                            tp = entry_price - risk
                        elif TP_MODE == "1.5R":
                            tp = entry_price - 1.5 * risk
                        elif TP_MODE == "2R":
                            tp = entry_price - 2.0 * risk
                        else:
                            tp = lvl000
                        if tp < entry_price:
                            position = -1
                            entry_i = i
                            traded = True

        equity_curve.append(equity if position == 0 else equity)  # MTM omitted for simplicity

    # force close at end
    if position != 0:
        close_trade(n - 1, c[-1], "EOD")

    wins = [t for t in trades if t.pnl > 0]
    losses = [t for t in trades if t.pnl <= 0]
    gross_profit = sum(t.pnl for t in wins)
    gross_loss = abs(sum(t.pnl for t in losses))
    pf = (gross_profit / gross_loss) if gross_loss > 0 else float("inf")
    rets = [t.pnl for t in trades]
    avg = float(np.mean(rets)) if rets else 0.0
    win_rate = len(wins) / len(trades) * 100 if trades else 0.0

    # max drawdown on closed-equity steps
    eq = [INITIAL_CAPITAL]
    for t in trades:
        eq.append(eq[-1] + t.pnl)
    peak = eq[0]
    max_dd = 0.0
    for v in eq:
        peak = max(peak, v)
        max_dd = min(max_dd, (v - peak) / peak * 100)

    long_t = [t for t in trades if t.direction == "long"]
    short_t = [t for t in trades if t.direction == "short"]

    return {
        "symbol": "GC=F (XAUUSD proxy)",
        "timeframe": "15m",
        "period": f"{times[0]} → {times[-1]}",
        "bars": n,
        "params": {
            "min_candles": MIN_CANDLES,
            "min_move_price": MIN_MOVE_PRICE,
            "ema_filter": USE_EMA_FILTER,
            "ema_length": EMA_LEN,
            "tp_mode": TP_MODE,
            "sl_buffer": SL_BUFFER,
            "one_per_impulse": ONE_PER_IMPULSE,
        },
        "results": {
            "trades": len(trades),
            "wins": len(wins),
            "losses": len(losses),
            "win_rate_pct": round(win_rate, 2),
            "net_pnl": round(sum(rets), 2),
            "net_pnl_pct": round(sum(rets) / INITIAL_CAPITAL * 100, 2),
            "avg_trade": round(avg, 2),
            "profit_factor": round(pf, 3) if pf != float("inf") else "inf",
            "max_drawdown_pct": round(max_dd, 2),
            "final_equity": round(eq[-1], 2),
            "long_trades": len(long_t),
            "long_pnl": round(sum(t.pnl for t in long_t), 2),
            "short_trades": len(short_t),
            "short_pnl": round(sum(t.pnl for t in short_t), 2),
        },
        "trades": [asdict(t) for t in trades],
    }


def main():
    out_dir = Path("/workspace/backtest_results")
    out_dir.mkdir(exist_ok=True)

    print("Fetching GC=F 15m data...")
    df = fetch_xauusd_15m("60d")
    print(f"Bars: {len(df)} | {df.index[0]} → {df.index[-1]}")

    # Run a few parameter variants
    variants = []

    global USE_EMA_FILTER, TP_MODE, MIN_MOVE_PRICE, MIN_CANDLES

    configs = [
        {"name": "default_ema_tp_impulse", "ema": True, "tp": "impulse_end", "min_move": 3.0, "min_c": 3},
        {"name": "default_ema_tp_1R", "ema": True, "tp": "1R", "min_move": 3.0, "min_c": 3},
        {"name": "default_ema_tp_2R", "ema": True, "tp": "2R", "min_move": 3.0, "min_c": 3},
        {"name": "no_ema_tp_impulse", "ema": False, "tp": "impulse_end", "min_move": 3.0, "min_c": 3},
        {"name": "stricter_5c_5usd", "ema": True, "tp": "1.5R", "min_move": 5.0, "min_c": 5},
        {"name": "noisy_3c_no_minmove", "ema": True, "tp": "1R", "min_move": 0.0, "min_c": 3},
    ]

    summary_rows = []
    for cfg in configs:
        USE_EMA_FILTER = cfg["ema"]
        TP_MODE = cfg["tp"]
        MIN_MOVE_PRICE = cfg["min_move"]
        global USE_MIN_MOVE
        USE_MIN_MOVE = cfg["min_move"] > 0
        MIN_CANDLES = cfg["min_c"]

        result = run_backtest(df)
        result["config_name"] = cfg["name"]
        path = out_dir / f"{cfg['name']}.json"
        path.write_text(json.dumps(result, indent=2))
        r = result["results"]
        summary_rows.append(
            {
                "config": cfg["name"],
                **{k: r[k] for k in [
                    "trades", "win_rate_pct", "net_pnl", "net_pnl_pct",
                    "profit_factor", "max_drawdown_pct", "avg_trade",
                    "long_trades", "short_trades",
                ]},
            }
        )
        print(
            f"{cfg['name']}: trades={r['trades']} WR={r['win_rate_pct']}% "
            f"PnL={r['net_pnl']} ({r['net_pnl_pct']}%) PF={r['profit_factor']} "
            f"MaxDD={r['max_drawdown_pct']}%"
        )

    summary = {
        "description": "Impulse Fib Golden Zone backtest on GC=F 15m (~60 trading days)",
        "note": (
            "yfinance GC=F is a gold futures proxy for XAUUSD. "
            "Results are approximate; use the Pine strategy on TradingView for official Strategy Tester stats."
        ),
        "data_range": f"{df.index[0]} → {df.index[-1]}",
        "bars": len(df),
        "configs": summary_rows,
    }
    (out_dir / "summary.json").write_text(json.dumps(summary, indent=2))

    # Markdown report
    lines = [
        "# Impulse Fib Golden Zone — Backtest Results",
        "",
        f"**Data:** GC=F (XAUUSD proxy), 15m, {df.index[0].date()} → {df.index[-1].date()} ({len(df)} bars)",
        "",
        "> Approximate offline backtest mirroring the Pine strategy. For live Strategy Tester stats, use `Impulse_Fibonacci_Golden_Zone_Strategy.pine` on TradingView.",
        "",
        "## Summary by Configuration",
        "",
        "| Config | Trades | Win Rate | Net PnL | Net % | Profit Factor | Max DD % | Avg Trade |",
        "|---|---:|---:|---:|---:|---:|---:|---:|",
    ]
    for row in summary_rows:
        lines.append(
            f"| {row['config']} | {row['trades']} | {row['win_rate_pct']}% | "
            f"{row['net_pnl']} | {row['net_pnl_pct']}% | {row['profit_factor']} | "
            f"{row['max_drawdown_pct']}% | {row['avg_trade']} |"
        )
    lines += [
        "",
        "## Suggested Starting Settings (XAUUSD 15m)",
        "",
        "- Minimum Consecutive Candles: **3–5**",
        "- Require Minimum Price Move: **ON**, ~**$3–$5** (30–50 with pip size 0.1)",
        "- EMA Trend Filter: **ON** (200)",
        "- Take Profit: start with **1R or 1.5R** (impulse-end targets can be optimistic)",
        "- One Trade Per Impulse: **ON**",
        "",
        "## How to read the chart markings",
        "",
        "| Marking | Meaning |",
        "|---|---|",
        "| **Bullish Impulse** | A completed run of consecutive green candles; Fib levels drawn from it |",
        "| **Bearish Impulse** | Same for consecutive red candles |",
        "| **Golden Zone / GZ** | Price band between Fib **0.500** and **0.618** |",
        "| **GZ Buy** | First revisit of that band after a bullish impulse → consider long |",
        "| **GZ Sell** | First revisit after a bearish impulse → consider short |",
        "| **1.000 line** | Impulse origin — logical stop / invalidation |",
        "| **0.000 line** | Impulse extreme — default take-profit target |",
        "",
    ]
    (out_dir / "BACKTEST_REPORT.md").write_text("\n".join(lines))
    print(f"\nWrote {out_dir / 'summary.json'} and BACKTEST_REPORT.md")


if __name__ == "__main__":
    main()
