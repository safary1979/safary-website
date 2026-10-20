<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class MarketRegimeV2ApiTest extends TestCase
{
    public function test_authenticated_user_can_load_causal_sol_regimes(): void
    {
        $user = User::query()->find(1);

        $this->assertNotNull($user);

        $response = $this
            ->actingAs($user)
            ->withSession([
                'viewing_tenant' => 1,
                'two_factor_verified_at' => now()->toDateTimeString(),
            ])
            ->get('/chart-api/market-regimes-v2?exchange=okx&symbol=SOL%2FUSDT%3AUSDT&from=2026-02-07&to=2026-06-13');

        $response
            ->assertOk()
            ->assertJsonPath('causal', true)
            ->assertJsonPath('strategy_or_pnl_used', false)
            ->assertJsonStructure([
                'version',
                'period' => ['start', 'end_exclusive'],
                'regimes' => [[
                    'start_time', 'end_time', 'mode', 'abbr', 'label', 'color',
                ]],
            ]);

        $this->assertNotEmpty($response->json('regimes'));
    }

    public function test_authenticated_user_can_load_accepted_low_latency_regimes(): void
    {
        $user = User::query()->find(1);

        $this->assertNotNull($user);

        $response = $this
            ->actingAs($user)
            ->withSession([
                'viewing_tenant' => 1,
                'two_factor_verified_at' => now()->toDateTimeString(),
            ])
            ->get('/chart-api/market-regimes-v3?exchange=okx&symbol=SOL%2FUSDT%3AUSDT&from=2026-02-07&to=2026-06-13');

        $response
            ->assertOk()
            ->assertJsonPath('causal', true)
            ->assertJsonPath('strategy_or_pnl_used', false)
            ->assertJsonPath('selection', 'cusum_h10.00_k0.10')
            ->assertJsonPath('audit.median_delay_h_le_4', true)
            ->assertJsonPath('audit.prefix_causality_zero_mismatches', true)
            ->assertJsonStructure([
                'version',
                'period' => ['start', 'end_exclusive'],
                'regimes' => [[
                    'start_time', 'end_time', 'mode', 'abbr', 'label', 'color',
                ]],
            ]);

        $this->assertNotEmpty($response->json('regimes'));
    }

    public function test_one_hour_chart_window_covers_the_complete_regime_period(): void
    {
        $response = $this->get(
            '/chart-api/candles?exchange=okx&symbol=SOL%2FUSDT%3AUSDT&tf=1h'
            .'&from=1767225600000&to=1786233600000'
        );

        $response->assertOk();
        $this->assertGreaterThan(3000, count($response->json()));
    }
}
