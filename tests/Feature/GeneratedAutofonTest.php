<?php

namespace Tests\Feature;

use App\Filament\Pages\GeneratedAutofon;
use App\Filament\Pages\GeneratedAutofonArchive;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class GeneratedAutofonTest extends TestCase
{
    public function createApplication()
    {
        $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $storage = sys_get_temp_dir().'/autofon-feature-storage';
        foreach (['framework/cache', 'framework/views', 'framework/sessions', 'logs', 'app'] as $folder) {
            @mkdir($storage.'/'.$folder, 0770, true);
        }
        $app->useStoragePath($storage);
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        umask(0002);
        parent::setUp();
        $folder = sys_get_temp_dir().'/autofon-feature-views';
        @mkdir($folder, 0770, true);
        config(['session.driver' => 'array', 'cache.default' => 'array', 'view.compiled' => $folder]);
        (new \ReflectionProperty(app('blade.compiler'), 'cachePath'))->setValue(app('blade.compiler'), $folder);
        $this->actingAs(User::query()->findOrFail(1));
        session(['viewing_tenant' => 1, 'two_factor_verified_at' => now()->toDateTimeString()]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Http::preventStrayRequests();
    }

    private function current(string $status = 'stopped'): array
    {
        return ['schema' => 'bybit_generated_gateway_v2', 'generated_at_utc' => '2026-10-03T08:00:00Z',
            'year2024_search' => ['status' => $status, 'stage' => $status === 'stopped' ? 'paused' : 'failed',
                'error' => $status === 'needs_attention' ? 'test_worker_failed' : null,
                'continuous' => ['status' => $status, 'completed_batches' => 38, 'stop_requested' => true],
                'counts' => ['new' => ['optuna_trials' => 520]],
                'top10_2024' => ['period' => ['2024-01-01', '2026-09-19'], 'candidates' => [[
                    'candidate_id' => 'gen_4832804c39625e0c', 'rank' => 1, 'net_pnl_usdt' => 600,
                    'completed_trades' => 300, 'max_dd_usdt' => 100, 'annual_display_status' => 'verified',
                    'parameters' => ['tp_pct' => 3, 'sl_pct' => 5, 'basis_z_min' => -1.7629],
                    'annual_display' => ['2024' => ['net_pnl_usdt' => 100, 'exits' => 100],
                        '2025' => ['net_pnl_usdt' => 200, 'exits' => 100], '2026' => ['net_pnl_usdt' => 300, 'exits' => 100]],
                ]]]]];
    }

    public function test_opening_page_does_not_wait_for_gateway(): void
    {
        Http::fake();
        $this->get('/admin/generated-autofon')->assertOk()->assertSee('Завантаження поточного стану');
        Http::assertNothingSent();
    }

    public function test_current_page_has_one_ranking_and_correct_stop_message(): void
    {
        Http::fake(['*/v1/autofon/current*' => Http::response($this->current())]);
        Livewire::test(GeneratedAutofon::class)->call('refreshStatus')
            ->assertSee('Автофон зупинено після партії 38')
            ->assertSee('gen_4832804c39625e0c')->assertSee('100,00 USDT')
            ->assertDontSee('поточна партія завершиться')
            ->assertDontSee('Історичний Top 10')->assertDontSee('Цикл 13')
            ->assertDontSee('Поріг z-score базису');
        Http::assertSentCount(1);
    }

    public function test_candidate_parameters_and_batch_results_load_on_click(): void
    {
        Http::fake(['*/v1/autofon/current*' => function ($request) {
            $data = $this->current();
            if (($request->data()['details'] ?? null) === '1') {
                $data['year2024_search']['last_batch']['arms'] = ['new' => ['candidates' => [[
                    'candidate_id' => 'gen_finalist', 'status' => 'eligible', 'selected_trial' => 7,
                    'transfer_2026' => ['net_pnl_usdt' => 10, 'completed_trades' => 51],
                    'full_2024' => ['net_pnl_usdt' => 60, 'status' => 'eligible'],
                ]]]];
            }
            return Http::response($data);
        }]);
        Livewire::test(GeneratedAutofon::class)->call('refreshStatus')
            ->assertDontSee('gen_finalist')->call('toggleCandidate', 'gen_4832804c39625e0c')
            ->assertSee('Поріг z-score базису')->call('toggleDetails')
            ->assertSee('gen_finalist')->assertSee('Пройшов повну перевірку')
            ->call('toggleDetails')->assertDontSee('gen_finalist');
        Http::assertSent(fn ($request) => ($request->data()['details'] ?? null) === '1');
    }

    public function test_current_error_is_visible(): void
    {
        Http::fake(['*/v1/autofon/current*' => Http::response($this->current('needs_attention'))]);
        Livewire::test(GeneratedAutofon::class)->call('refreshStatus')
            ->assertSee('Потрібне втручання')->assertSee('test_worker_failed');
    }

    public function test_warming_is_visible(): void
    {
        Http::fake(['*/v1/autofon/current*' => Http::response([
            'schema' => 'bybit_generated_gateway_v2', 'initializing' => true], 202)]);
        Livewire::test(GeneratedAutofon::class)->call('refreshStatus')->assertSee('Перевіряємо збережені результати');
    }

    public function test_archive_loads_only_selected_cycle(): void
    {
        Http::fake([
            '*/v1/autofon/archive' => Http::response(['schema' => 'bybit_generated_gateway_v2',
                'sections' => ['cycle-013' => 'Генератор · цикл 013', 'cycle-012' => 'Генератор · цикл 012']]),
            '*/v1/autofon/archive/section*' => Http::response(['schema' => 'bybit_generated_gateway_v2',
                'cycles' => [['cycle' => 13, 'status' => 'complete', 'candidates' => [], 'attempts' => []]]]),
        ]);
        $component = Livewire::test(GeneratedAutofonArchive::class)->call('refreshStatus');
        Http::assertSentCount(1);
        $component->assertSee('Оберіть розділ архіву')->assertDontSee('Жоден кандидат не пройшов')
            ->call('selectSection', 'cycle-013')->assertSee('Цикл 13')->assertDontSee('Цикл 12')
            ->assertDontSee('Top 10 · SOL Long');
        Http::assertSent(fn ($request) => ($request->data()['section'] ?? null) === 'cycle-013');
    }

    public function test_tenant_access_is_checked_again_on_livewire_refresh(): void
    {
        Http::fake();
        $component = Livewire::test(GeneratedAutofon::class);
        session(['viewing_tenant' => 2]);
        $component->call('refreshStatus')->assertForbidden();
        Http::assertNothingSent();
    }
}
