<x-filament-panels::page>

{{-- ─── Bot context badge ──────────────────────────────────────────── --}}
@if($botName)
<div style="margin-bottom:0.5rem; display:flex; align-items:center; gap:0.5rem;">
    <span style="font-size:0.7rem; color:#6b7280;">Угоди бота:</span>
    <span style="font-size:0.75rem; font-weight:600; color:#60a5fa; background:#1e3a5f22; border:1px solid #1e3a5f; border-radius:0.375rem; padding:0.15rem 0.5rem;">
        {{ $botName }}
    </span>
    <a href="/admin/chart-page?exchange={{ $exchange }}&symbol={{ urlencode($symbol) }}&tf={{ $tf }}"
       style="font-size:0.65rem; color:#4b5563; text-decoration:none; margin-left:0.25rem;">
        × Показати всіх
    </a>
</div>
@endif

{{-- ─── Controls bar ─────────────────────────────────────────────── --}}
<div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap; margin-bottom:0.75rem;">

    {{-- Exchange selector --}}
    <select id="chart-exchange"
            style="background:#111827; color:#e5e7eb; border:1px solid #374151;
                   border-radius:0.4rem; padding:0.35rem 0.65rem; font-size:0.8rem; cursor:pointer; min-width:70px;">
        @foreach(array_keys($symbolsByExchange) as $exch)
            <option value="{{ $exch }}" {{ $exch === $exchange ? 'selected' : '' }}>
                {{ strtoupper($exch) }}
            </option>
        @endforeach
    </select>

    {{-- Coin selector (filtered by exchange via JS) --}}
    <select id="chart-coin"
            style="background:#111827; color:#e5e7eb; border:1px solid #374151;
                   border-radius:0.4rem; padding:0.35rem 0.65rem; font-size:0.8rem; cursor:pointer; min-width:100px;">
        {{-- Populated by JS on load --}}
    </select>

    {{-- Hidden: full symbols map passed to JS --}}
    <script id="symbols-map-data" type="application/json">
        @json($symbolsByExchange)
    </script>

    {{-- Timeframe buttons --}}
    @foreach(['1m','5m','15m','1h','4h','1d'] as $t)
    <button class="tf-btn {{ $t === $tf ? 'tf-active' : '' }}" data-tf="{{ $t }}"
            style="font-size:0.75rem; padding:0.3rem 0.7rem; border-radius:0.4rem; cursor:pointer;
                   border:1px solid {{ $t === $tf ? '#3b82f6' : '#374151' }};
                   background:{{ $t === $tf ? '#1e3a5f' : '#111827' }};
                   color:{{ $t === $tf ? '#60a5fa' : '#9ca3af' }};">
        {{ $t }}
    </button>
    @endforeach

    {{-- Navigation + refresh buttons --}}
    <div style="margin-left:auto; display:flex; gap:0.5rem; align-items:center;">
        <button id="btn-go-start"
                style="font-size:0.7rem; padding:0.3rem 0.6rem; border-radius:0.4rem; cursor:pointer;
                       border:1px solid #374151; background:#111827; color:#9ca3af;"
                title="До початку даних">|◀</button>
        <button id="btn-go-end"
                style="font-size:0.7rem; padding:0.3rem 0.6rem; border-radius:0.4rem; cursor:pointer;
                       border:1px solid #374151; background:#111827; color:#9ca3af;"
                title="До кінця (зараз)">▶|</button>
        <button id="btn-fit"
                style="font-size:0.7rem; padding:0.3rem 0.6rem; border-radius:0.4rem; cursor:pointer;
                       border:1px solid #374151; background:#111827; color:#9ca3af;"
                title="Підігнати всі дані">⊡</button>

        {{-- Divider --}}
        <span style="color:#374151;">|</span>

        {{-- Manual refresh --}}
        <button id="btn-refresh"
                style="font-size:0.7rem; padding:0.3rem 0.7rem; border-radius:0.4rem; cursor:pointer;
                       border:1px solid #374151; background:#111827; color:#d1d5db;"
                title="Оновити нові свічки">↻ Оновити</button>

        {{-- Auto-refresh dropdown --}}
        <div id="auto-refresh-wrap" x-data="{ open: false }" style="position:relative;">
            <button @click="open = !open"
                    style="font-size:0.7rem; padding:0.3rem 0.65rem; border-radius:0.4rem; cursor:pointer;
                           border:1px solid #374151; background:#111827; color:#9ca3af; min-width:54px;">
                <span id="auto-label">Off</span> ▾
            </button>
            <div x-show="open" @click.outside="open = false" x-transition
                 style="position:absolute; right:0; top:100%; margin-top:0.25rem;
                        background:#111827; border:1px solid #374151; border-radius:0.375rem;
                        min-width:80px; z-index:50; overflow:hidden;">
                @foreach([0 => 'Off', 10 => '10s', 30 => '30s', 60 => '1хв'] as $sec => $label)
                <button onclick="setAutoRefresh({{ $sec }}); $data.open = false"
                        style="display:block; width:100%; text-align:left; padding:0.35rem 0.7rem;
                               font-size:0.7rem; color:#d1d5db; background:transparent; border:0; cursor:pointer;">
                    {{ $label }}
                </button>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ─── Indicator + Drawing toolbar ─────────────────────────────── --}}
