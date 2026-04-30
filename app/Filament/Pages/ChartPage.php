<?php

namespace App\Filament\Pages;

use App\Models\Bot;
use App\Services\TenantService;
use Filament\Pages\Page;
use PDO;

class ChartPage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationLabel = 'Графік';
    protected static ?string $title           = 'Графік';
    protected static ?int    $navigationSort  = 5;

    protected static string $view = 'filament.pages.chart';

    private const BACKTESTER_DB = '/home/ubuntu/SafaryEngine/data/backtester.db';

    public string  $exchange = 'okx';
    public string  $symbol   = 'HYPE/USDT:USDT';
    public string  $tf       = '1h';
    public ?int    $botId    = null;
    public ?string $botName  = null;

    public function mount(): void
    {
        $req = request();
        if ($req->has('exchange')) $this->exchange = $req->input('exchange', 'okx');
        if ($req->has('symbol'))   $this->symbol   = $req->input('symbol',   'HYPE/USDT:USDT');
        if ($req->has('tf'))       $this->tf        = $req->input('tf',       '1h');
        if ($req->has('bot_id')) {
            $this->botId   = (int) $req->input('bot_id');
            $bot = \App\Models\Bot::find($this->botId);
            $this->botName = $bot?->slug;
        }
    }

    public static function canAccess(): bool
    {
        return \Illuminate\Support\Facades\Auth::check();
    }

    protected function getViewData(): array
    {
        $tenant = app(TenantService::class);

        // ── Read available exchange/symbol pairs from backtester.db ──────
        $dbSymbols = $this->loadDbSymbols();

        // ── Also merge symbols from bot records (in case they're not yet in candle db) ──
        foreach (Bot::select('exchange', 'symbol')->distinct()->get() as $b) {
            $full = $this->toFullSymbol($b->exchange, $b->symbol);
            $exch = strtolower($b->exchange);
            if (! isset($dbSymbols[$exch])) $dbSymbols[$exch] = [];
            if (! in_array($full, $dbSymbols[$exch])) {
                $dbSymbols[$exch][] = $full;
            }
        }

        // Sort exchanges and symbols predictably
        ksort($dbSymbols);
        foreach ($dbSymbols as &$syms) {
            sort($syms);
        }
        unset($syms);

        $sessionStart = $tenant->getSessionStart();

        return [
            'symbolsByExchange' => $dbSymbols,          // ['okx' => ['HYPE/USDT:USDT', ...], ...]
            'exchange'          => $this->exchange,
            'symbol'            => $this->symbol,
            'tf'                => $this->tf,
            'botId'             => $this->botId,
            'botName'           => $this->botName,
            'sessionStart'      => $sessionStart?->timestamp,
        ];
    }

    /** Read distinct exchange+symbol from backtester.db candles table (cached 10 min). */
    private function loadDbSymbols(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('chart_db_symbols', 600, function () {
            if (! file_exists(self::BACKTESTER_DB)) return [];
            try {
                $pdo = new PDO('sqlite:' . self::BACKTESTER_DB);
                // Use index-friendly query: scan index prefix per exchange group
                $rows = $pdo->query(
                    "SELECT exchange, symbol FROM candles
                     GROUP BY exchange, symbol
                     ORDER BY exchange, symbol"
                )->fetchAll(PDO::FETCH_ASSOC);

                $out = [];
                foreach ($rows as $r) {
                    $out[strtolower($r['exchange'])][] = $r['symbol'];
                }
                return $out;
            } catch (\Throwable) {
                return [];
            }
        });
    }

    private function toFullSymbol(string $exchange, string $symbol): string
    {
        if (str_contains($symbol, '/')) return $symbol;
        return strtoupper($symbol) . '/USDT:USDT';
    }
}
