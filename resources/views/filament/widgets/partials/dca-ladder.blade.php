@php
    $variant = $variant ?? 'detail';
    $additions = is_array($dca['additions'] ?? null) ? $dca['additions'] : [];
    $initial = is_array($dca['initial'] ?? null) ? $dca['initial'] : null;
    $takeProfit = is_array($dca['take_profit'] ?? null) ? $dca['take_profit'] : null;
    $side = strtolower((string)($pos['side_str'] ?? 'long'));
    $current = (float)($markPrice ?: $avgPrice);
    $levels = [];
    foreach ($additions as $row) {
        if (is_numeric($row['price'] ?? null)) $levels[] = (float)$row['price'];
    }
    if ($initial && is_numeric($initial['price'] ?? null)) $levels[] = (float)$initial['price'];
    if ($takeProfit && is_numeric($takeProfit['price'] ?? null)) $levels[] = (float)$takeProfit['price'];
    if ($current > 0) $levels[] = $current;
    if ($avgPrice > 0) $levels[] = (float)$avgPrice;
    if ($slPrice && $slPrice > 0) $levels[] = (float)$slPrice;
    $levels = array_values(array_filter($levels, fn($value) => $value > 0));
    $laneMin = $levels ? min($levels) : 0;
    $laneMax = $levels ? max($levels) : 1;
    $laneSpan = max($laneMax - $laneMin, $laneMin * 0.001, 0.000001);
    $lanePad = $laneSpan * 0.08;
    $laneMin -= $lanePad;
    $laneMax += $lanePad;
    $laneSpan = $laneMax - $laneMin;
    $lanePct = fn($value) => $laneSpan > 0 ? round(max(0, min(100, (($value - $laneMin) / $laneSpan) * 100)), 2) : 50;
    $completed = max(0, (int)($dca['completed_additions'] ?? 0));
    $maxAdditions = max(count($additions), (int)($dca['max_additions'] ?? count($additions)));
    $statusLabel = [
        'filled' => 'виконано',
        'partial' => 'частково',
        'pending' => 'очікує',
        'cancelled' => 'скасовано',
        'rejected' => 'відхилено',
    ];
    $statusColor = [
        'filled' => '#22c55e',
        'partial' => '#60a5fa',
        'pending' => '#f59e0b',
        'cancelled' => '#94a3b8',
        'rejected' => '#ef4444',
    ];
    $orderColor = fn($row) => $statusColor[$row['status'] ?? 'pending'] ?? '#f59e0b';
    $orderLabel = fn($row) => $statusLabel[$row['status'] ?? 'pending'] ?? 'очікує';
    $format = $fmt ?? static function ($value) {
        $value = (float)$value;
        if ($value >= 10000) return number_format($value, 1);
        if ($value >= 1000) return number_format($value, 2);
        if ($value >= 10) return number_format($value, 3);
        if ($value >= 1) return number_format($value, 4);
        return number_format($value, 5);
    };
@endphp

@if($variant === 'compact')
    <div class="dca-compact-lane" style="min-width:0;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:0.5rem; margin-bottom:0.25rem; font-size:0.68rem;">
            <span style="color:#f59e0b; font-weight:700;">DCA {{ $completed }}/{{ $maxAdditions }}</span>
            <span style="color:#60a5fa; font-weight:600;">{{ $format($current) }}</span>
        </div>
        {{-- Entry: white bar with a thin violet core; filled additions grey, pending ones amber outlines; green is the TP only. --}}
        <div style="position:relative; height:30px; margin:0 2px;">
            <div style="position:absolute; left:0; right:0; top:17px; height:5px; background:#1f2937; border-radius:999px;"></div>
            @if($initial && is_numeric($initial['price'] ?? null))
                <span title="Вхід {{ $format($initial['price']) }}" style="position:absolute; left:{{ $lanePct((float)$initial['price']) }}%; top:11px; width:7px; height:18px; background:linear-gradient(90deg, #f8fafc 3px, #a855f7 3px, #a855f7 4px, #f8fafc 4px); border-radius:2px; transform:translateX(-50%);"></span>
            @endif
            @foreach($additions as $row)
                @php
                    $rowStatus = $row['status'] ?? 'pending';
                    $markerStyle = match ($rowStatus) {
                        'filled' => 'background:#94a3b8; border:1px solid #0f172a;',
                        'pending' => 'background:#111827; border:2px solid #f59e0b;',
                        default => 'background:' . $orderColor($row) . '; border:1px solid #0f172a;',
                    };
                @endphp
                <span title="{{ $row['label'] ?? 'DCA' }} {{ $format($row['price'] ?? 0) }} · {{ $orderLabel($row) }}" style="position:absolute; left:{{ $lanePct((float)($row['price'] ?? 0)) }}%; top:13px; width:9px; height:13px; {{ $markerStyle }} box-sizing:border-box; border-radius:3px; transform:translateX(-50%);"></span>
            @endforeach
            @if($current > 0)
                <span title="Поточна ціна {{ $format($current) }}" style="position:absolute; left:{{ $lanePct($current) }}%; top:11px; width:3px; height:18px; background:#60a5fa; border-radius:2px; transform:translateX(-50%); box-shadow:0 0 5px #60a5fa99;"></span>
            @endif
            @if($tpPrice)
                <span style="position:absolute; left:{{ $lanePct((float)$tpPrice) }}%; top:-1px; font-size:0.62rem; font-weight:700; line-height:1; color:#4ade80; transform:translateX(-50%);">TP</span>
                <span title="TP {{ $format($tpPrice) }}" style="position:absolute; left:{{ $lanePct((float)$tpPrice) }}%; top:11px; width:5px; height:18px; background:#4ade80; border:1px solid #f8fafc; box-sizing:border-box; border-radius:2px; transform:translateX(-50%); z-index:1;"></span>
            @endif
            @if($slPrice)
                <span title="SL {{ $format($slPrice) }}" style="position:absolute; left:{{ $lanePct((float)$slPrice) }}%; top:11px; width:3px; height:18px; background:#ef4444; border-radius:2px; transform:translateX(-50%);"></span>
            @endif
        </div>
        <div style="display:flex; justify-content:space-between; gap:0.5rem; font-size:0.6rem; color:#64748b;">
            <span>{{ $side === 'short' ? 'докупка вище' : 'докупка нижче' }}</span>
            <span>TP {{ $tpPrice ? $format($tpPrice) : '—' }}</span>
        </div>
    </div>
