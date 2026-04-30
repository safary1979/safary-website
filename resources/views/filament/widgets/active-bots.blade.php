<x-filament-widgets::widget>
    {{-- Alpine-based dynamic polling --}}
    <div
        x-data="{}"
        x-init="
            let timer = null;
            $wire.$watch('pollInterval', (val) => {
                clearInterval(timer);
                if (val) timer = setInterval(() => $wire.triggerRefresh(), parseInt(val) * 1000);
            });
        "
    ></div>

    <x-filament::section>
        <x-slot name="heading">
            <div style="display:flex; align-items:center; justify-content:space-between; width:100%;">
                <span>Активні боти</span>
                <div style="display:flex; gap:0.4rem; align-items:center;">
                    @if($canSwitch && !empty($tenants))
                        <div style="display:flex; border:1px solid #374151; border-radius:0.375rem; overflow:hidden;">
                            @foreach($tenants as $tid => $tname)
                                <button
                                    wire:click="setActiveTenant({{ $tid }})"
                                    style="
                                        font-size:0.7rem; padding:0.25rem 0.6rem; border:0; cursor:pointer;
                                        background: {{ $tid === $activeTenant ? '#f59e0b' : '#1f2937' }};
                                        color: {{ $tid === $activeTenant ? '#111827' : '#9ca3af' }};
                                        font-weight: {{ $tid === $activeTenant ? '600' : '400' }};
                                    "
                                    title="Переглянути кабінет: {{ $tname }}"
                                >👤 {{ $tname }}</button>
                            @endforeach
                        </div>
                    @endif
                    <button
                        wire:click="manualUpdate"
                        title="Оновити"
                        style="font-size:0.7rem; padding:0.25rem 0.6rem; border-radius:0.375rem; border:1px solid #374151; background:#1f2937; color:#d1d5db; cursor:pointer;"
                    >↻ Update</button>
                    <div x-data="{ open:false }" style="position:relative;">
                        <button
                            @click="open = !open"
                            style="font-size:0.7rem; padding:0.25rem 0.6rem; border-radius:0.375rem; border:1px solid #374151; background:#1f2937; color:#d1d5db; cursor:pointer; min-width:55px;"
                        >{{ $this->pollInterval ? $this->pollInterval . 's' : 'Off' }} ▾</button>
                        <div
                            x-show="open" @click.outside="open = false" x-transition
                            style="position:absolute; right:0; top:100%; margin-top:0.25rem; background:#111827; border:1px solid #374151; border-radius:0.375rem; min-width:80px; z-index:50; overflow:hidden;"
                        >
                            <button wire:click="setPollInterval(null)" @click="open=false" style="display:block; width:100%; text-align:left; padding:0.35rem 0.7rem; font-size:0.7rem; color:#d1d5db; background:transparent; border:0; cursor:pointer;">{{ $this->pollInterval === null ? '✓ ' : '' }}Off</button>
                            <button wire:click="setPollInterval('5')" @click="open=false" style="display:block; width:100%; text-align:left; padding:0.35rem 0.7rem; font-size:0.7rem; color:#d1d5db; background:transparent; border:0; cursor:pointer;">{{ $this->pollInterval === '5' ? '✓ ' : '' }}5 sec</button>
                            <button wire:click="setPollInterval('15')" @click="open=false" style="display:block; width:100%; text-align:left; padding:0.35rem 0.7rem; font-size:0.7rem; color:#d1d5db; background:transparent; border:0; cursor:pointer;">{{ $this->pollInterval === '15' ? '✓ ' : '' }}15 sec</button>
                        </div>
                    </div>
                </div>
            </div>
        </x-slot>

        @if($bots->isEmpty())
            <p style="color:#6b7280; padding:1rem 0; text-align:center;">Немає активних ботів</p>
        @else
            <div style="display:flex; flex-direction:column; gap:0.5rem;">
            @foreach($bots as $bot)
            @php
                $pos      = $bot->state?->position_json;
                $inPos    = !empty($pos);
                $markPrice = (float)($bot->state?->mark_price ?? 0);

                $statusColor = match($bot->status) {
                    'running' => '#22c55e', 'paused' => '#f59e0b', default => '#6b7280'
                };
                $statusLabel = match($bot->status) {
                    'running' => 'Running', 'paused' => 'Пауза',
                    'stopped' => '⚠ Зупинено', default => $bot->status,
                };
                $dirColor = $bot->direction === 'long' ? '#22c55e' : '#ef4444';

                $side     = $inPos ? strtoupper($pos['side_str'] ?? '') : null;
                $avgPrice = $inPos ? (float)($pos['avg_price'] ?? 0) : 0;
                $pnlUsdt  = $inPos ? (float)($pos['pnl_usdt'] ?? 0) : 0;
                $pnlPct   = $inPos ? (float)($pos['pnl_pct']  ?? 0) : 0;
                $notional = $inPos ? (float)($pos['notional'] ?? $pos['margin'] ?? 0) : 0;
                $qty      = $inPos ? (float)($pos['qty'] ?? 0) : 0;
                $openedAt = $inPos ? ($pos['opened_at'] ?? null) : null;

                $pnlSign  = $pnlUsdt >= 0 ? '+' : '';
                $pnlColor = $pnlUsdt >= 0 ? '#22c55e' : '#ef4444';

                // TP / SL from config
                $tpPrice = null; $slPrice = null; $tpPct = 0; $slPct = 0;
                if ($inPos && $avgPrice > 0 && $bot->config_path && file_exists($bot->config_path)) {
                    $cfg   = json_decode(file_get_contents($bot->config_path), true) ?? [];
                    $tpPct = (float)($cfg['take_profit_pct'] ?? 0);
                    $slPct = (float)($cfg['stop_loss_pct']   ?? 0);
                    $slOn  = (bool)($cfg['stop_loss_enabled'] ?? false);
                    $sLc   = $pos['side_str'] ?? 'long';
                    if ($tpPct > 0)        $tpPrice = $sLc === 'long' ? $avgPrice*(1+$tpPct/100) : $avgPrice*(1-$tpPct/100);
                    if ($slOn && $slPct>0) $slPrice = $sLc === 'long' ? $avgPrice*(1-$slPct/100) : $avgPrice*(1+$slPct/100);
                }

                // ── Price bar ─────────────────────────────────────
                $barLeft = $barRight = null;
                if ($inPos && $avgPrice > 0) {
                    $cur = $markPrice ?: $avgPrice;
                    if ($slPrice && $tpPrice) {
                        $barLeft  = min($slPrice, $tpPrice, $cur, $avgPrice);
                        $barRight = max($slPrice, $tpPrice, $cur, $avgPrice);
                    } elseif ($tpPrice) {
                        $spread   = abs($tpPrice - $avgPrice);
                        $barLeft  = min($avgPrice, $cur) - $spread * 0.3;
                        $barRight = max($tpPrice, $cur) + $spread * 0.05;
                    } elseif ($slPrice) {
                        $spread   = abs($avgPrice - $slPrice);
                        $barLeft  = min($slPrice, $cur) - $spread * 0.05;
                        $barRight = max($avgPrice, $cur) + $spread * 0.3;
                    } else {
                        $pad      = $avgPrice * 0.025;
                        $barLeft  = min($avgPrice, $cur) - $pad;
                        $barRight = max($avgPrice, $cur) + $pad;
                    }
                    // 3% padding on each side
                    $span     = $barRight - $barLeft;
                    $barLeft  -= $span * 0.03;
                    $barRight += $span * 0.03;
                    $span     = $barRight - $barLeft;

                    $pct  = fn($p) => $span > 0 ? round(max(0, min(100, ($p - $barLeft) / $span * 100)), 2) : 0;
                    $entryPct   = $pct($avgPrice);
                    $curPct     = $pct($cur);
                    $tpBarPct   = $tpPrice  ? $pct($tpPrice)  : null;
                    $slBarPct   = $slPrice  ? $pct($slPrice)  : null;

                    $fillL = min($entryPct, $curPct);
                    $fillW = abs($curPct - $entryPct);
                }

                $fmt = function($p) {
                    if ($p >= 10000) return number_format($p, 1);
                    if ($p >= 1000)  return number_format($p, 2);
                    if ($p >= 10)    return number_format($p, 3);
                    if ($p >= 1)     return number_format($p, 4);
                    return number_format($p, 5);
                };
                $timeAgo = $openedAt
                    ? now()->diffForHumans(\Carbon\Carbon::createFromTimestamp((float)$openedAt), true) . ' тому'
                    : null;
            @endphp

            <div
                x-data="{ open: false }"
                style="border:1px solid #374151; border-radius:0.625rem; overflow:hidden;"
            >
                {{-- ── Clickable header row ── --}}
                <div
                    @click="open = !open"
                    style="
                        display:grid;
                        grid-template-columns: minmax(180px,22%) 1fr auto;
                        align-items:center;
                        gap:0.75rem;
                        padding:0.75rem 1rem;
                        cursor:pointer;
                        background:#111827;
                        user-select:none;
                    "
                >
                    {{-- Left: name + badges --}}
                    <div style="display:flex; flex-direction:column; gap:0.3rem; min-width:0;">
                        <span style="font-weight:600; font-size:0.85rem; color:#f3f4f6; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            {{ $bot->slug }}
                        </span>
                        <div style="display:flex; gap:0.35rem; align-items:center; flex-wrap:wrap;">
                            <span style="font-size:0.65rem; font-weight:700; padding:0.1rem 0.4rem; border-radius:999px; background:{{ $dirColor }}22; border:1px solid {{ $dirColor }}66; color:{{ $dirColor }};">
                                {{ strtoupper($bot->direction) }} x{{ (int)($bot->leverage ?? 1) }}
                            </span>
                            <span style="font-size:0.65rem; padding:0.1rem 0.4rem; border-radius:999px; background:{{ $statusColor }}18; border:1px solid {{ $statusColor }}44; color:{{ $statusColor }};">
                                {{ $statusLabel }}
                            </span>
                        </div>
                    </div>

                    {{-- Middle: price bar or "Очікує" --}}
                    <div>
                    @if($inPos && $barLeft !== null)
                        {{-- Current price row --}}
                        <div style="text-align:center; font-size:0.7rem; color:#60a5fa; font-weight:600; margin-bottom:0.2rem;">
                            {{ $fmt($markPrice ?: $avgPrice) }}
                        </div>
                        {{-- Bar track --}}
                        <div style="position:relative; height:6px; background:#1f2937; border-radius:3px;">
                            {{-- PnL fill --}}
                            <div style="
                                position:absolute; top:0; bottom:0;
                                left:{{ $fillL }}%; width:{{ max(0.5, $fillW) }}%;
                                background:{{ $pnlColor }}; border-radius:3px; opacity:0.85;
                            "></div>
                            {{-- SL marker --}}
                            @if($slBarPct !== null)
                            <div style="position:absolute; top:-2px; bottom:-2px; left:{{ $slBarPct }}%; width:2px; background:#ef4444; border-radius:1px; transform:translateX(-50%);"></div>
                            @endif
                            {{-- Entry marker --}}
                            <div style="position:absolute; top:-3px; bottom:-3px; left:{{ $entryPct }}%; width:2px; background:#ffffff88; border-radius:1px; transform:translateX(-50%);"></div>
                            {{-- Current price marker --}}
                            <div style="position:absolute; top:-4px; bottom:-4px; left:{{ $curPct }}%; width:3px; background:#60a5fa; border-radius:2px; transform:translateX(-50%); box-shadow:0 0 4px #60a5fa88;"></div>
                            {{-- TP marker --}}
                            @if($tpBarPct !== null)
                            <div style="position:absolute; top:-2px; bottom:-2px; left:{{ $tpBarPct }}%; width:2px; background:#22c55e; border-radius:1px; transform:translateX(-50%);"></div>
                            @endif
                        </div>
                        {{-- Anchor labels (TP/SL sides swap for short) --}}
                        @php
                            $isShort   = ($pos['side_str'] ?? 'long') === 'short';
                            $tpLabel   = $tpPrice ? 'TP '.$fmt($tpPrice) : '';
                            $slLabel   = $slPrice ? 'SL '.$fmt($slPrice) : '';
                            $leftLabel  = $isShort ? $tpLabel : $slLabel;
                            $leftColor  = $isShort ? '#22c55e' : '#ef4444';
                            $rightLabel = $isShort ? $slLabel : $tpLabel;
                            $rightColor = $isShort ? '#ef4444' : '#22c55e';
                        @endphp
                        <div style="display:flex; justify-content:space-between; font-size:0.65rem; margin-top:0.2rem; padding:0 2px;">
                            <span style="color:{{ $leftColor }}; font-weight:600;">{{ $leftLabel }}</span>
                            <span style="color:#4b5563;">Вхід {{ $fmt($avgPrice) }}</span>
                            <span style="color:{{ $rightColor }}; font-weight:600;">{{ $rightLabel }}</span>
                        </div>
                    @elseif(!$inPos)
                        <span style="font-size:0.8rem; color:#4b5563;">Очікує сигналу</span>
                    @endif
                    </div>

                    {{-- Right: PnL --}}
                    <div style="text-align:right; min-width:110px;">
                    @if($inPos)
                        <div style="font-size:0.85rem; font-weight:700; color:{{ $pnlColor }};">
                            {{ $pnlSign }}{{ number_format($pnlUsdt, 2) }} USDT
                        </div>
                        <div style="font-size:0.72rem; color:{{ $pnlColor }}; opacity:0.8;">
                            {{ $pnlSign }}{{ number_format($pnlPct, 2) }}%
                        </div>
                    @endif
                        <div style="font-size:0.65rem; color:#374151; margin-top:0.15rem;">
                            <span x-show="!open">▼</span>
                            <span x-show="open">▲</span>
                        </div>
                    </div>
                </div>

                {{-- ── Expandable detail section ── --}}
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 transform -translate-y-1"
                    x-transition:enter-end="opacity-100 transform translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    style="border-top:1px solid #1f2937; background:#0f172a; padding:0.875rem 1rem;"
                >
                @if($inPos)
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem 2rem;">
                        {{-- Left column --}}
                        <div style="display:flex; flex-direction:column; gap:0.35rem;">
                            @if($qty > 0)
                            <div style="display:flex; justify-content:space-between;">
                                <span style="font-size:0.75rem; color:#6b7280;">Розмір позиції</span>
                                <span style="font-size:0.75rem; color:#d1d5db;">{{ $qty }} {{ $bot->symbol }}</span>
                            </div>
                            @endif
                            @if($notional > 0)
                            <div style="display:flex; justify-content:space-between;">
                                <span style="font-size:0.75rem; color:#6b7280;">Обʼєм</span>
                                <span style="font-size:0.75rem; color:#d1d5db;">{{ number_format($notional, 2) }} USDT</span>
                            </div>
                            @endif
                            <div style="display:flex; justify-content:space-between;">
                                <span style="font-size:0.75rem; color:#6b7280;">Середній вхід</span>
                                <span style="font-size:0.75rem; color:#d1d5db;">{{ $fmt($avgPrice) }}</span>
                            </div>
                            <div style="display:flex; justify-content:space-between;">
                                <span style="font-size:0.75rem; color:#6b7280;">Поточна ціна</span>
                                <span style="font-size:0.75rem; color:#60a5fa;">{{ $fmt($markPrice ?: $avgPrice) }}</span>
                            </div>
                            @if($timeAgo)
                            <div style="display:flex; justify-content:space-between;">
                                <span style="font-size:0.75rem; color:#6b7280;">Відкрито</span>
                                <span style="font-size:0.75rem; color:#9ca3af;">{{ $timeAgo }}</span>
                            </div>
                            @endif
                        </div>

                        {{-- Right column --}}
                        <div style="display:flex; flex-direction:column; gap:0.35rem;">
                            @if($tpPrice)
                            <div style="display:flex; justify-content:space-between;">
                                <span style="font-size:0.75rem; color:#6b7280;">Take Profit</span>
                                <span style="font-size:0.75rem; color:#22c55e; font-weight:600;">{{ $fmt($tpPrice) }} (+{{ number_format($tpPct, 2) }}%)</span>
                            </div>
                            @endif
                            @if($slPrice)
                            @php $slSign = ($pos['side_str'] ?? 'long') === 'short' ? '+' : '-'; @endphp
                            <div style="display:flex; justify-content:space-between;">
                                <span style="font-size:0.75rem; color:#6b7280;">Stop Loss</span>
                                <span style="font-size:0.75rem; color:#ef4444; font-weight:600;">{{ $fmt($slPrice) }} ({{ $slSign }}{{ number_format($slPct, 2) }}%)</span>
                            </div>
                            @endif
                            <div style="display:flex; justify-content:space-between;">
                                <span style="font-size:0.75rem; color:#6b7280;">Пара</span>
                                <span style="font-size:0.75rem; color:#d1d5db;">{{ $bot->symbol }}</span>
                            </div>
                            <div style="display:flex; justify-content:space-between;">
                                <span style="font-size:0.75rem; color:#6b7280;">Акаунт</span>
                                <span style="font-size:0.75rem; color:#d1d5db;">{{ $bot->account }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Action buttons --}}
                    @php
                        $chartSymbol = str_contains($bot->symbol ?? '', '/') ? $bot->symbol : strtoupper($bot->symbol).'/USDT:USDT';
                        $chartUrl = '/admin/chart-page?exchange='.urlencode($bot->exchange ?? 'bybit').'&symbol='.urlencode($chartSymbol).'&tf=1h'.($bot->id ? '&bot_id='.$bot->id : '');
                    @endphp
                    <div style="display:flex; gap:0.5rem; margin-top:0.875rem; padding-top:0.75rem; border-top:1px solid #1f2937; flex-wrap:wrap;">
                        @if($canControl && $bot->status === 'running' && $bot->id)
                        <button
                            wire:click="closePosition({{ $bot->id }})"
                            wire:confirm="Закрити позицію по ринку? Бот продовжить роботу."
                            style="font-size:0.75rem; padding:0.35rem 0.875rem; border-radius:0.375rem; border:1px solid #ef444466; background:#ef444411; color:#ef4444; cursor:pointer;"
                        >✕ Закрити позицію</button>
                        @elseif(!$canControl)
                        <span style="font-size:0.7rem; color:#4b5563;">🔒 Read-only</span>
                        @endif
                        <a href="{{ $chartUrl }}"
                           style="font-size:0.75rem; padding:0.35rem 0.875rem; border-radius:0.375rem; border:1px solid #2563eb66; background:#2563eb11; color:#60a5fa; cursor:pointer; text-decoration:none; display:inline-block;">
                            📈 Графік
                        </a>
                    </div>
                @else
                    <p style="font-size:0.8rem; color:#4b5563;">Бот активний, але поки не в угоді. Очікує сигналу за умовами конфігу.</p>
                    @php
                        $chartSymbol2 = str_contains($bot->symbol ?? '', '/') ? $bot->symbol : strtoupper($bot->symbol).'/USDT:USDT';
                        $chartUrl2 = '/admin/chart-page?exchange='.urlencode($bot->exchange ?? 'bybit').'&symbol='.urlencode($chartSymbol2).'&tf=1h'.($bot->id ? '&bot_id='.$bot->id : '');
                    @endphp
                    <div style="margin-top:0.5rem;">
                        <a href="{{ $chartUrl2 }}"
                           style="font-size:0.75rem; padding:0.35rem 0.875rem; border-radius:0.375rem; border:1px solid #2563eb66; background:#2563eb11; color:#60a5fa; cursor:pointer; text-decoration:none; display:inline-block;">
                            📈 Графік
                        </a>
                    </div>
                @endif
                </div>
            </div>
            @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