<div style="display:flex; align-items:center; gap:0.4rem; margin-bottom:0.35rem; flex-wrap:wrap;">

    {{-- Indicators --}}
    <span style="font-size:0.65rem; color:#4b5563; margin-right:0.1rem;">Індикатори:</span>

    <button class="ind-btn" data-ind="ema20"
            style="font-size:0.72rem; padding:0.28rem 0.65rem; border-radius:0.4rem; cursor:pointer;
                   border:1px solid #374151; background:#111827; color:#9ca3af;"
            title="EMA 20">EMA 20</button>

    <button class="ind-btn" data-ind="ema50"
            style="font-size:0.72rem; padding:0.28rem 0.65rem; border-radius:0.4rem; cursor:pointer;
                   border:1px solid #374151; background:#111827; color:#9ca3af;"
            title="EMA 50">EMA 50</button>

    <button class="ind-btn" data-ind="ema200"
            style="font-size:0.72rem; padding:0.28rem 0.65rem; border-radius:0.4rem; cursor:pointer;
                   border:1px solid #374151; background:#111827; color:#9ca3af;"
            title="EMA 200">EMA 200</button>

    <button class="ind-btn" data-ind="bb"
            style="font-size:0.72rem; padding:0.28rem 0.65rem; border-radius:0.4rem; cursor:pointer;
                   border:1px solid #374151; background:#111827; color:#9ca3af;"
            title="Bollinger Bands (20, 2)">BB (20,2)</button>

    {{-- Divider --}}
    <span style="color:#1f2937; margin:0 0.15rem;">|</span>

    {{-- Drawing tools --}}
    <span style="font-size:0.65rem; color:#4b5563; margin-right:0.1rem;">Малювання:</span>

    <button id="btn-vol"
            style="font-size:0.72rem; padding:0.28rem 0.65rem; border-radius:0.4rem; cursor:pointer;
                   border:1px solid #374151; background:#111827; color:#9ca3af;"
            title="Показати / приховати об'єм">≡ Об'єм</button>

    <button class="draw-btn" data-mode="trendline"
            style="font-size:0.72rem; padding:0.28rem 0.65rem; border-radius:0.4rem; cursor:pointer;
                   border:1px solid #374151; background:#111827; color:#9ca3af;"
            title="Лінія тренду — 2 кліки">╱ Тренд</button>

    <button class="draw-btn" data-mode="fibonacci"
            style="font-size:0.72rem; padding:0.28rem 0.65rem; border-radius:0.4rem; cursor:pointer;
                   border:1px solid #374151; background:#111827; color:#9ca3af;"
            title="Рівні Фібоначчі — 2 кліки">◈ Фіб</button>

    <button class="draw-btn" data-mode="ruler"
            style="font-size:0.72rem; padding:0.28rem 0.65rem; border-radius:0.4rem; cursor:pointer;
                   border:1px solid #374151; background:#111827; color:#9ca3af;"
            title="Лінійка % — 2 кліки">↔ Лінійка</button>

    <button id="btn-undo"
            style="font-size:0.72rem; padding:0.28rem 0.65rem; border-radius:0.4rem; cursor:pointer;
                   border:1px solid #374151; background:#111827; color:#9ca3af; margin-left:0.35rem;"
            title="Скасувати останній малюнок">↩ Undo</button>

    <button id="btn-clear-drawings"
            style="font-size:0.72rem; padding:0.28rem 0.65rem; border-radius:0.4rem; cursor:pointer;
                   border:1px solid #374151; background:#111827; color:#6b7280;"
            title="Видалити всі малюнки">✕ Очистити</button>
</div>

{{-- ─── Chart container ─────────────────────────────────────────── --}}
<div style="position:relative; background:#0f1923; border:1px solid #1f2937; border-radius:0.5rem; overflow:hidden;">
    <div id="chart-container" style="width:100%; height:520px;"></div>
    <div id="chart-loading"
         style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center;
                background:rgba(15,25,35,0.85); font-size:0.85rem; color:#60a5fa; z-index:10;">
        Завантаження…
    </div>
    <div id="chart-error"
         style="display:none; position:absolute; inset:0; align-items:center; justify-content:center;
                background:rgba(15,25,35,0.9); font-size:0.85rem; color:#ef4444; z-index:10; padding:1rem; text-align:center;">
    </div>