@else
    <div class="dca-ladder-panel" style="margin-bottom:0.85rem; padding:0.75rem; border:1px solid #334155; border-radius:0.5rem; background:#111827;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:0.75rem; flex-wrap:wrap; margin-bottom:0.65rem;">
            <div>
                <div style="font-size:0.8rem; color:#f8fafc; font-weight:700;">DCA ladder · {{ $completed }}/{{ $maxAdditions }} докупок</div>
                <div style="font-size:0.65rem; color:#64748b;">{{ strtoupper($side) }} · {{ $side === 'short' ? 'докупка при русі ціни вгору' : 'докупка при русі ціни вниз' }}</div>
            </div>
            <div style="font-size:0.68rem; color:#60a5fa;">Поточна {{ $format($current) }}</div>
        </div>

        <div class="dca-detail-lane" style="position:relative; height:72px; margin:0.1rem 0.25rem 0.7rem;">
            <div style="position:absolute; left:0; right:0; top:27px; height:7px; background:#1f2937; border-radius:999px;"></div>
            @if($initial && is_numeric($initial['price'] ?? null))
                @php $initialPct = $lanePct((float)$initial['price']); @endphp
                <div style="position:absolute; left:{{ $initialPct }}%; top:14px; transform:translateX(-50%); text-align:center; min-width:52px;">
                    <div style="font-size:0.62rem; color:#f8fafc; font-weight:700;">Вхід</div>
                    <div style="width:3px; height:25px; background:#f8fafc; margin:2px auto 0; border-radius:2px;"></div>
                    <div style="font-size:0.6rem; color:#cbd5e1; white-space:nowrap;">{{ $format($initial['price']) }}</div>
                </div>
            @endif
            @foreach($additions as $row)
                @php
                    $rowPrice = (float)($row['price'] ?? 0);
                    $rowPct = $lanePct($rowPrice);
                    $rowColor = $orderColor($row);
                @endphp
                <div style="position:absolute; left:{{ $rowPct }}%; top:35px; transform:translateX(-50%); text-align:center; min-width:48px;">
                    <div style="width:10px; height:10px; margin:0 auto 2px; background:{{ $rowColor }}; border:2px solid #0f172a; border-radius:50%; box-shadow:0 0 0 1px {{ $rowColor }}66;"></div>
                    <div style="font-size:0.62rem; color:{{ $rowColor }}; font-weight:700;">{{ $row['label'] ?? 'DCA' }}</div>
                    <div style="font-size:0.58rem; color:#94a3b8; white-space:nowrap;">{{ $format($rowPrice) }}</div>
                </div>
            @endforeach
            @if($current > 0)
                @php $currentPct = $lanePct($current); @endphp
                <div style="position:absolute; left:{{ $currentPct }}%; top:3px; transform:translateX(-50%); text-align:center; min-width:62px; z-index:2;">
                    <div style="font-size:0.62rem; color:#60a5fa; font-weight:700;">Зараз</div>
                    <div style="width:4px; height:27px; background:#60a5fa; margin:2px auto 0; border-radius:2px; box-shadow:0 0 6px #60a5fa99;"></div>
                    <div style="font-size:0.6rem; color:#bfdbfe; white-space:nowrap;">{{ $format($current) }}</div>
                </div>
            @endif
            @if($tpPrice)
                <div style="position:absolute; left:{{ $lanePct((float)$tpPrice) }}%; top:14px; transform:translateX(-50%); text-align:center; min-width:52px;">
                    <div style="font-size:0.62rem; color:#22c55e; font-weight:700;">TP</div>
                    <div style="width:3px; height:25px; background:#22c55e; margin:2px auto 0; border-radius:2px;"></div>
                    <div style="font-size:0.6rem; color:#86efac; white-space:nowrap;">{{ $format($tpPrice) }}</div>
                </div>
            @endif
            @if($slPrice)
                <div style="position:absolute; left:{{ $lanePct((float)$slPrice) }}%; top:14px; transform:translateX(-50%); text-align:center; min-width:52px;">
                    <div style="font-size:0.62rem; color:#ef4444; font-weight:700;">SL</div>
                    <div style="width:3px; height:25px; background:#ef4444; margin:2px auto 0; border-radius:2px;"></div>
                    <div style="font-size:0.6rem; color:#fca5a5; white-space:nowrap;">{{ $format($slPrice) }}</div>
                </div>
            @endif
        </div>

        <div class="dca-order-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(125px, 1fr)); gap:0.4rem;">
            @if($initial)
                @php $initialColor = $orderColor($initial); @endphp
                <div style="padding:0.45rem; border:1px solid #334155; border-left:3px solid {{ $initialColor }}; border-radius:0.35rem; background:#0f172a;">
                    <div style="display:flex; justify-content:space-between; gap:0.25rem; font-size:0.68rem; font-weight:700; color:#f8fafc;"><span>Вхід</span><span style="color:{{ $initialColor }};">{{ $orderLabel($initial) }}</span></div>
                    <div style="font-size:0.72rem; color:#cbd5e1; margin-top:0.2rem;">{{ $format($initial['price']) }}</div>
                    <div style="font-size:0.6rem; color:#64748b;">{{ $format($initial['filled_quantity'] ?? 0) }} / {{ $format($initial['quantity']) }}</div>
                </div>
            @endif
            @foreach($additions as $row)
                @php $rowColor = $orderColor($row); @endphp
                <div style="padding:0.45rem; border:1px solid #334155; border-left:3px solid {{ $rowColor }}; border-radius:0.35rem; background:#0f172a;">
                    <div style="display:flex; justify-content:space-between; gap:0.25rem; font-size:0.68rem; font-weight:700; color:#f8fafc;"><span>{{ $row['label'] ?? 'DCA' }}</span><span style="color:{{ $rowColor }};">{{ $orderLabel($row) }}</span></div>
                    <div style="font-size:0.72rem; color:#cbd5e1; margin-top:0.2rem;">{{ $format($row['price']) }}</div>
                    <div style="font-size:0.6rem; color:#64748b;">{{ $format($row['filled_quantity'] ?? 0) }} / {{ $format($row['quantity']) }}</div>
                </div>
            @endforeach
            @if($takeProfit)
                <div style="padding:0.45rem; border:1px solid #334155; border-left:3px solid #22c55e; border-radius:0.35rem; background:#0f172a;">
                    <div style="display:flex; justify-content:space-between; gap:0.25rem; font-size:0.68rem; font-weight:700; color:#f8fafc;"><span>TP</span><span style="color:#22c55e;">{{ $orderLabel($takeProfit) }}</span></div>
                    <div style="font-size:0.72rem; color:#cbd5e1; margin-top:0.2rem;">{{ $format($takeProfit['price']) }}</div>
                    <div style="font-size:0.6rem; color:#64748b;">{{ $format($takeProfit['filled_quantity'] ?? 0) }} / {{ $format($takeProfit['quantity']) }}</div>
                </div>
            @endif
        </div>

        <div style="display:flex; gap:0.8rem; flex-wrap:wrap; margin-top:0.65rem; padding-top:0.55rem; border-top:1px solid #1f2937; font-size:0.65rem; color:#94a3b8;">
            <span>Середня: <b style="color:#d1d5db;">{{ $format($avgPrice) }}</b></span>
            <span>Зараз: <b style="color:#60a5fa;">{{ $format($current) }}</b></span>
            <span>TP: <b style="color:#22c55e;">{{ $tpPrice ? $format($tpPrice) : '—' }}</b></span>
            <span>SL: <b style="color:#ef4444;">{{ $slPrice ? $format($slPrice) : '—' }}</b></span>
        </div>
    </div>
@endif
