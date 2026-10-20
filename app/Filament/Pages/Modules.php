<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Modules extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cube-transparent';
    protected static ?string $navigationLabel = 'Модулі';
    protected static ?string $title = 'Модулі';
    protected static ?int $navigationSort = 35;
    protected static string $view = 'filament.pages.modules';

    public static function canAccess(): bool
    {
        $tenant = app(\App\Services\TenantService::class);

        return $tenant->canSwitch()
            && $tenant->viewingTenantId() === \App\Services\TenantService::PRIMARY_USER_ID;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function getModules(): array
    {
        return [
            [
                'name' => 'Regime Lab',
                'status' => 'research-only',
                'responsibility' => 'Нейтральна карта ринку: state, direction, phase, subperiod, confidence, delay, stability.',
                'replaces' => 'Має замінити слабку/грубу стару логіку визначення періодів після доказів на історії.',
                'important' => 'Не визначає прибутковість і не називає періоди хорошими/поганими.',
                'files' => '_run_regime_lab.py, _run_regime_lab_continuous.py, docs/modules/REGIME_LAB.md',
            ],
            [
                'name' => 'Strategy Module',
                'status' => 'active initial implementation',
                'responsibility' => 'Окремий шар між Period Engine і Candidate/Entry Lab: визначає, які strategy-family варто досліджувати в конкретному контексті.',
                'replaces' => 'Замінює небезпечну ідею Zone Quality як фільтра хороших/поганих періодів.',
                'important' => 'Поточний top: shock_fade_reversal research-only baseline; не створює live-входи.',
                'files' => '_export_strategy_family_suitability.py, data/strategy_search/strategy_module/strategy_family_suitability_latest.json, docs/modules/STRATEGY_MODULE.md',
            ],
            [
                'name' => 'Zone Quality Diagnostics',
                'status' => 'converted to diagnostics',
                'responsibility' => 'Старі V1-V4 розрахунки edge/stability лишаються діагностикою для Strategy Module.',
                'replaces' => 'Більше не має права блокувати період або називати його хорошим/поганим.',
                'important' => 'Будь-який висновок має бути прив’язаний до конкретної strategy-family і executable validation.',
                'files' => '_run_zone_quality_*.py, docs/modules/ZONE_QUALITY_MODULE.md',
            ],
            [
                'name' => 'Candidate/Entry Lab',
                'status' => 'active research-only',
                'responsibility' => 'Перевіряє конкретні executable входи після Strategy Module: trigger, TP/SL/time, live-style lock, PF/net/DD.',
                'replaces' => 'Замінює raw edge як доказ. Тепер потрібні реальні потенційні входи, а не просто майбутній return.',
                'important' => 'Поточний probe: shock_fade_reversal, short shock_risk/up/shock_risk, без live-дій.',
                'files' => '_run_strategy_family_candidate_baseline.py, _run_shock_fade_context_sweep.py, data/strategy_search/candidate_entry_lab/*',
            ],
            [
                'name' => 'Site Verification',
                'status' => 'active, repaired',
                'responsibility' => 'Перевіряє сайт після редагування PHP/Blade: lint, routes, Livewire render, HTTP smoke, права runtime-cache.',
                'replaces' => 'Має замінити ручні фрази “перевірив сайт” без реального render під php-fpm/www-data.',
                'important' => 'Ключовий файл: _verify_website_post_change.sh. Після правки сторінок він обов’язковий.',
                'files' => '_verify_website_post_change.sh, _fix_website_runtime_permissions.py, docs/modules/SITE_MODULE.md',
            ],
            [
                'name' => 'Site Module',
                'status' => 'active display layer',
                'responsibility' => 'Адмін-сайт: Result, Dashboard, графік, Live Journal, Roadmap, модулі, таблиці кандидатів.',
                'replaces' => '',
                'important' => 'Показує тільки канонічні артефакти, не вигадує метрики.',
                'files' => '/home/ubuntu/website/app/Filament/Pages, docs/modules/SITE_MODULE.md',
            ],
            [
                'name' => 'Background/Ops',
                'status' => 'active',
                'responsibility' => 'Фони, CPU, watchdog, registry, завершені задачі, планувальник без live-дій.',
                'replaces' => 'Поступово замінює ручний запуск фонів на task-ledger scheduler.',
                'important' => 'Не має права сам зупиняти/стартувати live-ботів без GO.',
                'files' => 'launch_background_watchdog.py, _check_background_registry.py, docs/modules/BACKGROUND_OPS_MODULE.md',
            ],
            [
                'name' => 'Decision Core',
                'status' => 'read-only policy engine',
                'responsibility' => 'Зводить кандидатів, ризики, періоди, live-journal і видає рішення/попередження.',
                'replaces' => 'Має замінити ручне тлумачення розрізнених звітів.',
                'important' => 'Live-дії тільки як requires_user_go.',
                'files' => 'data/decision_core/*, docs/modules/ANALYZER_DECISION_CORE.md',
            ],
            [
                'name' => 'Control Tower',
                'status' => 'read-only supervisor',
                'responsibility' => 'Перевіряє стан усіх модулів, P0/P1 ризики, safe-auto queue.',
                'replaces' => 'Має стати центральним контролером готовності проекту.',
                'important' => 'Не торгує і не керує ботами напряму.',
                'files' => 'data/control_tower/*, docs/modules/CONTROL_TOWER_MODULE.md',
            ],
            [
                'name' => 'ML Module',
                'status' => 'shadow-only',
                'responsibility' => 'ML dataset, historical replay trainer, point-in-time trainer, ranking/explanations.',
                'replaces' => 'У майбутньому може замінити частину ручного ранжування, але не live-gate без окремого GO.',
                'important' => 'Зараз тільки shadow, не блокує і не дозволяє live-входи.',
                'files' => 'docs/modules/ML_MODULE.md, data/ml_decision_lab/*',
            ],
            [
                'name' => 'Research V4',
                'status' => 'active',
                'responsibility' => 'Пошук, нормалізація, реєстр, revalidation, selection report кандидатів.',
                'replaces' => 'Замінює хаотичні окремі пошуки на канонічний candidate research OS.',
                'important' => 'Кандидат не стає live без Live Execution gate і GO.',
                'files' => 'docs/modules/RESEARCH_V4_MODULE.md, data/research/*',
            ],
            [
                'name' => 'Opportunity Research Lab',
                'status' => 'architecture active',
                'responsibility' => 'Шукає opportunity events, live-like replay, фільтри входів, period binding.',
                'replaces' => 'Має поступово замінити direct/random search там, де потрібна краща логіка входу.',
                'important' => 'Read-only research; live promotion заборонена.',
                'files' => 'docs/modules/OPPORTUNITY_RESEARCH_LAB_MODULE.md',
            ],
            [
                'name' => 'Live Execution',
                'status' => 'active, guarded',
                'responsibility' => 'Live bot processes/configs, exchange fills, journal, risk gates, position lifecycle.',
                'replaces' => '',
                'important' => 'Будь-яка live-дія тільки після явного GO.',
                'files' => 'live/*, docs/modules/LIVE_EXECUTION_MODULE.md',
            ],
            [
                'name' => 'DB V3',
                'status' => 'usable',
                'responsibility' => 'Market data, parquet/data-lake, HistoryService, parity checks.',
                'replaces' => 'Має стати стабільною data-platform основою для Research/ML.',
                'important' => 'Не приймає торгові рішення.',
                'files' => 'docs/modules/DB_V3_MODULE.md',
            ],
        ];
    }
}
