<x-filament-panels::page>
    @php($data = $this->getRegimePeriods())
    @php($current = $data['current_period'] ?? [])

    <div wire:poll.300s style="display:grid; gap:1rem;">
        @if (empty($data['available']))
            <div style="padding:1rem; border:1px solid #1f2937; border-radius:0.5rem; background:#020617; color:#cbd5e1;">
                Market regime data is not available yet.
            </div>
        @else
            <div style="padding:1rem; border:1px solid #1f2937; border-radius:0.5rem; background:#0f172a; color:#cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.75rem;">
                    <div>
                        <div style="font-size:0.8rem; color:#94a3b8;">Поточний режим</div>
                        <div style="font-size:1.1rem; font-weight:700; color:#f8fafc;">{{ $current['major_key'] ?? '' }}</div>
                    </div>
                    <div style="font-size:0.8rem; color:#94a3b8;">{{ $data['generated_at'] }}</div>
                </div>
                <div style="display:grid; gap:0.45rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.84rem; line-height:1.45;">
                    <div>
                        <span style="color:#94a3b8;">period:</span>
                        {{ $current['start_kyiv'] ?? '' }} -> {{ $current['end_kyiv'] ?? '' }}
                        <span style="color:#64748b;"> | </span>
                        {{ $current['duration'] ?? '' }}
                    </div>
                    <div>
                        <span style="color:#94a3b8;">details:</span>
                        trend={{ $current['trend'] ?? '' }},
                        vol={{ $current['vol'] ?? '' }},
                        range={{ $current['range'] ?? '' }},
                        impulse={{ $current['impulse'] ?? '' }},
                        cluster={{ $current['cluster'] ?? '' }}
                    </div>
                    <div>
                        <span style="color:#94a3b8;">metrics:</span>
                        hype720={{ $current['avg_hype_ret_720m_pct'] ?? 0 }}%,
                        btc720={{ $current['avg_btc_ret_720m_pct'] ?? 0 }}%,
                        atr60={{ $current['avg_hype_atr60_pct'] ?? 0 }}%,
                        hype1440sum={{ $current['sum_hype_ret_1440m_pct'] ?? 0 }}%
                    </div>
                </div>
            </div>

            <div style="padding:1rem; border:1px solid #1f2937; border-radius:0.5rem; background:#020617; color:#cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.7rem;">
                    <div style="font-size:0.9rem; font-weight:700; color:#f8fafc;">Огляд</div>
                    <div style="font-size:0.8rem; color:#94a3b8;">
                        bins={{ $data['counts']['bins_12h'] ?? 0 }};
                        periods={{ $data['counts']['periods'] ?? 0 }};
                        similar={{ $data['counts']['similar_to_current'] ?? 0 }}
                    </div>
                </div>
                <div style="font-size:0.82rem; color:#94a3b8; line-height:1.45;">
                    {{ $data['sources']['from_utc'] ?? '' }} -> {{ $data['sources']['to_utc'] ?? '' }};
                    {{ $data['method']['major_key'] ?? '' }}.
                    {{ $data['method']['note'] ?? '' }}
                </div>
            </div>

            <div style="padding:1rem; border:1px solid #1f2937; border-radius:0.5rem; background:#020617; color:#cbd5e1;">
                <div style="font-size:0.9rem; font-weight:700; color:#f8fafc; margin-bottom:0.7rem;">Схожі періоди</div>
                @if (! empty($data['similar_periods']))
                    <ol style="margin:0; padding-left:1.25rem; display:grid; gap:0.45rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.82rem; line-height:1.45;">
                        @foreach ($data['similar_periods'] as $period)
                            <li>
                                <span style="color:#e2e8f0;">{{ $period['duration'] ?? '' }}</span>
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#cbd5e1;">{{ $period['start_kyiv'] ?? '' }} -> {{ $period['end_kyiv'] ?? '' }}</span>
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#94a3b8;">{{ $period['regime_key'] ?? '' }}</span>
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#cbd5e1;">hype720 {{ $period['avg_hype_ret_720m_pct'] ?? 0 }}%, btc720 {{ $period['avg_btc_ret_720m_pct'] ?? 0 }}%</span>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <div style="font-size:0.85rem; color:#94a3b8;">Схожих періодів не знайдено.</div>
                @endif
            </div>

            <div style="padding:1rem; border:1px solid #1f2937; border-radius:0.5rem; background:#020617; color:#cbd5e1;">
                <div style="font-size:0.9rem; font-weight:700; color:#f8fafc; margin-bottom:0.7rem;">Останні періоди</div>
                <div style="display:grid; gap:0.4rem;">
                    @foreach ($data['recent_periods'] as $period)
                        @php($trend = $period['trend'] ?? '')
                        @php($trendColor = $trend === 'up' ? '#86efac' : ($trend === 'down' ? '#fca5a5' : '#fde68a'))
                        <div style="display:grid; grid-template-columns:minmax(8rem, 0.6fr) minmax(10rem, 1.3fr) minmax(12rem, 1.1fr); gap:0.75rem; align-items:center; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.8rem; line-height:1.35; border-top:1px solid #1f2937; padding-top:0.4rem;">
                            <div style="color:{{ $trendColor }};">{{ $period['major_key'] ?? '' }}</div>
                            <div style="color:#cbd5e1;">{{ $period['start_kyiv'] ?? '' }} -> {{ $period['end_kyiv'] ?? '' }}</div>
                            <div style="color:#94a3b8;">{{ $period['duration'] ?? '' }} | {{ $period['regime_key'] ?? '' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
