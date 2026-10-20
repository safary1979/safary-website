<x-filament-panels::page>

<style>
    .cw { display:flex; flex-direction:column; background:#0f1923; border:1px solid #1f2937; border-radius:0.5rem; }
    .cw:fullscreen { border:0; border-radius:0; height:100vh; }
    .cw-top, .cw-bottom { display:flex; align-items:center; gap:0.4rem; flex-wrap:wrap; padding:0.4rem 0.5rem; background:#111827; }
    .cw-top { border-bottom:1px solid #1f2937; border-radius:0.5rem 0.5rem 0 0; }
    .cw-bottom { border-top:1px solid #1f2937; border-radius:0 0 0.5rem 0.5rem; }
    .cw-body { display:flex; height:calc(100vh - 250px); min-height:420px; }
    .cw:fullscreen .cw-body { flex:1; height:auto; }
    .cw-tools { display:flex; flex-direction:column; gap:0.3rem; padding:0.4rem 0.3rem; border-right:1px solid #1f2937; background:#111827; }
    .cw-main { position:relative; flex:1; min-width:0; }
    #chart-canvas { position:absolute; inset:0; }
    .cw-btn { font-size:0.75rem; padding:0.28rem 0.6rem; border-radius:0.4rem; cursor:pointer; border:1px solid #374151; background:#111827; color:#9ca3af; white-space:nowrap; }
    .cw-btn:hover { color:#e5e7eb; }
    .cw-btn:disabled { opacity:0.5; cursor:default; }
    .cw-btn.on { border-color:#3b82f6; background:#1e3a5f; color:#60a5fa; }
    .cw-tool { width:2rem; height:2rem; padding:0; display:flex; align-items:center; justify-content:center; font-size:0.95rem; }
    .cw-tool.on { border-color:#f59e0b; background:rgba(245,158,11,0.12); color:#f59e0b; }
    .cw-sel { background:#111827; color:#e5e7eb; border:1px solid #374151; border-radius:0.4rem; padding:0.3rem 0.5rem; font-size:0.8rem; cursor:pointer; }
    .cw-menu { position:absolute; top:100%; margin-top:0.25rem; min-width:13rem; background:#111827; border:1px solid #374151; border-radius:0.4rem; z-index:50; padding:0.35rem; }
    .cw-menu label { display:flex; align-items:center; gap:0.5rem; padding:0.3rem 0.4rem; font-size:0.78rem; color:#d1d5db; cursor:pointer; border-radius:0.3rem; }
    .cw-menu label:hover { background:#1f2937; }
    .cw-menu .sw { width:0.7rem; height:0.7rem; border-radius:2px; display:inline-block; }
    .cw-sep { width:1px; height:1.2rem; background:#1f2937; margin:0 0.1rem; }
    #chart-legend { position:absolute; top:0.4rem; left:0.6rem; z-index:6; pointer-events:none; font-size:0.75rem; font-family:monospace; color:#9ca3af; line-height:1.5; max-width:calc(100% - 6rem); }
    #chart-loading, #chart-error { position:absolute; inset:0; align-items:center; justify-content:center; z-index:10; font-size:0.85rem; text-align:center; padding:1rem; }
    #chart-loading { display:flex; background:rgba(15,25,35,0.85); color:#60a5fa; }
    #chart-error { display:none; background:rgba(15,25,35,0.9); color:#ef4444; }
</style>

<div id="chart-root" class="cw">

    {{-- ─── Top bar ─────────────────────────────────────────────── --}}
    <div class="cw-top">
        <select id="chart-exchange" class="cw-sel" title="Біржа">
            @foreach(array_keys($symbolsByExchange) as $exch)
                <option value="{{ $exch }}" {{ $exch === $exchange ? 'selected' : '' }}>{{ strtoupper($exch) }}</option>
            @endforeach
        </select>
        <select id="chart-coin" class="cw-sel" title="Символ"></select>
        <script id="symbols-map-data" type="application/json">@json($symbolsByExchange)</script>

        <span class="cw-sep"></span>

        @foreach(['1m','5m','15m','1h','4h','1d'] as $t)
            <button type="button" class="cw-btn tf-btn {{ $t === $tf ? 'on' : '' }}" data-tf="{{ $t }}">{{ $t }}</button>
        @endforeach

        <span class="cw-sep"></span>

        {{-- Indicators --}}
        <div x-data="{ open: false }" style="position:relative;">
            <button type="button" class="cw-btn" @click="open = !open">Індикатори ▾</button>
            <div class="cw-menu" x-show="open" x-cloak @click.outside="open = false" style="left:0;">
                <label><input type="checkbox" class="ind-chk" data-ind="ema20"><span class="sw" style="background:#facc15"></span>EMA 20</label>
                <label><input type="checkbox" class="ind-chk" data-ind="ema50"><span class="sw" style="background:#f97316"></span>EMA 50</label>
                <label><input type="checkbox" class="ind-chk" data-ind="ema200"><span class="sw" style="background:#c084fc"></span>EMA 200</label>
                <label><input type="checkbox" class="ind-chk" data-ind="bb"><span class="sw" style="background:#38bdf8"></span>Bollinger (20, 2)</label>
                <label><input type="checkbox" id="chk-vol" checked><span class="sw" style="background:#38bdf8;opacity:.6"></span>Об'єм</label>
            </div>
        </div>

        {{-- Trades --}}
        <div x-data="{ open: false }" style="position:relative;">
            <button type="button" class="cw-btn" id="btn-trades" @click="open = !open">Угоди ▾</button>
            <div class="cw-menu" x-show="open" x-cloak @click.outside="open = false" style="left:0; min-width:19rem;">
                <label><input type="checkbox" id="chk-bot-trades"> Угоди ботів</label>
                <div style="padding:0.3rem 0.4rem;">
                    <div style="font-size:0.7rem; color:#6b7280; margin-bottom:0.25rem;">Replay-кандидат</div>
                    <select id="candidate-overlay-select" class="cw-sel" style="width:100%; font-size:0.75rem;">
                        <option value="">Replay: вимкнено</option>
                    </select>
                    <div id="candidate-overlay-status" style="font-size:0.68rem; color:#6b7280; margin-top:0.25rem; min-height:1rem;"></div>
                </div>
            </div>
        </div>

        @if($botName)
            <span style="font-size:0.72rem; color:#60a5fa; background:#1e3a5f22; border:1px solid #1e3a5f; border-radius:0.375rem; padding:0.15rem 0.5rem;">
                {{ $botName }}
                <a href="/admin/chart-page?exchange={{ $exchange }}&symbol={{ urlencode($symbol) }}&tf={{ $tf }}"
                   style="color:#6b7280; text-decoration:none; margin-left:0.3rem;" title="Показати всіх">×</a>
            </span>
        @endif

        <div style="margin-left:auto; display:flex; gap:0.4rem; align-items:center;">
            <button type="button" class="cw-btn" id="btn-fullscreen" title="На весь екран">⛶</button>
            <button type="button" class="cw-btn" id="btn-refresh" title="Оновити нові свічки">↻</button>
            <div x-data="{ open: false }" style="position:relative;">
                <button type="button" class="cw-btn" @click="open = !open" title="Автооновлення">
                    <span id="auto-label">Off</span> ▾
                </button>
                <div class="cw-menu" x-show="open" x-cloak @click.outside="open = false" style="right:0; min-width:5rem;">
                    @foreach([0 => 'Off', 10 => '10s', 30 => '30s', 60 => '1хв'] as $sec => $label)
                        <label @click="setAutoRefresh({{ $sec }}); open = false">{{ $label }}</label>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Body: drawing tools + chart ─────────────────────────── --}}
    <div class="cw-body">
        <div class="cw-tools">
            <button type="button" class="cw-btn cw-tool draw-btn" data-mode="trendline" title="Лінія тренду — 2 кліки">╱</button>
            <button type="button" class="cw-btn cw-tool draw-btn" data-mode="fibonacci" title="Рівні Фібоначчі — 2 кліки">◈</button>
            <button type="button" class="cw-btn cw-tool draw-btn" data-mode="ruler" title="Лінійка % — 2 кліки">↔</button>
            <span style="height:1px; background:#1f2937; margin:0.2rem 0;"></span>
            <button type="button" class="cw-btn cw-tool" id="btn-undo" title="Скасувати останній малюнок">↩</button>
            <button type="button" class="cw-btn cw-tool" id="btn-clear-drawings" title="Видалити всі малюнки">✕</button>
        </div>
        <div class="cw-main" id="chart-container">
            <div id="chart-canvas"></div>
            <div id="chart-legend"></div>
            <div id="chart-loading">Завантаження…</div>
            <div id="chart-error"></div>
        </div>
    </div>

    {{-- ─── Bottom bar: ranges ──────────────────────────────────── --}}
    <div class="cw-bottom">
        @foreach(['1D','5D','1M','3M','6M','1Y'] as $r)
            <button type="button" class="cw-btn range-btn" data-range="{{ $r }}" style="font-size:0.7rem; padding:0.2rem 0.5rem;">{{ $r }}</button>
        @endforeach
        <button type="button" class="cw-btn range-btn" data-range="all" style="font-size:0.7rem; padding:0.2rem 0.5rem;">Все</button>
        <div style="margin-left:auto; display:flex; gap:0.3rem;">
            <button type="button" class="cw-btn" id="btn-go-start" style="font-size:0.7rem; padding:0.2rem 0.5rem;" title="До початку даних">|◀</button>
            <button type="button" class="cw-btn" id="btn-go-end" style="font-size:0.7rem; padding:0.2rem 0.5rem;" title="До кінця (зараз)">▶|</button>
        </div>
    </div>
</div>

{{-- ─── TradingView Lightweight Charts ──────────────────────────── --}}
<script src="https://unpkg.com/lightweight-charts@4.2.0/dist/lightweight-charts.standalone.production.js"></script>
<script>
(function () {
    // ── State ──────────────────────────────────────────────────────────────
    let chart        = null;
    let candleSeries = null;
    let volumeSeries = null;
    let currentExchange  = '{{ $exchange }}';
    let currentSymbol    = '{{ $symbol }}';
    let currentTf        = '{{ $tf }}';
    let currentBotId     = {{ $botId ?? 'null' }};
    let currentBotRef    = @json($botRef);
    const sessionStartTs = {{ $sessionStart ?? 'null' }};
    let cachedCandles = [];
    let cachedVolume  = [];
    let currentTrades = [];
    let showBotTrades = currentBotId !== null || currentBotRef !== null;
    let currentCandidateId = '';
    let currentCandidateTrades = [];
    let candidateOverlayMeta = null;
    let currentCandidateSummary = null;
    let autoRefreshTimer = null;
    let isRefreshing     = false;
    let volumeVisible    = true;
    let positionOverlay  = { entry: null, sl: null, tp: null };
    let closedTradeOverlay = { entry: null, exit: null };
    let currentOpenPosition = null;

    const lsGet = k => { try { return localStorage.getItem(k); } catch (_) { return null; } };
    const lsSet = (k, v) => { try { localStorage.setItem(k, v); } catch (_) {} };

    // ── Indicator state ────────────────────────────────────────────────────
    const IND_CFG = {
        ema20:  { period: 20,  color: '#facc15', label: 'EMA 20'  },
        ema50:  { period: 50,  color: '#f97316', label: 'EMA 50'  },
        ema200: { period: 200, color: '#c084fc', label: 'EMA 200' },
        bb:     { period: 20,  mult: 2, colors: { upper: '#38bdf8', mid: '#7dd3fc', lower: '#38bdf8' } },
    };
    const indState = {
        ema20:  { series: null, visible: false },
        ema50:  { series: null, visible: false },
        ema200: { series: null, visible: false },
        bb:     { upper: null, mid: null, lower: null, visible: false },
    };

    // ── Drawing state ──────────────────────────────────────────────────────
    let drawMode     = 'none';
    let pendingPoint = null;
    let drawings     = [];

    const WINDOW_MS = {
        '1m': 2000*60_000, '5m': 2000*300_000, '15m': 2000*900_000,
        '1h': 5000*3_600_000, '4h': 1200*14_400_000, '1d': 365*86_400_000,
    };

    function tfToSeconds(tf) {
        return { '1m':60,'5m':300,'15m':900,'1h':3600,'4h':14400,'1d':86400 }[tf] || 3600;
    }
    function fmt4(v) { return Number(v).toFixed(4); }
    function volColor(bullish) {
        return bullish ? 'rgba(56,189,248,0.40)' : 'rgba(251,146,60,0.40)';
    }
    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, ch => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[ch]));
    }

    // ── Legend (overlay, top-left of chart) ────────────────────────────────
    function legendHead() {
        return `<span style="color:#e5e7eb;font-weight:600">${escapeHtml(currentSymbol.split('/')[0])}</span>`
            + `<span style="color:#6b7280"> · ${escapeHtml(currentTf)} · ${escapeHtml(currentExchange.toUpperCase())}</span>`;
    }

    function ohlcHtml(c) {
        const up   = c.close >= c.open;
        const sign = up ? '+' : '';
        const diff = (c.close - c.open).toFixed(4);
        const pct  = (((c.close - c.open) / c.open) * 100).toFixed(2);
        const col  = up ? '#22c55e' : '#ef4444';
        return `<span style="color:${col}">O</span> ${fmt4(c.open)}&nbsp;`
            + `<span style="color:${col}">H</span> ${fmt4(c.high)}&nbsp;`
            + `<span style="color:${col}">L</span> ${fmt4(c.low)}&nbsp;`
            + `<span style="color:${col}">C</span> ${fmt4(c.close)}&nbsp;`
            + `<span style="color:${col}">${sign}${diff} (${sign}${pct}%)</span>`;
    }

    function indicatorHtml(valueOf) {
        let html = '';
        ['ema20', 'ema50', 'ema200'].forEach(name => {
            const s = indState[name].series;
            if (!s) return;
            const v = valueOf(s);
            if (v) html += `<span style="color:${IND_CFG[name].color}">${IND_CFG[name].label} ${fmt4(v.value)}</span>&nbsp;&nbsp;`;
        });
        if (indState.bb.visible && indState.bb.upper) {
            const u = valueOf(indState.bb.upper), m = valueOf(indState.bb.mid), l = valueOf(indState.bb.lower);
            if (u && m && l) html += `<span style="color:#38bdf8">BB ${fmt4(l.value)} / ${fmt4(m.value)} / ${fmt4(u.value)}</span>`;
        }
        return html;
    }

    function renderDefaultLegend() {
        const legend = document.getElementById('chart-legend');
        if (pendingPoint) return;
        const last = cachedCandles[cachedCandles.length - 1];
        if (!last) { legend.innerHTML = legendHead(); return; }
        const lastBy = s => lastIndicatorValue(s);
        legend.innerHTML = legendHead() + '<br>' + ohlcHtml(last)
            + (hasIndicators() ? '<br>' + indicatorHtml(lastBy) : '');
    }

    // Last value per indicator series, kept alongside the series (no series.data() dependency)
    function lastIndicatorValue(series) {
        for (const name of ['ema20', 'ema50', 'ema200']) {
            const st = indState[name];
            if (st.series === series && st.lastEma !== undefined) return { value: st.lastEma };
        }
        const bb = indState.bb;
        if (bb.last) {
            if (bb.upper === series) return { value: bb.last.upper };
            if (bb.mid === series)   return { value: bb.last.mid };
            if (bb.lower === series) return { value: bb.last.lower };
        }
        return null;
    }

    function hasIndicators() {
        return Object.values(indState).some(s => s.visible);
    }

    // ── Chart init ─────────────────────────────────────────────────────────
    function initChart() {
        const container = document.getElementById('chart-canvas');
        chart = LightweightCharts.createChart(container, {
            autoSize: true,
            layout:    { background: { color: '#0f1923' }, textColor: '#9ca3af' },
            grid:      { vertLines: { color: '#1a2431' }, horzLines: { color: '#1a2431' } },
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
            lastValueVisible: false, priceLineVisible: false,
        });
        chart.priceScale('vol').applyOptions({ scaleMargins: { top: 0.85, bottom: 0 } });

        chart.subscribeCrosshairMove(param => {
            const legend = document.getElementById('chart-legend');
            if (!param.time || !param.seriesData) { renderDefaultLegend(); return; }

            if (drawMode === 'ruler' && pendingPoint && param.point) {
                const price = candleSeries.coordinateToPrice(param.point.y);
                if (price !== null) {
                    const diff = price - pendingPoint.price;
                    const pct  = ((diff / pendingPoint.price) * 100).toFixed(2);
                    const sign = diff >= 0 ? '+' : '';
                    const bars = Math.abs(Math.round((param.time - pendingPoint.time) / tfToSeconds(currentTf)));
                    const col  = diff >= 0 ? '#22c55e' : '#ef4444';
                    legend.innerHTML = legendHead() + '<br>'
                        + `<span style="color:#a855f7">↔</span>&nbsp;`
                        + `<span style="color:${col}">${sign}${fmt4(diff)} (${sign}${pct}%)</span>`
                        + `&nbsp;<span style="color:#6b7280">· ${bars} барів</span>`;
                    return;
                }
            }

            const c = param.seriesData.get(candleSeries);
            if (!c) { renderDefaultLegend(); return; }
            legend.innerHTML = legendHead() + '<br>' + ohlcHtml(c)
                + (hasIndicators() ? '<br>' + indicatorHtml(s => param.seriesData.get(s)) : '');
        });

        chart.subscribeClick(param => {
            if (drawMode === 'none' || !param.time || !param.point) return;
            const price = candleSeries.coordinateToPrice(param.point.y);
            if (price === null) return;
            const pt = { time: param.time, price };

            if (!pendingPoint) {
                pendingPoint = pt;
                updateDrawToolbar();
                document.getElementById('chart-legend').innerHTML = legendHead() + '<br>'
                    + `<span style="color:#f59e0b">● Перша точка встановлена</span>&nbsp;`
                    + `<span style="color:#6b7280">— натисніть другу точку</span>`;
            } else {
                if (drawMode === 'trendline') drawTrendLine(pendingPoint, pt);
                if (drawMode === 'fibonacci') drawFibonacci(pendingPoint, pt);
                if (drawMode === 'ruler')     drawRulerLine(pendingPoint, pt);
                pendingPoint = null;
                drawMode     = 'none';
                updateDrawToolbar();
                renderDefaultLegend();
            }
        });
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
        const priceLines = FIB_LEVELS.map(({ r, col, label }) => candleSeries.createPriceLine({
            price: high - diff * r, color: col, lineWidth: 1,
            lineStyle: LightweightCharts.LineStyle.Dashed,
            axisLabelVisible: true, title: `Fib ${label}`,
        }));
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
        const pl = s.createPriceLine({
            price: b.price, color: '#a855f7', lineWidth: 0,
            axisLabelVisible: true, title: `${sign}${pct}% (${bars}б)`,
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
        try { if (d.priceLines) d.priceLines.forEach(pl => candleSeries.removePriceLine(pl)); } catch(_) {}
    }

    function updateDrawToolbar() {
        document.querySelectorAll('.draw-btn').forEach(btn => {
            btn.classList.toggle('on', btn.dataset.mode === drawMode);
        });
        document.getElementById('chart-container').style.cursor = drawMode !== 'none' ? 'crosshair' : '';
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
    function saveIndicators() {
        lsSet('safary_chart_indicators', JSON.stringify(
            Object.keys(indState).filter(k => indState[k].visible)
        ));
    }

    function setIndicator(name, on) {
        const st = indState[name];
        st.visible = on;
        if (name === 'bb') {
            if (on) _renderBB(); else _removeBBSeries();
        } else if (on) {
            _renderEMA(name);
        } else if (st.series) {
            try { chart.removeSeries(st.series); } catch(_) {}
            st.series = null;
        }
        saveIndicators();
        renderDefaultLegend();
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
            crosshairMarkerVisible: false, title: cfg.label,
        });
        st.series.setData(data);
        st.lastEma = data[data.length - 1].value;
    }

    function _renderBB() {
        const cfg = IND_CFG.bb;
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
        st.last = { upper: upper.at(-1).value, mid: mid.at(-1).value, lower: lower.at(-1).value };
    }

    function _removeBBSeries() {
        const st = indState.bb;
        ['upper','mid','lower'].forEach(k => {
            if (st[k]) { try { chart.removeSeries(st[k]); } catch(_) {} st[k] = null; }
        });
    }

    // Full re-render of visible indicators (loadData / TF change)
    function refreshIndicators() {
        ['ema20','ema50','ema200'].forEach(name => {
            if (indState[name].visible) {
                if (indState[name].series) { try { chart.removeSeries(indState[name].series); } catch(_) {} indState[name].series = null; }
                _renderEMA(name);
            }
        });
        if (indState.bb.visible) { _removeBBSeries(); _renderBB(); }
    }

    // Incremental update for refreshData(); BB stays frozen until the next full load
    function updateIndicatorsIncremental(updatedCandles) {
        ['ema20','ema50','ema200'].forEach(name => {
            const st = indState[name];
            if (!st.visible || !st.series || st.lastEma === undefined) return;
            const k = 2 / (IND_CFG[name].period + 1);
            [...updatedCandles].sort((a, b) => a.time - b.time).forEach(c => {
                st.lastEma = c.close * k + st.lastEma * (1 - k);
                st.series.update({ time: c.time, value: st.lastEma });
            });
        });
    }

    // ── Volume toggle ──────────────────────────────────────────────────────
    function setVolume(on) {
        volumeVisible = on;
        volumeSeries.applyOptions({ visible: on });
        lsSet('safary_chart_volume', on ? '1' : '0');
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
        btn.textContent = busy ? '…' : '↻';
        btn.disabled    = busy;
    }

    async function manualRefresh() {
        if (isRefreshing) return;
        isRefreshing = true;
        setRefreshBtnState(true);
        try {
            await loadData(false);
        } finally {
            isRefreshing = false;
            setRefreshBtnState(false);
        }
    }

    function tradesUrl() {
        return `/chart-api/trades?exchange=${encodeURIComponent(currentExchange)}&symbol=${encodeURIComponent(currentSymbol)}`
            + (currentBotId   ? `&bot_id=${currentBotId}` : '')
            + (sessionStartTs ? `&from=${sessionStartTs}` : '');
    }

    function candidateTradesUrl() {
        const first = cachedCandles[0]?.time ?? null;
        const last = cachedCandles[cachedCandles.length - 1]?.time ?? null;
        return `/chart-api/candidate-trades?exchange=${encodeURIComponent(currentExchange)}&symbol=${encodeURIComponent(currentSymbol)}`
            + `&candidate=${encodeURIComponent(currentCandidateId)}`
            + (first ? `&from=${Math.floor(first * 1000)}` : '')
            + (last ? `&to=${Math.floor(last * 1000)}` : '');
    }

    function setCandidateOverlayStatus(text, color = '#6b7280') {
        const el = document.getElementById('candidate-overlay-status');
        if (!el) return;
        el.textContent = text || '';
        el.style.color = color;
    }

    function selectedCandidateOption() {
        const options = Array.isArray(candidateOverlayMeta?.options) ? candidateOverlayMeta.options : [];
        return options.find(option => option.id === currentCandidateId) || null;
    }

    function updateTradesButton() {
        const chk = document.getElementById('chk-bot-trades');
        if (chk) chk.checked = showBotTrades;
        const btn = document.getElementById('btn-trades');
        if (!btn) return;
        const active = showBotTrades || !!currentCandidateId;
        btn.classList.toggle('on', active);
        btn.textContent = active ? 'Угоди ● ▾' : 'Угоди ▾';
    }

    function clearPositionOverlay() {
        Object.keys(positionOverlay).forEach(key => {
            if (positionOverlay[key]) {
                try { candleSeries.removePriceLine(positionOverlay[key]); } catch (_) {}
                positionOverlay[key] = null;
            }
        });
        currentOpenPosition = null;
    }

    function clearClosedTradeOverlay() {
        Object.keys(closedTradeOverlay).forEach(key => {
            if (closedTradeOverlay[key]) {
                try { candleSeries.removePriceLine(closedTradeOverlay[key]); } catch (_) {}
                closedTradeOverlay[key] = null;
            }
        });
    }

    function drawPositionOverlay(position) {
        Object.keys(positionOverlay).forEach(key => {
            if (positionOverlay[key]) {
                try { candleSeries.removePriceLine(positionOverlay[key]); } catch (_) {}
                positionOverlay[key] = null;
            }
        });
        currentOpenPosition = position || null;
        if (!position || (!currentBotId && !currentBotRef)) {
            buildMarkers(currentTrades);
            return;
        }

        positionOverlay.entry = candleSeries.createPriceLine({
            price: position.entry_price, color: '#60a5fa', lineWidth: 2,
            lineStyle: LightweightCharts.LineStyle.Solid, axisLabelVisible: true, title: 'Entry',
        });
        if (position.stop_loss) {
            positionOverlay.sl = candleSeries.createPriceLine({
                price: position.stop_loss, color: '#ef4444', lineWidth: 1,
                lineStyle: LightweightCharts.LineStyle.Dashed, axisLabelVisible: true, title: 'SL',
            });
        }
        if (position.take_profit) {
            positionOverlay.tp = candleSeries.createPriceLine({
                price: position.take_profit, color: '#22c55e', lineWidth: 1,
                lineStyle: LightweightCharts.LineStyle.Dashed, axisLabelVisible: true, title: 'TP',
            });
        }
        buildMarkers(currentTrades);
    }

    async function loadOpenPosition() {
        clearPositionOverlay();
        if (!showBotTrades) return;

        if (currentBotRef) {
            await loadGatewayBotContext();
            return;
        }
        if (!currentBotId) return;

        try {
            const resp = await fetch(`/chart-api/position?bot_id=${currentBotId}`);
            if (!resp.ok) return;
            const data = await resp.json();
            drawPositionOverlay(data.position ?? null);
        } catch (_) {
        }
    }

    async function loadGatewayBotContext() {
        const botRefAtRequest = currentBotRef;
        try {
            const resp = await fetch(`/chart-api/bot-context?bot_ref=${encodeURIComponent(botRefAtRequest)}`);
            if (!resp.ok) throw new Error(`Bot context: ${resp.status}`);
            const data = await resp.json();
            if (botRefAtRequest !== currentBotRef) return;
            currentTrades = Array.isArray(data.closed_trades) ? data.closed_trades : [];
            drawPositionOverlay(data.position ?? null);
        } catch (error) {
            if (botRefAtRequest === currentBotRef) {
                showError('Помилка даних бота: ' + error.message);
            }
        }
    }

    async function loadTrades() {
        if (!showBotTrades) {
            currentTrades = [];
            buildMarkers([]);
            return;
        }
        if (currentBotRef) return;

        const exchangeAtRequest = currentExchange;
        const symbolAtRequest = currentSymbol;
        const botIdAtRequest = currentBotId;

        try {
            const resp = await fetch(tradesUrl());
            if (!resp.ok) return;
            const trades = await resp.json();
            if (!Array.isArray(trades)) return;
            if (
                exchangeAtRequest !== currentExchange ||
                symbolAtRequest !== currentSymbol ||
                botIdAtRequest !== currentBotId
            ) {
                return;
            }
            currentTrades = trades;
            buildMarkers(trades);
        } catch (_) {
        }
    }

    async function loadCandidateOptions() {
        const sel = document.getElementById('candidate-overlay-select');
        if (!sel) return;

        try {
            const resp = await fetch(`/chart-api/candidate-trades?meta=1&exchange=${encodeURIComponent(currentExchange)}&symbol=${encodeURIComponent(currentSymbol)}`);
            if (!resp.ok) return;
            const data = await resp.json();
            const options = Array.isArray(data.options) ? data.options : [];
            candidateOverlayMeta = data;

            sel.innerHTML = '<option value="">Replay: вимкнено</option>';
            options.forEach(option => {
                const opt = document.createElement('option');
                opt.value = option.id;
                const tag = option.live_tag ? `${option.live_tag} · ` : '';
                opt.textContent = `${tag}${option.side?.toUpperCase?.() || ''} · ${option.profile} · ${option.available_count} угод · ${option.id}`;
                sel.appendChild(opt);
            });

            const saved = lsGet('safary_chart_candidate_overlay');
            const candidateIds = new Set(options.map(option => option.id));
            currentCandidateId = saved === '__off__' ? '' : (candidateIds.has(saved) ? saved : '');
            if (currentCandidateId) {
                sel.value = currentCandidateId;
                currentCandidateSummary = selectedCandidateOption();
            } else {
                currentCandidateSummary = null;
                sel.value = '';
            }
            updateTradesButton();
            await loadCandidateTrades();
        } catch (_) {
        }
    }

    async function loadCandidateTrades() {
        if (!currentCandidateId || !cachedCandles.length) {
            currentCandidateTrades = [];
            currentCandidateSummary = selectedCandidateOption();
            buildMarkers(currentTrades);
            setCandidateOverlayStatus(currentCandidateId ? 'нема свічок' : '');
            return;
        }

        const candidateAtRequest = currentCandidateId;
        const exchangeAtRequest = currentExchange;
        const symbolAtRequest = currentSymbol;

        try {
            const resp = await fetch(candidateTradesUrl());
            if (!resp.ok) {
                setCandidateOverlayStatus('помилка replay', '#f97316');
                return;
            }
            const data = await resp.json();
            if (
                candidateAtRequest !== currentCandidateId ||
                exchangeAtRequest !== currentExchange ||
                symbolAtRequest !== currentSymbol
            ) {
                return;
            }
            const trades = Array.isArray(data.trades) ? data.trades : [];
            currentCandidateTrades = trades;
            currentCandidateSummary = data.candidate || null;
            buildMarkers(currentTrades);
            const tag = data.candidate?.live_tag ? `${data.candidate.live_tag} · ` : '';
            const label = data.candidate?.profile ? `${tag}replay ${trades.length} · ${data.candidate.profile}` : `${tag}replay ${trades.length}`;
            setCandidateOverlayStatus(label, trades.length ? '#38bdf8' : '#6b7280');
        } catch (_) {
            setCandidateOverlayStatus('помилка replay', '#f97316');
        }
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
            const candlesResp = await fetch(
                `/chart-api/candles?exchange=${encodeURIComponent(currentExchange)}&symbol=${encodeURIComponent(currentSymbol)}&tf=${currentTf}&from=${fromMs}&to=${toMs}`
            );

            if (!candlesResp.ok) throw new Error(`Candles: ${candlesResp.status}`);
            const candles = await candlesResp.json();
            if (candles.error) throw new Error(candles.error);
            if (!Array.isArray(candles) || !candles.length) {
                candleSeries.setData([]);
                volumeSeries.setData([]);
                showError('Немає даних для цього символу / таймфрейму');
                renderDefaultLegend();
                return;
            }

            candles.sort((a, b) => a.time - b.time);
            cachedCandles = candles.map(c => ({ time: c.time, open: c.open, high: c.high, low: c.low, close: c.close }));
            cachedVolume  = candles.map(c => ({
                time: c.time, value: c.volume, color: volColor(c.close >= c.open),
            }));

            candleSeries.setData(cachedCandles);
            volumeSeries.setData(cachedVolume);
            candleSeries.setMarkers([]);

            refreshIndicators();
            if (goToEnd) chart.timeScale().scrollToRealTime();
            renderDefaultLegend();
            showLoading(false);

            loadTrades();
            loadOpenPosition();
            loadCandidateTrades();

        } catch (err) {
            showError('Помилка: ' + err.message);
        } finally {
            showLoading(false);
        }
    }

    // ── Incremental refresh ────────────────────────────────────────────────
    async function refreshData() {
        if (isRefreshing) return;
        if (!cachedCandles.length) {
            await manualRefresh();
            return;
        }
        isRefreshing = true;
        setRefreshBtnState(true);

        const toMs   = Date.now();
        const tfMs   = tfToSeconds(currentTf) * 1000;
        const fromMs = toMs - tfMs * 5;

        try {
            const resp = await fetch(
                `/chart-api/candles?exchange=${encodeURIComponent(currentExchange)}&symbol=${encodeURIComponent(currentSymbol)}&tf=${currentTf}&from=${fromMs}&to=${toMs}`
            );
            if (!resp.ok) throw new Error(`Candles: ${resp.status}`);
            const newCandles = await resp.json();
            if (newCandles.error) throw new Error(newCandles.error);
            if (!Array.isArray(newCandles) || !newCandles.length) throw new Error('Немає нових свічок');

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

            loadTrades();
            loadOpenPosition();
            loadCandidateTrades();
            updateIndicatorsIncremental(newCandles);
            renderDefaultLegend();

        } catch (error) {
            showError('Помилка оновлення: ' + error.message);
        } finally {
            isRefreshing = false;
            setRefreshBtnState(false);
        }
    }

    // ── Trade markers ──────────────────────────────────────────────────────
    function buildMarkers(trades) {
        const marks = [];
        const tfSec = tfToSeconds(currentTf);
        const snap  = t => t ? Math.floor(t / tfSec) * tfSec : null;
        const fmtPrice = p => {
            const v = Number(p || 0);
            if (!v) return '—';
            if (v >= 1000) return v.toFixed(2);
            if (v >= 10) return v.toFixed(3);
            return v.toFixed(4);
        };
        clearClosedTradeOverlay();
        (trades || []).forEach(t => {
            const eSnap = snap(t.entry_time);
            const xSnap = snap(t.marker_time || t.exit_time);
            if (eSnap) marks.push({
                time: eSnap, position: t.side==='long'?'belowBar':'aboveBar',
                color: '#ffffff', shape: t.side==='long'?'arrowUp':'arrowDown',
                text: `${t.side==='long'?'BUY':'SELL'} ${fmtPrice(t.entry_price)}`, size: 1.2,
            });
            if (xSnap) {
                const win = t.pnl_usdt >= 0;
                const label = (t.exit_reason || '').includes('take_profit') ? 'TP'
                    : ((t.exit_reason || '').includes('stop_loss') ? 'SL' : 'EXIT');
                const move = Number(t.price_move_pct || 0);
                const moveLabel = `${move >= 0 ? '+' : ''}${move.toFixed(2)}%`;
                marks.push({
                    time: xSnap, position: t.side==='long'?'aboveBar':'belowBar',
                    color: win?'#f59e0b':'#a855f7', shape: 'circle',
                    text: `${label} ${fmtPrice(t.exit_price)} ${moveLabel} ${win?'+':''}${t.pnl_usdt.toFixed(2)}$`, size: 1,
                });
            }
        });

        if (!currentOpenPosition && trades?.length) {
            const last = [...trades].filter(t => t.entry_price && t.exit_price).sort((a, b) => (b.exit_time || 0) - (a.exit_time || 0))[0];
            if (last) {
                const win = last.pnl_usdt >= 0;
                const exitLabel = (last.exit_reason || '').includes('take_profit') ? 'TP'
                    : ((last.exit_reason || '').includes('stop_loss') ? 'SL' : 'Exit');
                const move = Number(last.price_move_pct || 0);
                const moveLabel = `${move >= 0 ? '+' : ''}${move.toFixed(2)}%`;
                const sideLabel = (last.side || '').toUpperCase();
                closedTradeOverlay.entry = candleSeries.createPriceLine({
                    price: Number(last.entry_price), color: '#60a5fa', lineWidth: 1,
                    lineStyle: LightweightCharts.LineStyle.Dotted, axisLabelVisible: true,
                    title: `#${last.id} ${sideLabel} Entry ${fmtPrice(last.entry_price)}`,
                });
                closedTradeOverlay.exit = candleSeries.createPriceLine({
                    price: Number(last.exit_price), color: win ? '#22c55e' : '#ef4444', lineWidth: 1,
                    lineStyle: LightweightCharts.LineStyle.Dashed, axisLabelVisible: true,
                    title: `#${last.id} ${exitLabel} ${fmtPrice(last.exit_price)} ${moveLabel}`,
                });
            }
        }

        if (currentOpenPosition?.opened_at) {
            const entrySnap = snap(currentOpenPosition.opened_at);
            if (entrySnap) {
                const isLong = (currentOpenPosition.side ?? '') === 'long';
                marks.push({
                    time: entrySnap, position: isLong ? 'belowBar' : 'aboveBar',
                    color: '#60a5fa', shape: isLong ? 'arrowUp' : 'arrowDown',
                    text: isLong ? 'BUY' : 'SELL', size: 1.3,
                });
            }
        }

        (currentCandidateTrades || []).forEach(t => {
            const eSnap = snap(t.entry_time);
            const xSnap = snap(t.marker_time || t.exit_time);
            const replayTag = currentCandidateSummary?.live_tag || 'R';
            if (eSnap) marks.push({
                time: eSnap, position: t.side === 'long' ? 'belowBar' : 'aboveBar',
                color: '#38bdf8', shape: t.side === 'long' ? 'arrowUp' : 'arrowDown',
                text: `${replayTag} ${t.side === 'long' ? 'BUY' : 'SELL'} ${fmtPrice(t.entry_price)}`, size: 1,
            });
            if (xSnap) {
                const label = (t.exit_reason || '').includes('take_profit') ? 'TP'
                    : ((t.exit_reason || '').includes('stop_loss') ? 'SL' : 'TIME');
                const net = Number(t.pnl_pct ?? t.price_move_pct ?? 0);
                marks.push({
                    time: xSnap, position: t.side === 'long' ? 'aboveBar' : 'belowBar',
                    color: net >= 0 ? '#14b8a6' : '#fb7185', shape: 'circle',
                    text: `${replayTag} ${label} ${fmtPrice(t.exit_price)} ${net >= 0 ? '+' : ''}${net.toFixed(2)}%`, size: 0.9,
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

    // ── Visible range ──────────────────────────────────────────────────────
    const RANGE_SECONDS = { '1D': 86400, '5D': 5*86400, '1M': 30*86400, '3M': 91*86400, '6M': 182*86400, '1Y': 365*86400 };

    function setRange(key) {
        if (!cachedCandles.length) return;
        if (key === 'all') { chart.timeScale().fitContent(); return; }
        const first = cachedCandles[0].time;
        const last  = cachedCandles[cachedCandles.length - 1].time;
        chart.timeScale().setVisibleRange({ from: Math.max(first, last - RANGE_SECONDS[key]), to: last });
    }

    // ── UI events ──────────────────────────────────────────────────────────
    document.querySelectorAll('.tf-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tf-btn').forEach(b => b.classList.remove('on'));
            btn.classList.add('on');
            currentTf = btn.dataset.tf;
            loadData(true);
        });
    });

    document.querySelectorAll('.range-btn').forEach(btn => {
        btn.addEventListener('click', () => setRange(btn.dataset.range));
    });

    document.querySelectorAll('.ind-chk').forEach(chk => {
        chk.addEventListener('change', () => setIndicator(chk.dataset.ind, chk.checked));
    });
    document.getElementById('chk-vol').addEventListener('change', function () { setVolume(this.checked); });

    document.querySelectorAll('.draw-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const mode = btn.dataset.mode;
            drawMode     = drawMode === mode ? 'none' : mode;
            pendingPoint = null;
            updateDrawToolbar();
            renderDefaultLegend();
        });
    });

    // ── Exchange / coin selectors ──────────────────────────────────────────
    const symbolsMap = JSON.parse(document.getElementById('symbols-map-data').textContent);

    function coinLabel(sym) { return sym.split('/')[0]; }

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
        if (sel.value === '') {
            if (sel.options.length) {
                sel.options[0].selected = true;
                currentSymbol = sel.options[0].value;
            }
        } else {
            currentSymbol = sel.value;
        }
    }

    function resetBotContext() {
        currentBotId  = null;
        currentBotRef = null;
        showBotTrades = false;
        currentTrades = [];
        currentCandidateTrades = [];
        clearPositionOverlay();
        updateTradesButton();
    }

    populateCoinSelect(currentExchange);

    document.getElementById('chart-exchange').addEventListener('change', function () {
        currentExchange = this.value;
        resetBotContext();
        populateCoinSelect(currentExchange);
        loadData(true);
        loadCandidateOptions();
    });

    document.getElementById('chart-coin').addEventListener('change', function () {
        currentSymbol = this.value;
        resetBotContext();
        loadData(true);
        loadCandidateOptions();
    });

    document.getElementById('candidate-overlay-select').addEventListener('change', function () {
        currentCandidateId = this.value;
        currentCandidateTrades = [];
        currentCandidateSummary = selectedCandidateOption();
        lsSet('safary_chart_candidate_overlay', currentCandidateId || '__off__');
        updateTradesButton();
        buildMarkers(currentTrades);
        loadCandidateTrades();
    });

    document.getElementById('chk-bot-trades').addEventListener('change', function () {
        showBotTrades = this.checked;
        updateTradesButton();
        if (!showBotTrades) {
            clearPositionOverlay();
            currentTrades = [];
            buildMarkers([]);
            return;
        }
        loadTrades();
        loadOpenPosition();
    });

    document.getElementById('btn-undo').addEventListener('click', undoLastDrawing);
    document.getElementById('btn-clear-drawings').addEventListener('click', clearDrawings);
    document.getElementById('btn-refresh').addEventListener('click', manualRefresh);
    document.getElementById('btn-go-start').addEventListener('click', () => chart.timeScale().scrollToPosition(-1e10, false));
    document.getElementById('btn-go-end').addEventListener('click',   () => chart.timeScale().scrollToRealTime());
    document.getElementById('btn-fullscreen').addEventListener('click', () => {
        const root = document.getElementById('chart-root');
        if (document.fullscreenElement) document.exitFullscreen();
        else root.requestFullscreen?.();
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && drawMode !== 'none') {
            pendingPoint = null; drawMode = 'none'; updateDrawToolbar(); renderDefaultLegend();
        }
    });

    // ── Boot ───────────────────────────────────────────────────────────────
    initChart();

    try {
        (JSON.parse(lsGet('safary_chart_indicators') || '[]') || []).forEach(name => {
            if (indState[name]) {
                indState[name].visible = true;
                const chk = document.querySelector(`.ind-chk[data-ind="${name}"]`);
                if (chk) chk.checked = true;
            }
        });
    } catch (_) {}
    if (lsGet('safary_chart_volume') === '0') {
        document.getElementById('chk-vol').checked = false;
        setVolume(false);
    }

    updateTradesButton();
    loadCandidateOptions();
    loadData(true);
})();
</script>

</x-filament-panels::page>
