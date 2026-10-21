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
            <div class="active-bots-heading" style="display:flex; align-items:center; justify-content:space-between; width:100%;">
                <span>Активні боти · {{ strtoupper($dashboardMode ?? 'live') }}</span>
                <div class="active-bots-heading-controls" style="display:flex; gap:0.4rem; align-items:center;">
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
                        wire:loading.attr="disabled"
                        wire:target="manualUpdate"
                        title="Оновити"
                        style="font-size:0.7rem; padding:0.25rem 0.6rem; border-radius:0.375rem; border:1px solid #374151; background:#1f2937; color:#d1d5db; cursor:pointer;"
                    >
                        <span wire:loading.remove wire:target="manualUpdate">↻ Update</span>
                        <span wire:loading wire:target="manualUpdate">⟳ Оновлення…</span>
                    </button>
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

        @if($gatewayStatus)
            <div style="margin:-0.35rem 0 0.65rem; font-size:0.68rem; color:{{ $gatewayStatus['available'] ? '#64748b' : '#f87171' }};">
                @if($gatewayStatus['available'])
                    Дані Gateway: {{ $gatewayStatus['generated_at_utc'] ?? '—' }} · {{ $gatewayStatus['age_seconds'] ?? '—' }} с тому
                    @if($gatewayStatus['host'] ?? null) · Load {{ implode(' / ', array_map(fn ($v) => number_format($v, 2), $gatewayStatus['host']['load'])) }} · RAM {{ number_format($gatewayStatus['host']['available_gib'], 1) }} GiB @endif
                @else
                    Gateway недоступний: {{ $gatewayStatus['error'] ?? 'невідома помилка' }}
                @endif
            </div>
        @endif

        @if(!($modeAvailable ?? true))
            <p style="color:#f59e0b; padding:1rem 0; text-align:center;">{{ strtoupper($dashboardMode ?? 'live') }}: дані недоступні</p>
        @elseif($bots->isEmpty())
            <p style="color:#6b7280; padding:1rem 0; text-align:center;">Немає активних ботів</p>
        @else
            <div style="display:flex; flex-direction:column; gap:0.5rem;">
            @foreach($bots as $bot)
            @php
                $pos      = $bot->state?->position_json;
                $inPos    = !empty($pos);
                $dca      = is_array($bot->dca ?? null) ? $bot->dca : [];
                // DCA is a live position control, not a bot configuration badge.
                // Do not show the last persisted ladder for a stopped/stale bot.
                $hasDca   = $inPos
                    && in_array($bot->status, ['running', 'blocked'], true)
                    && ($dca['enabled'] ?? false) === true
                    && !empty($dca['additions'] ?? []);
                $dcaCompleted = max(0, (int)($dca['completed_additions'] ?? 0));
                $dcaMax = max(count($dca['additions'] ?? []), (int)($dca['max_additions'] ?? 0));
                $runtimeError = trim((string)($bot->runtime_error ?? ''));
                $activeWarnings = is_array($bot->active_warnings ?? null)
                    ? array_values(array_filter($bot->active_warnings, fn ($warning) => is_string($warning)))
                    : [];
                $nautilusControlId = ($bot->source ?? null) === 'nautilus' ? (string)($bot->control_id ?? '') : '';
                $marginTelemetry = (($bot->source ?? null) === 'nautilus' && is_array($bot->margin ?? null)) ? $bot->margin : [];
                $marginAvailable = is_string($marginTelemetry['available_usdt'] ?? null) ? $marginTelemetry['available_usdt'] : null;
                $marginRequired = is_string($marginTelemetry['required_usdt'] ?? null) ? $marginTelemetry['required_usdt'] : null;
                $marginRemaining = is_string($marginTelemetry['remaining_usdt'] ?? null) ? $marginTelemetry['remaining_usdt'] : null;
                $marginPending = is_int($marginTelemetry['pending_exposure_orders'] ?? null) ? $marginTelemetry['pending_exposure_orders'] : 0;
                $marginBlocked = ($marginTelemetry['exposure_blocked'] ?? false) === true;
                $marginKnown = ($marginTelemetry['calculation_state'] ?? null) === 'calculated';
                $noPositionMessage = match($bot->status) {
                    'blocked' => $runtimeError !== '' ? $runtimeError : 'Входи заблоковані',
                    'stale' => 'Немає зв’язку з ботом',
                    'stopped' => 'Бот зупинений',
                    'starting' => 'Бот запускається',
                    'paused' => 'Бот на паузі',
                    default => 'Бот активний, але поки не в угоді. Очікує сигналу.',
                };
                if ($runtimeError !== '' && $bot->status === 'running') {
                    $noPositionMessage = $runtimeError;
                }
                $markPrice = (float)($bot->state?->mark_price ?? 0);

                $statusColor = match($bot->status) {
                    'running' => '#22c55e', 'paused', 'starting' => '#f59e0b', 'blocked', 'stale' => '#ef4444', default => '#6b7280'
                };
                $statusLabel = match($bot->status) {
                    'running' => 'Running', 'paused' => 'Пауза',
                    'starting' => 'Запуск', 'blocked' => '⚠ Входи заблоковані', 'stale' => '⚠ Немає зв’язку',
                    'stopped' => '⚠ Зупинено', default => $bot->status,
                };
                $dirColor = match($bot->direction) { 'long' => '#22c55e', 'short' => '#ef4444', default => '#60a5fa' };

                $side     = $inPos ? strtoupper($pos['side_str'] ?? '') : null;
                $avgPrice = $inPos ? (float)($pos['avg_price'] ?? 0) : 0;
                $pnlUsdt  = $inPos ? (float)($pos['pnl_usdt'] ?? 0) : 0;
                $pnlPct   = $inPos ? (float)($pos['pnl_pct']  ?? 0) : 0;
                $notional = $inPos ? (float)($pos['notional'] ?? $pos['margin'] ?? 0) : 0;
                $qty      = $inPos ? (float)($pos['qty'] ?? 0) : 0;
                $openedAt = $inPos ? ($pos['opened_at'] ?? null) : null;
                $barsHeld = $inPos ? (int)($pos['bars_held'] ?? 0) : 0;

                $pnlSign  = $pnlUsdt >= 0 ? '+' : '';
                $pnlColor = $pnlUsdt >= 0 ? '#22c55e' : '#ef4444';
                $netStats = ($bot->id && isset($botNetStats[$bot->id])) ? $botNetStats[$bot->id] : (is_array($bot->net_stats ?? null) ? $bot->net_stats : null);
                $netWinLoss = $netStats && ($netStats['wins'] ?? null) !== null && ($netStats['losses'] ?? null) !== null;
                $netUsdt = $netStats ? (float)($netStats['pnl_usdt'] ?? 0) : null;
                $netTrades = $netStats ? (int)($netStats['trades'] ?? 0) : 0;
                $netWins = $netStats ? (int)($netStats['wins'] ?? 0) : 0;
                $netLosses = $netStats ? (int)($netStats['losses'] ?? 0) : 0;
                $netSign = ($netUsdt ?? 0) >= 0 ? '+' : '';
                $netColor = ($netUsdt ?? 0) >= 0 ? '#22c55e' : '#ef4444';

                // Display metadata and TP / SL from config
                $tpPrice = null; $slPrice = null; $tpPct = 0; $slPct = 0; $maxHoldingBars = 0; $displayLeverage = (float)($bot->leverage ?? 1);
                $cfg = [];
                $displayName = $bot->direction === 'short' ? 'HYPE Short' : ($bot->direction === 'long' ? 'HYPE Long' : $bot->slug);
                $displaySubName = $bot->slug;
                $candidateCount = 0;
                $candidateLabel = null;
                $candidateProfileLabel = null;
                if ($bot->config_path && file_exists($bot->config_path)) {
                    $cfg = json_decode(file_get_contents($bot->config_path), true) ?? [];
                    $displayLeverage = (float)($cfg['leverage'] ?? $displayLeverage);
                    $statusTag = (string)($cfg['_status'] ?? '');
                    $configBase = basename((string)$bot->config_path);
                    if ($bot->direction === 'short') {
                        $displayName = str_contains($configBase, 'v9') ? 'HYPE Short · v9 router' : 'HYPE Short';
                    } elseif ($bot->direction === 'long') {
                        $displayName = str_contains($configBase, 'down_friday') ? 'HYPE Long · regime router' : 'HYPE Long';
                    }

                    $candidateKeys = [];
                    foreach (($cfg['entry_exit_profiles'] ?? []) as $profileKey => $profileData) {
                        if (!is_array($profileData)) continue;
                        $cid = (string)($profileData['candidate_id'] ?? $profileData['id'] ?? $profileKey);
                        if ($cid !== '') $candidateKeys[$cid] = true;
                    }
                    if (!$candidateKeys) {
                        foreach (($cfg['entry_conditions'] ?? []) as $condition) {
                            if (!is_array($condition)) continue;
                            foreach (['ids', 'lanes'] as $field) {
                                foreach (($condition[$field] ?? []) as $cid) {
                                    $cid = (string)$cid;
                                    if ($cid !== '') $candidateKeys[$cid] = true;
                                }
                            }
                        }
                    }
                    $candidateCount = count($candidateKeys);

                    $candidateWord = $candidateCount === 1 ? 'candidate' : 'candidates';
                    $exchangeLabel = strtoupper((string)($bot->exchange ?? ($cfg['exchange'] ?? '')));
                    $displaySubName = trim(($candidateCount > 0 ? $candidateCount.' '.$candidateWord.' · ' : '').$exchangeLabel.' · x'.(int)$displayLeverage, ' ·');
                }
                if (in_array(($bot->source ?? null), ['freqtrade', 'nautilus'], true)) {
                    $displayName = (string)($bot->display_name ?? $bot->slug);
                    $engine = ($bot->source ?? null) === 'nautilus' ? 'NautilusTrader' : 'Freqtrade';
                    $accountLabel = ($bot->source ?? null) === 'nautilus' ? (string)($bot->account ?? '') : '';
                    $timeframeLabel = $bot->timeframe ? ' · '.(string)$bot->timeframe : '';
                    $exchangeLabel = strtoupper((string)($bot->market_exchange ?? $bot->exchange ?? 'okx'));
                    if (($bot->run_mode ?? null) === 'demo') $exchangeLabel .= ' · DEMO';
                    $displaySubName = $engine.' · '.($accountLabel !== '' ? $accountLabel.' · ' : '').$exchangeLabel.$timeframeLabel.' · x'.(int)$displayLeverage;
                }
                if ($inPos) {
                    $entrySignal = is_array($pos['entry_signal'] ?? null) ? $pos['entry_signal'] : [];
                    $candidateLabel = $entrySignal['candidate_id'] ?? $entrySignal['id'] ?? $entrySignal['lane'] ?? null;
                    $candidateProfileLabel = $entrySignal['profile'] ?? null;
                    if ($cfg && !$candidateProfileLabel) {
                        $entryLaneForLabel = $entrySignal['lane'] ?? null;
                        $entryIdForLabel = $entrySignal['id'] ?? $entrySignal['candidate_id'] ?? null;
                        $profileForLabel = null;
                        if ($entryIdForLabel && isset($cfg['entry_exit_profiles'][$entryIdForLabel])) {
                            $profileForLabel = $cfg['entry_exit_profiles'][$entryIdForLabel];
                        } elseif ($entryLaneForLabel && isset($cfg['entry_exit_profiles'][$entryLaneForLabel])) {
                            $profileForLabel = $cfg['entry_exit_profiles'][$entryLaneForLabel];
                        } else {
                            foreach (($cfg['entry_exit_profiles'] ?? []) as $profileData) {
                                if (!is_array($profileData)) continue;
                                if (($profileData['candidate_id'] ?? null) === $candidateLabel || ($profileData['lane'] ?? null) === $candidateLabel) {
                                    $profileForLabel = $profileData;
                                    break;
                                }
                            }
                        }
                        if (!$candidateLabel && is_array($profileForLabel)) {
                            $candidateLabel = $profileForLabel['candidate_id'] ?? $profileForLabel['id'] ?? $profileForLabel['lane'] ?? null;
                        }
                        if (is_array($profileForLabel)) {
                            $candidateProfileLabel = $profileForLabel['profile'] ?? null;
                        }
                    }
                }
                if ($inPos && $avgPrice > 0 && $cfg) {
                    $entryLane = $pos['entry_signal']['lane'] ?? ($cfg['entry_conditions'][0]['lanes'][0] ?? null);
                    $entryId = $pos['entry_signal']['id'] ?? $pos['entry_signal']['candidate_id'] ?? null;
                    $profileKey = null;
                    if ($entryId && isset($cfg['entry_exit_profiles'][$entryId])) {
                        $profileKey = $entryId;
                    } elseif ($entryLane && isset($cfg['entry_exit_profiles'][$entryLane])) {
                        $profileKey = $entryLane;
                    }
                    $profileCfg = $profileKey ? ($cfg['entry_exit_profiles'][$profileKey] ?? []) : [];
                    $tpPct = (float)($profileCfg['take_profit_pct'] ?? $cfg['take_profit_pct'] ?? 0);
                    $slPct = (float)($profileCfg['stop_loss_pct'] ?? $cfg['stop_loss_pct'] ?? 0);
                    $slOn  = (bool)($cfg['stop_loss_enabled'] ?? ($slPct > 0));
                    $maxHoldingBars = (int)($profileCfg['max_holding_bars'] ?? $cfg['max_holding_bars'] ?? 0);
                    $sLc   = $pos['side_str'] ?? 'long';
                    if ($tpPct > 0)        $tpPrice = $sLc === 'long' ? $avgPrice*(1+$tpPct/100) : $avgPrice*(1-$tpPct/100);
                    if ($slOn && $slPct>0) $slPrice = $sLc === 'long' ? $avgPrice*(1-$slPct/100) : $avgPrice*(1+$slPct/100);
                }
                if ($inPos && in_array(($bot->source ?? null), ['freqtrade', 'nautilus'], true)) {
                    $tpPrice = (float)($pos['take_profit'] ?? 0) ?: null;
                    $slPrice = (float)($pos['stop_loss'] ?? 0) ?: null;
                    $tpPct = (float)($pos['take_profit_pct'] ?? 0);
                    $slPct = (float)($pos['stop_loss_pct'] ?? 0);
                    if ($tpPrice && $tpPct <= 0 && $avgPrice > 0) $tpPct = abs(($tpPrice - $avgPrice) / $avgPrice * 100);
                    if ($slPrice && $slPct <= 0 && $avgPrice > 0) $slPct = abs(($slPrice - $avgPrice) / $avgPrice * 100);
                }
                $curPrice = $markPrice ?: $avgPrice;
                $isShortPosition = $inPos && (($pos['side_str'] ?? 'long') === 'short');
                $priceMovePct = 0.0;
                $tpProgressPct = 0.0;
                $distanceToTpPct = null;
                $distanceToSlPct = null;
                if ($inPos && $avgPrice > 0 && $curPrice > 0) {
                    $rawMove = ($curPrice - $avgPrice) / $avgPrice * 100;
                    $priceMovePct = $isShortPosition ? -$rawMove : $rawMove;
                    $tpProgressPct = $tpPct > 0 ? max(-999, min(999, $priceMovePct / $tpPct * 100)) : 0.0;
                    if ($tpPrice) {
                        $distanceToTpPct = $isShortPosition
                            ? (($curPrice - $tpPrice) / $avgPrice * 100)
                            : (($tpPrice - $curPrice) / $avgPrice * 100);
                    }
                    if ($slPrice) {
                        $distanceToSlPct = $isShortPosition
                            ? (($slPrice - $curPrice) / $avgPrice * 100)
                            : (($curPrice - $slPrice) / $avgPrice * 100);
                    }
                }
                $priceMoveSign = $priceMovePct >= 0 ? '+' : '';
                $priceMoveColor = $priceMovePct >= 0 ? '#22c55e' : '#ef4444';
                $holdingProgressPct = $maxHoldingBars > 0 ? min(100, round($barsHeld / $maxHoldingBars * 100, 1)) : null;

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
                $timeAgo = null;
                if ($openedAt) {
                    $opened = \Carbon\Carbon::createFromTimestamp((float)$openedAt);
                    $secondsAgo = max(0, $opened->diffInSeconds(now()));
                    $days = intdiv($secondsAgo, 86400);
                    $hours = intdiv($secondsAgo % 86400, 3600);
                    $minutes = intdiv($secondsAgo % 3600, 60);
                    if ($days > 0) {
                        $timeAgo = $days . 'd ' . $hours . 'h тому';
                    } elseif ($hours > 0) {
                        $timeAgo = $hours . 'h ' . $minutes . 'm тому';
                    } elseif ($minutes > 0) {
                        $timeAgo = $minutes . 'm тому';
                    } else {
                        $timeAgo = '<1m тому';
                    }
                }
                $botStartedAt = null;
                $botStartedAtLabel = null;
                $waitingTime = null;
                $startedAtRaw = $bot->started_at_utc
                    ?? $bot->started_at
                    ?? $bot->run_started_at_utc
                    ?? null;
                if ($startedAtRaw !== null && in_array($bot->status, ['running', 'blocked', 'starting', 'paused'], true)) {
                    try {
                        $botStartedAt = is_numeric($startedAtRaw)
                            ? \Carbon\Carbon::createFromTimestamp((float)$startedAtRaw, 'UTC')
                            : \Carbon\Carbon::parse((string)$startedAtRaw, 'UTC');
                        $botStartedAtLabel = $botStartedAt->setTimezone('Europe/Kyiv')->format('d.m.Y H:i:s');
                        $elapsedSeconds = max(0, now('UTC')->timestamp - $botStartedAt->timestamp);
                        if ($elapsedSeconds < 60) {
                            $waitingTime = '<1 хв';
                        } else {
                            $days = intdiv($elapsedSeconds, 86400);
                            $hours = intdiv($elapsedSeconds % 86400, 3600);
                            $minutes = intdiv($elapsedSeconds % 3600, 60);
                            $parts = [];
                            if ($days > 0) $parts[] = $days . ' д';
                            if ($hours > 0) $parts[] = $hours . ' год';
                            if ($minutes > 0 || $parts === []) $parts[] = $minutes . ' хв';
                            $waitingTime = implode(' ', $parts);
                        }
                    } catch (\Throwable) {
                        $botStartedAt = null;
                    }
                }
                $waitingForEntry = !$inPos
                    && $bot->status === 'running'
                    && $runtimeError === ''
                    && $waitingTime !== null;
            @endphp

            <div
                wire:key="bot-{{ $bot->bot_ref ?? $bot->slug }}"
                x-data="{ open: false }"
                style="border:1px solid #374151; border-radius:0.625rem; overflow:hidden;"
            >
                {{-- ── Clickable header row ── --}}
                <div class="active-bot-header"
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
                        <div style="display:flex; align-items:center; gap:0.35rem; min-width:0;">
                        <span style="font-weight:600; font-size:0.85rem; color:#f3f4f6; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            {{ $displayName }}
                        </span>
                        <button type="button" title="Копіювати назву"
                            x-data="{ done: false }"
                            @click.stop="(navigator.clipboard ? navigator.clipboard.writeText($el.dataset.copy) : Promise.reject()).catch(() => { const t = document.createElement('textarea'); t.value = $el.dataset.copy; document.body.appendChild(t); t.select(); document.execCommand('copy'); t.remove(); }).finally(() => { done = true; setTimeout(() => done = false, 1200); })"
                            data-copy="{{ $displayName }}"
                            style="flex:none; border:0; background:transparent; padding:0 0.15rem; font-size:0.8rem; line-height:1; color:#6b7280; cursor:pointer;"
                            x-text="done ? '✓' : '⧉'">⧉</button>
                        </div>
                        <span style="font-size:0.62rem; color:#6b7280; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            {{ $displaySubName }}
                        </span>
                        @if($activeWarnings !== [])
                        <span style="font-size:0.65rem; color:#f87171; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="Проблема поточного циклу">
                            ⚠ {{ implode(' · ', array_slice($activeWarnings, 0, 2)) }}
                        </span>
                        @endif
                        <div style="display:flex; gap:0.35rem; align-items:center; flex-wrap:wrap;">
                            <span style="font-size:0.65rem; font-weight:700; padding:0.1rem 0.4rem; border-radius:999px; background:{{ $dirColor }}22; border:1px solid {{ $dirColor }}66; color:{{ $dirColor }};">
                                {{ strtoupper($bot->direction) }} x{{ (int)$displayLeverage }}
                            </span>
                            <span style="font-size:0.65rem; padding:0.1rem 0.4rem; border-radius:999px; background:{{ $statusColor }}18; border:1px solid {{ $statusColor }}44; color:{{ $statusColor }};">
                                {{ $statusLabel }}
                            </span>
                            @if($hasDca)
                            <span style="font-size:0.65rem; padding:0.1rem 0.4rem; border-radius:999px; background:#f59e0b18; border:1px solid #f59e0b55; color:#fbbf24;">
                                DCA {{ $dcaCompleted }}/{{ $dcaMax }}
                            </span>
                            @endif
                            @if($inPos && $candidateLabel)
                            <span style="font-size:0.65rem; padding:0.1rem 0.4rem; border-radius:999px; background:#60a5fa18; border:1px solid #60a5fa44; color:#93c5fd; max-width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                in trade: {{ $candidateLabel }}
                            </span>
                            @endif
                        </div>
                    </div>

                    {{-- Middle: price bar or "Очікує" --}}
                    <div class="active-bot-price">
                    @if($inPos && $hasDca)
                        @include('filament.widgets.partials.dca-ladder', [
                            'variant' => 'compact', 'dca' => $dca, 'pos' => $pos,
                            'markPrice' => $markPrice, 'avgPrice' => $avgPrice,
                            'tpPrice' => $tpPrice, 'slPrice' => $slPrice, 'fmt' => $fmt,
                        ])
                    @elseif($inPos && $barLeft !== null)
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
                    @elseif($bot->status !== 'running' || $runtimeError !== '')
                        <span style="font-size:0.8rem; color:{{ $bot->status === 'running' ? '#f87171' : $statusColor }}; font-weight:600;">{{ $noPositionMessage }}</span>
                    @elseif(!$inPos)
                        <span style="font-size:0.8rem; color:#4b5563;">Очікує сигналу</span>
                    @endif
                    </div>

                    {{-- Right: PnL --}}
                    <div class="active-bot-pnl" style="text-align:right; min-width:110px;">
                    @if($inPos)
                        <div style="font-size:0.85rem; font-weight:700; color:{{ $pnlColor }};">
                            {{ $pnlSign }}{{ number_format($pnlUsdt, 2) }} USDT
                        </div>
                        <div style="font-size:0.72rem; color:{{ $priceMoveColor }}; opacity:0.95;">
                            {{ $priceMoveSign }}{{ number_format($priceMovePct, 2) }}% / TP {{ number_format($tpPct, 2) }}%
                        </div>
                        <div style="font-size:0.65rem; color:#94a3b8; margin-top:0.05rem;">
                            margin {{ $pnlSign }}{{ number_format($pnlPct, 2) }}%
                        </div>
                    @endif
                    @if($netStats)
                        <div style="font-size:0.65rem; color:{{ $netColor }}; margin-top:0.12rem;">
                            net {{ $netSign }}{{ number_format((float)$netUsdt, 4) }} USDT
                        </div>
                        <div style="font-size:0.6rem; color:#64748b;">
                            {{ $netTrades }} closed{{ $netWinLoss ? ' · '.$netWins.'/'.$netLosses : '' }}
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
                    @if($hasDca)
                        @include('filament.widgets.partials.dca-ladder', [
                            'variant' => 'detail', 'dca' => $dca, 'pos' => $pos,
                            'markPrice' => $markPrice, 'avgPrice' => $avgPrice,
                            'tpPrice' => $tpPrice, 'slPrice' => $slPrice, 'fmt' => $fmt,
                        ])
                    @endif
                    <div class="active-bot-details" style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem 2rem;">
                        {{-- Left column --}}
                        <div style="display:flex; flex-direction:column; gap:0.35rem;">
                            @if($qty > 0)
                            <div style="display:flex; justify-content:space-between;">
                                <span style="font-size:0.75rem; color:#6b7280;">Розмір позиції</span>
                                <span style="font-size:0.75rem; color:#d1d5db;">{{ $qty }} {{ $bot->symbol }}</span>
                            </div>
                            @endif
                            @if($marginTelemetry)
                            <div style="display:flex; justify-content:space-between; gap:1rem;">
                                <span style="font-size:0.75rem; color:#6b7280;">Маржа entry/DCA</span>
                                <span style="font-size:0.75rem; color:{{ $marginBlocked ? '#f87171' : '#d1d5db' }}; text-align:right;">
                                    дост. {{ $marginAvailable ?? '—' }} · потрібно {{ $marginRequired ?? '—' }} · залишок {{ $marginRemaining ?? '—' }}
                                    @if($marginPending > 0) · pending {{ $marginPending }} @endif
                                </span>
                            </div>
                            @if($marginBlocked)
                            <div style="font-size:0.7rem; color:#f87171; text-align:right;">Нові entry/DCA заблоковані; TP продовжує працювати</div>
                            @if($runtimeError !== '')
                            <div style="font-size:0.7rem; color:#f87171; text-align:right;">Причина: {{ $runtimeError }}</div>
                            @endif
                            @elseif(!$marginKnown)
                            <div style="font-size:0.7rem; color:#f59e0b; text-align:right;">Розрахунок маржі тимчасово недоступний</div>
                            @endif
                            @endif
                            @if($candidateLabel)
                            <div style="display:flex; justify-content:space-between; gap:1rem;">
                                <span style="font-size:0.75rem; color:#6b7280;">Кандидат угоди</span>
                                <span style="font-size:0.75rem; color:#93c5fd; text-align:right; word-break:break-word;">
                                    {{ $candidateLabel }}{{ $candidateProfileLabel ? ' · '.$candidateProfileLabel : '' }}
                                </span>
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
                            <div style="display:flex; justify-content:space-between;">
                                <span style="font-size:0.75rem; color:#6b7280;">Рух ціни / TP</span>
                                <span style="font-size:0.75rem; color:{{ $priceMoveColor }}; font-weight:600;">
                                    {{ $priceMoveSign }}{{ number_format($priceMovePct, 3) }}% / {{ number_format($tpPct, 2) }}%
                                </span>
                            </div>
                            <div style="display:flex; justify-content:space-between;">
                                <span style="font-size:0.75rem; color:#6b7280;">PnL по маржі</span>
                                <span style="font-size:0.75rem; color:{{ $pnlColor }}; font-weight:600;">
                                    {{ $pnlSign }}{{ number_format($pnlPct, 2) }}%
                                </span>
                            </div>
                            @if($netStats)
                            <div style="display:flex; justify-content:space-between;">
                                <span style="font-size:0.75rem; color:#6b7280;">Net закриті</span>
                                <span style="font-size:0.75rem; color:{{ $netColor }}; font-weight:600;">
                                    {{ $netSign }}{{ number_format((float)$netUsdt, 4) }} USDT{{ $netWinLoss ? ' · '.$netWins.'/'.$netLosses : '' }}
                                </span>
                            </div>
                            @endif
                            @if($timeAgo)
                            <div style="display:flex; justify-content:space-between;">
                                <span style="font-size:0.75rem; color:#6b7280;">Відкрито</span>
                                <span style="font-size:0.75rem; color:#9ca3af;">{{ $timeAgo }}</span>
                            </div>
                            @endif
                            @if($botStartedAtLabel)
                            <div style="display:flex; justify-content:space-between;">
                                <span style="font-size:0.75rem; color:#6b7280;">Запущено</span>
                                <span style="font-size:0.75rem; color:#9ca3af;">{{ $botStartedAtLabel }} · Київ</span>
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
                            <div style="display:flex; justify-content:space-between;">
                                <span style="font-size:0.75rem; color:#6b7280;">До TP</span>
                                <span style="font-size:0.75rem; color:#22c55e; font-weight:600;">
                                    {{ number_format(max(0, (float)$distanceToTpPct), 3) }}% · {{ number_format($tpProgressPct, 1) }}%
                                </span>
                            </div>
                            @endif
                            @if($slPrice)
                            @php $slSign = '-'; @endphp
                            <div style="display:flex; justify-content:space-between;">
                                <span style="font-size:0.75rem; color:#6b7280;">Stop Loss</span>
                                <span style="font-size:0.75rem; color:#ef4444; font-weight:600;">{{ $fmt($slPrice) }} ({{ $slSign }}{{ number_format($slPct, 2) }}%)</span>
                            </div>
                            <div style="display:flex; justify-content:space-between;">
                                <span style="font-size:0.75rem; color:#6b7280;">До SL</span>
                                <span style="font-size:0.75rem; color:#ef4444; font-weight:600;">
                                    {{ number_format(max(0, (float)$distanceToSlPct), 3) }}%
                                </span>
                            </div>
                            @endif
                            @if($maxHoldingBars > 0)
                            <div style="display:flex; justify-content:space-between;">
                                <span style="font-size:0.75rem; color:#6b7280;">Bars held</span>
                                <span style="font-size:0.75rem; color:#d1d5db;">{{ $barsHeld }}/{{ $maxHoldingBars }} · {{ number_format((float)$holdingProgressPct, 1) }}%</span>
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
                        $chartUrl = '/admin/chart-page?exchange='.urlencode($bot->market_exchange ?? $bot->exchange ?? 'bybit').'&symbol='.urlencode($chartSymbol).'&tf=1h'.(!empty($bot->bot_ref) ? '&bot_ref='.urlencode($bot->bot_ref) : ($bot->id ? '&bot_id='.$bot->id : ''));
                    @endphp
                    <div class="active-bot-actions" style="display:flex; gap:0.5rem; margin-top:0.875rem; padding-top:0.75rem; border-top:1px solid #1f2937; flex-wrap:wrap;">
                        @if($canControl && $nautilusControlId !== '')
                            <button
                                wire:click="commandNautilus('{{ $nautilusControlId }}', 'restart')"
                                wire:confirm="Перезапустити Nautilus-бота?"
                                style="font-size:0.75rem; padding:0.35rem 0.75rem; border-radius:0.375rem; border:1px solid #60a5fa66; background:#60a5fa11; color:#93c5fd; cursor:pointer;"
                            >Restart</button>
                            <button
                                wire:click="commandNautilus('{{ $nautilusControlId }}', 'stop')"
                                wire:confirm="Зупинити Nautilus-бота? Біржові TP/SL залишаться активними."
                                style="font-size:0.75rem; padding:0.35rem 0.75rem; border-radius:0.375rem; border:1px solid #ef444466; background:#ef444411; color:#f87171; cursor:pointer;"
                            >Stop</button>
                        @endif
                        @if($canControl && $bot->id)
                            @if($bot->status === 'running')
                            <button
                                wire:click="commandBot({{ $bot->id }}, 'pause')"
                                wire:confirm="Поставити бота на паузу?"
                                style="font-size:0.75rem; padding:0.35rem 0.75rem; border-radius:0.375rem; border:1px solid #f59e0b66; background:#f59e0b11; color:#fbbf24; cursor:pointer;"
                            >Pause</button>
                            @elseif($bot->status === 'paused')
                            <button
                                wire:click="commandBot({{ $bot->id }}, 'resume')"
                                wire:confirm="Відновити роботу бота?"
                                style="font-size:0.75rem; padding:0.35rem 0.75rem; border-radius:0.375rem; border:1px solid #22c55e66; background:#22c55e11; color:#22c55e; cursor:pointer;"
                            >Resume</button>
                            @endif
                            <button
                                wire:click="commandBot({{ $bot->id }}, 'restart')"
                                wire:confirm="Перезапустити бота? Якщо позиція відкрита, бот має коректно завершити поточний цикл."
                                style="font-size:0.75rem; padding:0.35rem 0.75rem; border-radius:0.375rem; border:1px solid #60a5fa66; background:#60a5fa11; color:#93c5fd; cursor:pointer;"
                            >Restart</button>
                            <button
                                wire:click="commandBot({{ $bot->id }}, 'stop')"
                                wire:confirm="Зупинити бота?"
                                style="font-size:0.75rem; padding:0.35rem 0.75rem; border-radius:0.375rem; border:1px solid #ef444466; background:#ef444411; color:#f87171; cursor:pointer;"
                            >Stop</button>
                        @endif
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
                    <p style="font-size:0.8rem; color:{{ $bot->status === 'running' ? ($runtimeError !== '' ? '#f87171' : '#4b5563') : $statusColor }}; margin:0; font-weight:{{ $runtimeError !== '' || $bot->status !== 'running' ? '600' : '400' }};">{{ $noPositionMessage }}</p>
                    @if(($bot->source ?? null) === 'nautilus')
                    <div style="display:flex; flex-wrap:wrap; gap:0.35rem 0.75rem; margin-top:0.45rem; font-size:0.7rem; color:#9ca3af;">
                        <span>Акаунт: <strong style="color:#d1d5db;">{{ $bot->account }}</strong></span>
                        <span>Пара: <strong style="color:#d1d5db;">{{ $bot->symbol }}</strong></span>
                        <span>TF: <strong style="color:#d1d5db;">{{ $bot->timeframe ?? '—' }}</strong></span>
                        <span>Плече: <strong style="color:#d1d5db;">x{{ (int)$displayLeverage }}</strong></span>
                        <span>Режим: <strong style="color:#d1d5db;">{{ strtoupper((string)($bot->run_mode ?? 'live')) }}</strong></span>
                    </div>
                    @if($marginTelemetry)
                    <div style="margin-top:0.45rem; font-size:0.7rem; color:{{ $marginBlocked ? '#f87171' : '#9ca3af' }};">
                        Маржа entry/DCA: доступно {{ $marginAvailable ?? '—' }} · потрібно {{ $marginRequired ?? '—' }} · залишок {{ $marginRemaining ?? '—' }}
                        @if($marginPending > 0) · pending {{ $marginPending }} @endif
                        @if($marginBlocked) · Нові entry/DCA заблоковані; TP працює @elseif(!$marginKnown) · розрахунок недоступний @endif
                    </div>
                    @endif
                    @endif
                    @if($botStartedAtLabel)
                    <div style="display:flex; flex-wrap:wrap; gap:0.35rem 0.75rem; margin-top:0.45rem; font-size:0.7rem; color:#9ca3af;">
                        <span>Запущено: <strong style="color:#d1d5db;">{{ $botStartedAtLabel }} · Київ</strong></span>
                        @if($waitingForEntry)
                        <span>Очікує входу: <strong style="color:#d1d5db;">{{ $waitingTime }}</strong></span>
                        @endif
                    </div>
                    @endif
                    @if($netStats)
                    <div style="font-size:0.75rem; color:{{ $netColor }}; margin-top:0.35rem;">
                        net {{ $netSign }}{{ number_format((float)$netUsdt, 4) }} USDT · {{ $netTrades }} closed{{ $netWinLoss ? ' · '.$netWins.'/'.$netLosses : '' }}
                    </div>
                    @endif
                    @php
                        $chartSymbol2 = str_contains($bot->symbol ?? '', '/') ? $bot->symbol : strtoupper($bot->symbol).'/USDT:USDT';
                        $chartUrl2 = '/admin/chart-page?exchange='.urlencode($bot->market_exchange ?? $bot->exchange ?? 'bybit').'&symbol='.urlencode($chartSymbol2).'&tf=1h'.(!empty($bot->bot_ref) ? '&bot_ref='.urlencode($bot->bot_ref) : ($bot->id ? '&bot_id='.$bot->id : ''));
                    @endphp
                    <div style="margin-top:0.5rem;">
                        @if($canControl && $nautilusControlId !== '')
                        <div class="active-bot-actions" style="display:flex; gap:0.5rem; margin-bottom:0.5rem; flex-wrap:wrap;">
                            @if(in_array($bot->status, ['stopped', 'error', null], true))
                            <button
                                wire:click="commandNautilus('{{ $nautilusControlId }}', 'start')"
                                wire:confirm="Запустити Nautilus-бота? Перед стартом автоматично виконається preflight."
                                style="font-size:0.75rem; padding:0.35rem 0.75rem; border-radius:0.375rem; border:1px solid #22c55e66; background:#22c55e11; color:#22c55e; cursor:pointer;"
                            >Start</button>
                            @endif
                            <button
                                wire:click="commandNautilus('{{ $nautilusControlId }}', 'restart')"
                                wire:confirm="Перезапустити Nautilus-бота?"
                                style="font-size:0.75rem; padding:0.35rem 0.75rem; border-radius:0.375rem; border:1px solid #60a5fa66; background:#60a5fa11; color:#93c5fd; cursor:pointer;"
                            >Restart</button>
                            <button
                                wire:click="commandNautilus('{{ $nautilusControlId }}', 'stop')"
                                wire:confirm="Зупинити Nautilus-бота? Біржові TP/SL залишаться активними."
                                style="font-size:0.75rem; padding:0.35rem 0.75rem; border-radius:0.375rem; border:1px solid #ef444466; background:#ef444411; color:#f87171; cursor:pointer;"
                            >Stop</button>
                        </div>
                        @endif
                        @if($canControl && $bot->id)
                        <div class="active-bot-actions" style="display:flex; gap:0.5rem; margin-bottom:0.5rem; flex-wrap:wrap;">
                            @if($bot->status === 'running')
                            <button
                                wire:click="commandBot({{ $bot->id }}, 'pause')"
                                wire:confirm="Поставити бота на паузу?"
                                style="font-size:0.75rem; padding:0.35rem 0.75rem; border-radius:0.375rem; border:1px solid #f59e0b66; background:#f59e0b11; color:#fbbf24; cursor:pointer;"
                            >Pause</button>
                            @elseif($bot->status === 'paused')
                            <button
                                wire:click="commandBot({{ $bot->id }}, 'resume')"
                                wire:confirm="Відновити роботу бота?"
                                style="font-size:0.75rem; padding:0.35rem 0.75rem; border-radius:0.375rem; border:1px solid #22c55e66; background:#22c55e11; color:#22c55e; cursor:pointer;"
                            >Resume</button>
                            @elseif(in_array($bot->status, ['stopped', 'error', null], true))
                            <button
                                wire:click="commandBot({{ $bot->id }}, 'start')"
                                wire:confirm="Запустити бота?"
                                style="font-size:0.75rem; padding:0.35rem 0.75rem; border-radius:0.375rem; border:1px solid #22c55e66; background:#22c55e11; color:#22c55e; cursor:pointer;"
                            >Start</button>
                            @endif
                            <button
                                wire:click="commandBot({{ $bot->id }}, 'restart')"
                                wire:confirm="Перезапустити бота?"
                                style="font-size:0.75rem; padding:0.35rem 0.75rem; border-radius:0.375rem; border:1px solid #60a5fa66; background:#60a5fa11; color:#93c5fd; cursor:pointer;"
                            >Restart</button>
                            <button
                                wire:click="commandBot({{ $bot->id }}, 'stop')"
                                wire:confirm="Зупинити бота?"
                                style="font-size:0.75rem; padding:0.35rem 0.75rem; border-radius:0.375rem; border:1px solid #ef444466; background:#ef444411; color:#f87171; cursor:pointer;"
                            >Stop</button>
                        </div>
                        @endif
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