</div>

{{-- ─── Legend / tooltip ─────────────────────────────────────────── --}}
<div id="chart-legend"
     style="margin-top:0.4rem; min-height:1.4rem; font-size:0.75rem; color:#9ca3af; font-family:monospace;"></div>

{{-- ─── TradingView Lightweight Charts ──────────────────────────── --}}
<script src="https://unpkg.com/lightweight-charts@4.2.0/dist/lightweight-charts.standalone.production.js"></script>

<script>
(function () {
    // ── State ─────────────────────────────────────────────────────────────
    let chart        = null;
    let candleSeries = null;
    let volumeSeries = null;

    let currentExchange  = '{{ $exchange }}';
    let currentSymbol    = '{{ $symbol }}';
    let currentTf        = '{{ $tf }}';
    let currentBotId     = {{ $botId ?? 'null' }};
    const sessionStartTs = {{ $sessionStart ?? 'null' }};

    let cachedCandles = [];
    let cachedVolume  = [];
    let currentTrades = [];

    let autoRefreshTimer = null;
    let isRefreshing     = false;
    let volumeVisible    = true;

    // ── Indicator state ────────────────────────────────────────────────────
    // Each entry: { series: LineSeries|null, visible: bool }
    const IND_CFG = {
        ema20:  { period: 20,  color: '#facc15', label: 'EMA 20'  },  // yellow
        ema50:  { period: 50,  color: '#f97316', label: 'EMA 50'  },  // orange
        ema200: { period: 200, color: '#c084fc', label: 'EMA 200' },  // purple
        // bb: handled separately (3 lines: upper, mid, lower)
        bb:     { period: 20,  mult: 2, colors: { upper:'#38bdf8', mid:'#94a3b8', lower:'#38bdf8' }, label: 'BB(20,2)' },
    };
    const indState = {
        ema20:  { series: null, visible: false },
        ema50:  { series: null, visible: false },
        ema200: { series: null, visible: false },
        bb:     { upper: null, mid: null, lower: null, fill: null, visible: false },
    };

    // ── Drawing state ──────────────────────────────────────────────────────
    let drawMode     = 'none';   // 'none' | 'trendline' | 'fibonacci' | 'ruler'
    let pendingPoint = null;     // first click {time, price}
    let drawings     = [];       // [{type, series?, priceLines?}]

    const WINDOW_MS = {
        '1m': 2000*60_000, '5m': 2000*300_000, '15m': 2000*900_000,
        '1h': 1000*3_600_000, '4h': 500*14_400_000, '1d': 365*86_400_000,
    };
    const DRAW_STYLE = { lineWidth: 1, lineStyle: LightweightCharts.LineStyle.Dashed };

    function tfToSeconds(tf) {
        return { '1m':60,'5m':300,'15m':900,'1h':3600,'4h':14400,'1d':86400 }[tf] || 3600;
    }
    function fmt4(v) { return Number(v).toFixed(4); }

    // Volume colors — distinct from green/red candles
    function volColor(bullish) {
        return bullish ? 'rgba(56,189,248,0.45)' : 'rgba(251,146,60,0.45)';  // sky / orange
    }

    // ── Chart init ─────────────────────────────────────────────────────────
    function initChart() {
        const container = document.getElementById('chart-container');
        chart = LightweightCharts.createChart(container, {
            layout:    { background: { color: '#0f1923' }, textColor: '#9ca3af' },
            grid:      { vertLines: { color: '#1f2937' }, horzLines: { color: '#1f2937' } },
            crosshair: { mode: LightweightCharts.CrosshairMode.Normal },
            rightPriceScale: { borderColor: '#374151' },
            timeScale: { borderColor: '#374151', timeVisible: true, secondsVisible: false },
            handleScroll: { mouseWheel: true, pressedMouseMove: true },
            handleScale:  { mouseWheel: true, pinch: true, axisPressedMouseMove: true },
        });

        candleSeries = chart.addCandlestickSeries({
            upColor: '#22c55e', downColor: '#ef4444',
            borderUpColor: '#22c55e', borderDownColor: '#ef4444',
            wickUpColor:   '#22c55e', wickDownColor:   '#ef4444',
        });

        volumeSeries = chart.addHistogramSeries({
            priceFormat: { type: 'volume' }, priceScaleId: 'vol',
        });
        chart.priceScale('vol').applyOptions({ scaleMargins: { top: 0.85, bottom: 0 } });

        // ── Crosshair: OHLC tooltip + live ruler preview ───────────────────
        chart.subscribeCrosshairMove(param => {
            const legend = document.getElementById('chart-legend');
            if (!param.time || !param.seriesData) { legend.textContent = ''; return; }

            // Ruler live preview
            if (drawMode === 'ruler' && pendingPoint && param.point) {
                const price = candleSeries.coordinateToPrice(param.point.y);
                if (price !== null) {
                    const diff  = price - pendingPoint.price;
                    const pct   = ((diff / pendingPoint.price) * 100).toFixed(2);
                    const sign  = diff >= 0 ? '+' : '';
                    const bars  = Math.abs(Math.round((param.time - pendingPoint.time) / tfToSeconds(currentTf)));
                    const col   = diff >= 0 ? '#22c55e' : '#ef4444';
                    legend.innerHTML = `<span style="color:#a855f7">📏</span>&nbsp;`
                        + `<span style="color:${col}">${sign}${fmt4(diff)} (${sign}${pct}%)</span>`
                        + `&nbsp;<span style="color:#6b7280">· ${bars} барів</span>`;
                    return;
                }
            }

            const c = param.seriesData.get(candleSeries);
            if (!c) { legend.textContent = ''; return; }
            const sign = c.close >= c.open ? '+' : '';
            const diff = (c.close - c.open).toFixed(4);
            const pct  = (((c.close - c.open) / c.open) * 100).toFixed(2);
            const col  = c.close >= c.open ? '#22c55e' : '#ef4444';
            let html =
                `<span style="color:#e5e7eb">O</span> ${fmt4(c.open)}&nbsp;`+
                `<span style="color:#e5e7eb">H</span> ${fmt4(c.high)}&nbsp;`+
                `<span style="color:#e5e7eb">L</span> ${fmt4(c.low)}&nbsp;`+
                `<span style="color:#e5e7eb">C</span> ${fmt4(c.close)}&nbsp;`+
                `<span style="color:${col}">${sign}${diff} (${sign}${pct}%)</span>`;

            // Append visible indicator values
            [
                { key:'ema20',  s: indState.ema20.series,  col:'#facc15', lbl:'EMA20'  },
                { key:'ema50',  s: indState.ema50.series,  col:'#f97316', lbl:'EMA50'  },
                { key:'ema200', s: indState.ema200.series, col:'#c084fc', lbl:'EMA200' },
            ].forEach(({ s, col: c2, lbl }) => {
                if (!s) return;
                const v = param.seriesData.get(s);
                if (v) html += `&nbsp;&nbsp;<span style="color:${c2}">${lbl} ${fmt4(v.value)}</span>`;
            });
            if (indState.bb.visible && indState.bb.upper) {
                const u = param.seriesData.get(indState.bb.upper);
                const m = param.seriesData.get(indState.bb.mid);
                const l = param.seriesData.get(indState.bb.lower);
                if (u && m && l)
                    html += `&nbsp;&nbsp;<span style="color:#38bdf8">BB ${fmt4(l.value)} / ${fmt4(m.value)} / ${fmt4(u.value)}</span>`;
            }
            legend.innerHTML = html;
        });

        // ── Click handler for drawing tools ───────────────────────────────
        chart.subscribeClick(param => {
            if (drawMode === 'none' || !param.time || !param.point) return;
            const price = candleSeries.coordinateToPrice(param.point.y);
            if (price === null) return;
            const pt = { time: param.time, price };

            if (!pendingPoint) {
                // First click — store point, wait for second
                pendingPoint = pt;
                updateDrawToolbar();
                document.getElementById('chart-legend').innerHTML =
                    `<span style="color:#f59e0b">● Перша точка встановлена</span>&nbsp;`+
                    `<span style="color:#4b5563">— натисніть другу точку</span>`;
            } else {
                // Second click — commit drawing
                if (drawMode === 'trendline') drawTrendLine(pendingPoint, pt);
                if (drawMode === 'fibonacci')  drawFibonacci(pendingPoint, pt);
                if (drawMode === 'ruler')       drawRulerLine(pendingPoint, pt);
                pendingPoint = null;
                drawMode     = 'none';
                updateDrawToolbar();
            }
        });

        const ro = new ResizeObserver(() => chart.applyOptions({ width: container.clientWidth }));
        ro.observe(container);
    }

    // ── Drawing functions ──────────────────────────────────────────────────
    function sortPoints(p1, p2) {
        return p1.time <= p2.time ? [p1, p2] : [p2, p1];
    }

    function drawTrendLine(p1, p2) {
        const [a, b] = sortPoints(p1, p2);
        const s = chart.addLineSeries({
            color: '#f59e0b', lineWidth: 2,
            lastValueVisible: false, priceLineVisible: false, crosshairMarkerVisible: false,
        });
        s.setData([{ time: a.time, value: a.price }, { time: b.time, value: b.price }]);
        drawings.push({ type: 'trendline', series: s });
    }

    const FIB_LEVELS = [
        { r: 0,     col: '#ef4444', label: '0%'     },
        { r: 0.236, col: '#f59e0b', label: '23.6%'  },
        { r: 0.382, col: '#22c55e', label: '38.2%'  },
        { r: 0.5,   col: '#60a5fa', label: '50%'    },
        { r: 0.618, col: '#22c55e', label: '61.8%'  },
        { r: 0.786, col: '#f59e0b', label: '78.6%'  },
        { r: 1.0,   col: '#ef4444', label: '100%'   },
    ];

    function drawFibonacci(p1, p2) {
        const high = Math.max(p1.price, p2.price);
        const low  = Math.min(p1.price, p2.price);
        const diff = high - low;
        const priceLines = FIB_LEVELS.map(({ r, col, label }) => {
            const price = high - diff * r;
            return candleSeries.createPriceLine({
                price, color: col, lineWidth: 1,
                lineStyle: LightweightCharts.LineStyle.Dashed,
                axisLabelVisible: true,
                title: `Fib ${label}`,
            });
        });
        drawings.push({ type: 'fibonacci', priceLines });
    }

    function drawRulerLine(p1, p2) {
        const [a, b] = sortPoints(p1, p2);
        const diff   = b.price - a.price;
        const pct    = ((diff / a.price) * 100).toFixed(2);
        const sign   = diff >= 0 ? '+' : '';
        const bars   = Math.abs(Math.round((b.time - a.time) / tfToSeconds(currentTf)));

        const s = chart.addLineSeries({
            color: '#a855f7', lineWidth: 1,
            lineStyle: LightweightCharts.LineStyle.Dashed,
            lastValueVisible: false, priceLineVisible: false, crosshairMarkerVisible: false,
        });
        s.setData([{ time: a.time, value: a.price }, { time: b.time, value: b.price }]);

        // Label at the end point
        const pl = s.createPriceLine({
            price: b.price, color: '#a855f7', lineWidth: 0,
            axisLabelVisible: true,
            title: `${sign}${pct}% (${bars}б)`,
        });
        drawings.push({ type: 'ruler', series: s, priceLine: pl });
    }

    function undoLastDrawing() {
        if (!drawings.length) return;
        removeDrawing(drawings.pop());
    }

    function clearDrawings() {
        drawings.forEach(removeDrawing);
        drawings = [];
        pendingPoint = null;
        drawMode = 'none';
        updateDrawToolbar();
    }

    function removeDrawing(d) {
        try { if (d.series)     chart.removeSeries(d.series); } catch(_) {}
        try { if (d.priceLine)  d.series?.removePriceLine?.(d.priceLine); } catch(_) {}
        try { if (d.priceLines) d.priceLines.forEach(pl => candleSeries.removePriceLine(pl)); } catch(_) {}
    }

    function updateDrawToolbar() {
        document.querySelectorAll('.draw-btn').forEach(btn => {
            const on = btn.dataset.mode === drawMode;
            btn.style.borderColor = on ? '#f59e0b' : '#374151';
            btn.style.color       = on ? '#f59e0b' : '#9ca3af';
            btn.style.background  = on ? 'rgba(245,158,11,0.1)' : '#111827';
        });
        // Crosshair cursor when drawing active
        document.getElementById('chart-container').style.cursor =
            drawMode !== 'none' ? 'crosshair' : '';
        // Pending indicator in pending point
        if (!pendingPoint && drawMode === 'none') {
            // clear legend only if it shows the pending message
        }
    }

    // ── Indicator math ─────────────────────────────────────────────────────
    function calcEMA(candles, period) {
        if (candles.length < period) return [];
        const k   = 2 / (period + 1);
        const out = [];
        let ema = candles.slice(0, period).reduce((s, c) => s + c.close, 0) / period;
        out.push({ time: candles[period - 1].time, value: ema });
        for (let i = period; i < candles.length; i++) {
            ema = candles[i].close * k + ema * (1 - k);
            out.push({ time: candles[i].time, value: ema });
        }
        return out;
    }

    function calcBB(candles, period, mult) {
        const upper = [], mid = [], lower = [];
        for (let i = period - 1; i < candles.length; i++) {
            const slice = candles.slice(i - period + 1, i + 1);
            const mean  = slice.reduce((s, c) => s + c.close, 0) / period;
            const variance = slice.reduce((s, c) => s + (c.close - mean) ** 2, 0) / period;
            const sd    = Math.sqrt(variance);
            const t     = candles[i].time;
            upper.push({ time: t, value: mean + mult * sd });
            mid.push(  { time: t, value: mean });
            lower.push({ time: t, value: mean - mult * sd });
        }
        return { upper, mid, lower };
    }

    // ── Indicator toggle ───────────────────────────────────────────────────
    function toggleIndicator(name) {
        const st  = indState[name];
        st.visible = !st.visible;

        if (name === 'bb') {
            if (st.visible) {
                _renderBB();
            } else {
                _removeBBSeries();
            }
        } else {
            if (st.visible) {
                _renderEMA(name);
            } else {
                if (st.series) { try { chart.removeSeries(st.series); } catch(_) {} st.series = null; }
            }
        }
        _updateIndBtn(name);
    }

    function _renderEMA(name) {
        const cfg  = IND_CFG[name];
        const data = calcEMA(cachedCandles, cfg.period);
        if (!data.length) return;
        const st = indState[name];
        if (st.series) { try { chart.removeSeries(st.series); } catch(_) {} }
        st.series = chart.addLineSeries({
            color: cfg.color, lineWidth: 1,
            lastValueVisible: true, priceLineVisible: false,
            crosshairMarkerVisible: false,
            title: cfg.label,
        });
        st.series.setData(data);
        // Store last value for incremental updates
        st.lastEma = data[data.length - 1].value;
    }

    function _renderBB() {
        const cfg  = IND_CFG.bb;
        const { upper, mid, lower } = calcBB(cachedCandles, cfg.period, cfg.mult);
        if (!upper.length) return;
        _removeBBSeries();
        const st = indState.bb;

        const lineOpts = (color, title) => ({
            color, lineWidth: 1, lastValueVisible: true,
            priceLineVisible: false, crosshairMarkerVisible: false, title,
        });
        st.upper = chart.addLineSeries(lineOpts(cfg.colors.upper, 'BB↑'));
        st.mid   = chart.addLineSeries(lineOpts(cfg.colors.mid,   'BB mid'));
        st.lower = chart.addLineSeries(lineOpts(cfg.colors.lower, 'BB↓'));
        st.upper.setData(upper);
        st.mid.setData(mid);
        st.lower.setData(lower);
    }

    function _removeBBSeries() {
        const st = indState.bb;
        ['upper','mid','lower'].forEach(k => {
            if (st[k]) { try { chart.removeSeries(st[k]); } catch(_) {} st[k] = null; }
        });
    }

    function _updateIndBtn(name) {
        const btn = document.querySelector(`.ind-btn[data-ind="${name}"]`);
        if (!btn) return;
        const on = indState[name].visible;
        const colMap = { ema20:'#facc15', ema50:'#f97316', ema200:'#c084fc', bb:'#38bdf8' };
        btn.style.borderColor = on ? colMap[name]  : '#374151';
        btn.style.color       = on ? colMap[name]  : '#9ca3af';
        btn.style.background  = on ? `${colMap[name]}18` : '#111827';
    }

    // Full re-render of all visible indicators (called only on loadData / TF change)
    function refreshIndicators() {
        ['ema20','ema50','ema200'].forEach(name => {
            if (indState[name].visible) {
                if (indState[name].series) { try { chart.removeSeries(indState[name].series); } catch(_) {} indState[name].series = null; }
                _renderEMA(name);
            }
        });
        if (indState.bb.visible) { _removeBBSeries(); _renderBB(); }
    }

    // Lightweight incremental update — only push new/updated points to existing series
    // Called from refreshData() instead of full refreshIndicators()
    function updateIndicatorsIncremental(updatedCandles) {
        ['ema20','ema50','ema200'].forEach(name => {
            const st  = indState[name];
            if (!st.visible || !st.series || st.lastEma === undefined) return;
            const k   = 2 / (IND_CFG[name].period + 1);
            // Only iterate the few new/changed candles
            const sorted = [...updatedCandles].sort((a, b) => a.time - b.time);
            sorted.forEach(c => {
                st.lastEma = c.close * k + st.lastEma * (1 - k);
                st.series.update({ time: c.time, value: st.lastEma });
            });
        });
        // BB: skip incremental (20-period window needs recalc from slice — too complex for
        // 5-candle refresh; visible BB stays frozen until next full loadData)
    }

    // ── Volume toggle ──────────────────────────────────────────────────────
    function toggleVolume() {
        volumeVisible = !volumeVisible;
        volumeSeries.applyOptions({ visible: volumeVisible });
        chart.priceScale('vol').applyOptions({
            scaleMargins: volumeVisible ? { top: 0.85, bottom: 0 } : { top: 1, bottom: 0 },
        });
        const btn = document.getElementById('btn-vol');
        btn.style.borderColor = volumeVisible ? '#374151' : '#374151';
        btn.style.color       = volumeVisible ? '#9ca3af' : '#4b5563';
        btn.style.textDecoration = volumeVisible ? 'none' : 'line-through';
    }

    // ── Helpers ────────────────────────────────────────────────────────────
    function showLoading(v) {
        document.getElementById('chart-loading').style.display = v ? 'flex' : 'none';
    }
    function showError(msg) {
        const el = document.getElementById('chart-error');
        el.textContent = msg;
        el.style.display = msg ? 'flex' : 'none';
    }
    function setRefreshBtnState(busy) {
        const btn = document.getElementById('btn-refresh');
        btn.textContent = busy ? '↻ …' : '↻ Оновити';
        btn.disabled    = busy;
        btn.style.color = busy ? '#4b5563' : '#d1d5db';
    }
    function tradesUrl() {
        return `/chart-api/trades?exchange=${encodeURIComponent(currentExchange)}&symbol=${encodeURIComponent(currentSymbol)}`
            + (currentBotId   ? `&bot_id=${currentBotId}` : '')
            + (sessionStartTs ? `&from=${sessionStartTs}` : '');
    }

    // ── Full load ──────────────────────────────────────────────────────────
    async function loadData(goToEnd = true) {
        showLoading(true);
        showError('');
        clearDrawings();
        cachedCandles = [];
        cachedVolume  = [];

        const toMs   = Date.now();
        const fromMs = toMs - (WINDOW_MS[currentTf] || WINDOW_MS['1h']);

        try {
            const [candlesResp, tradesResp] = await Promise.all([
                fetch(`/chart-api/candles?exchange=${encodeURIComponent(currentExchange)}&symbol=${encodeURIComponent(currentSymbol)}&tf=${currentTf}&from=${fromMs}&to=${toMs}`),
                fetch(tradesUrl()),
            ]);

            if (!candlesResp.ok) throw new Error(`Candles: ${candlesResp.status}`);
            const candles = await candlesResp.json();
            if (candles.error) throw new Error(candles.error);
            if (!Array.isArray(candles) || !candles.length) {
                showError('Немає даних для цього символу / таймфрейму');
                return;
            }

            candles.sort((a, b) => a.time - b.time);
            cachedCandles = candles.map(c => ({ time: c.time, open: c.open, high: c.high, low: c.low, close: c.close }));
            cachedVolume  = candles.map(c => ({
                time: c.time, value: c.volume, color: volColor(c.close >= c.open),
            }));

            candleSeries.setData(cachedCandles);
            volumeSeries.setData(cachedVolume);

            if (tradesResp.ok) {
                currentTrades = await tradesResp.json();
                if (Array.isArray(currentTrades)) buildMarkers(currentTrades);
            }

            refreshIndicators();
            if (goToEnd) chart.timeScale().scrollToRealTime();

        } catch (err) {
            showError('Помилка: ' + err.message);
        } finally {
            showLoading(false);
        }
    }

    // ── Incremental refresh ────────────────────────────────────────────────
    async function refreshData() {
        if (isRefreshing || !cachedCandles.length) return;
        isRefreshing = true;
        setRefreshBtnState(true);

        const toMs   = Date.now();
        const tfMs   = tfToSeconds(currentTf) * 1000;
        const fromMs = toMs - tfMs * 5;

        try {
            const resp = await fetch(
                `/chart-api/candles?exchange=${encodeURIComponent(currentExchange)}&symbol=${encodeURIComponent(currentSymbol)}&tf=${currentTf}&from=${fromMs}&to=${toMs}`
            );
            if (!resp.ok) return;
            const newCandles = await resp.json();
            if (!Array.isArray(newCandles) || !newCandles.length) return;

            newCandles.sort((a, b) => a.time - b.time);
            for (const c of newCandles) {
                const bar = { time: c.time, open: c.open, high: c.high, low: c.low, close: c.close };
                const vol = { time: c.time, value: c.volume, color: volColor(c.close >= c.open) };
                candleSeries.update(bar);
                volumeSeries.update(vol);
                const idx = cachedCandles.findIndex(x => x.time === c.time);
                if (idx >= 0) { cachedCandles[idx] = bar; cachedVolume[idx] = vol; }
                else          { cachedCandles.push(bar);  cachedVolume.push(vol);  }
            }

            const tResp = await fetch(tradesUrl());
            if (tResp.ok) {
                const trades = await tResp.json();
                if (Array.isArray(trades)) { currentTrades = trades; buildMarkers(trades); }
            }

            // Incremental indicator update (no series destroy/recreate)
            updateIndicatorsIncremental(newCandles);

        } catch (_) {
        } finally {
            isRefreshing = false;
            setRefreshBtnState(false);
        }
    }

    // ── Trade markers ──────────────────────────────────────────────────────
    function buildMarkers(trades) {
        if (!trades.length) { candleSeries.setMarkers([]); return; }
        const tfSec = tfToSeconds(currentTf);
        const snap  = t => t ? Math.floor(t / tfSec) * tfSec : null;
        const marks = [];
        trades.forEach(t => {
            const eSnap = snap(t.entry_time);
            const xSnap = snap(t.exit_time);
            if (eSnap) marks.push({
                time: eSnap, position: t.side==='long'?'belowBar':'aboveBar',
                color: '#ffffff', shape: t.side==='long'?'arrowUp':'arrowDown',
                text: t.side==='long'?'BUY':'SELL', size: 1.2,
            });
            if (xSnap) {
                const win = t.pnl_usdt >= 0;
                marks.push({
                    time: xSnap, position: t.side==='long'?'aboveBar':'belowBar',
                    color: win?'#f59e0b':'#a855f7', shape: 'circle',
                    text: `${win?'+':''}${t.pnl_usdt.toFixed(2)}$`, size: 1,
                });
            }
        });
        marks.sort((a, b) => a.time - b.time);
        candleSeries.setMarkers(marks);
    }

    // ── Auto-refresh ───────────────────────────────────────────────────────
    window.setAutoRefresh = function(sec) {
        clearInterval(autoRefreshTimer);
        document.getElementById('auto-label').textContent =
            { 0:'Off', 10:'10s', 30:'30s', 60:'1хв' }[sec] ?? 'Off';
        if (sec > 0) autoRefreshTimer = setInterval(refreshData, sec * 1000);
    };

    // ── UI events ──────────────────────────────────────────────────────────
    document.querySelectorAll('.tf-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tf-btn').forEach(b => {
                b.style.background  = '#111827'; b.style.color = '#9ca3af'; b.style.borderColor = '#374151';
            });
            btn.style.background  = '#1e3a5f';
            btn.style.color       = '#60a5fa';
            btn.style.borderColor = '#3b82f6';
            currentTf = btn.dataset.tf;
            loadData(true);
        });
    });

    document.querySelectorAll('.ind-btn').forEach(btn => {
        btn.addEventListener('click', () => toggleIndicator(btn.dataset.ind));
    });

    document.querySelectorAll('.draw-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const mode = btn.dataset.mode;
            drawMode     = drawMode === mode ? 'none' : mode;  // toggle off if same
            pendingPoint = null;
            updateDrawToolbar();
        });
    });

    // ── Exchange / coin selectors ──────────────────────────────────────────
    const symbolsMap = JSON.parse(
        document.getElementById('symbols-map-data').textContent
    );

    /** Short display name: "HYPE/USDT:USDT" → "HYPE" */
    function coinLabel(sym) {
        return sym.split('/')[0];
    }

    /** Populate coin <select> for a given exchange, pre-select currentSymbol. */
    function populateCoinSelect(exchange) {
        const sel  = document.getElementById('chart-coin');
        const syms = symbolsMap[exchange] || [];
        sel.innerHTML = '';
        syms.forEach(sym => {
            const opt = document.createElement('option');
            opt.value = sym;
            opt.textContent = coinLabel(sym);
            if (sym === currentSymbol) opt.selected = true;
            sel.appendChild(opt);
        });
        // If nothing matched, fall back to first option
        if (sel.value === '') {
            if (sel.options.length) {
                sel.options[0].selected = true;
                currentSymbol = sel.options[0].value;
            }
        } else {
            currentSymbol = sel.value;
        }
    }

    // Init coin select on page load
    populateCoinSelect(currentExchange);

    document.getElementById('chart-exchange').addEventListener('change', function () {
        currentExchange = this.value;
        currentBotId    = null;
        populateCoinSelect(currentExchange);
        loadData(true);
    });

    document.getElementById('chart-coin').addEventListener('change', function () {
        currentSymbol = this.value;
        currentBotId  = null;
        loadData(true);
    });

    document.getElementById('btn-vol').addEventListener('click', toggleVolume);
    document.getElementById('btn-undo').addEventListener('click', undoLastDrawing);
    document.getElementById('btn-clear-drawings').addEventListener('click', clearDrawings);
    document.getElementById('btn-refresh').addEventListener('click', refreshData);
    document.getElementById('btn-go-start').addEventListener('click', () => chart.timeScale().scrollToPosition(-1e10, false));
    document.getElementById('btn-go-end').addEventListener('click',   () => chart.timeScale().scrollToRealTime());
    document.getElementById('btn-fit').addEventListener('click',       () => chart.timeScale().fitContent());

    // Escape — cancel active drawing
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && drawMode !== 'none') {
            pendingPoint = null; drawMode = 'none'; updateDrawToolbar();
        }
    });

    // ── Boot ───────────────────────────────────────────────────────────────
    initChart();
    loadData(true);

})();
</script>

</x-filament-panels::page>
