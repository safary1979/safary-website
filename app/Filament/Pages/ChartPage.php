<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use PDO;

class ChartPage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationLabel = 'Графік';
    protected static ?string $title           = 'Графік';
    protected static ?int    $navigationSort  = 5;

    protected static string $view = 'filament.pages.chart';

    private const MARKET_DATA_DB = '/home/ubuntu/website/storage/app/market-data/market_data.sqlite';

    public string  $exchange = 'bybit';
    public string  $symbol   = 'SOL/USDT:USDT';
    public string  $tf       = '1h';
    public ?int    $botId    = null;
    public ?string $botName  = null;
    public ?string $botRef   = null;

    public function mount(): void
    {
        $req = request();
        if ($req->has('exchange')) $this->exchange = $req->input('exchange', 'bybit');
        if ($req->has('symbol'))   $this->symbol   = $req->input('symbol',   'SOL/USDT:USDT');
        if ($req->has('tf'))       $this->tf        = $req->input('tf',       '1h');
        $candidateRef = (string) $req->input('bot_ref', '');
        if (preg_match('/^(nautilus|freqtrade):[A-Za-z0-9._-]+$/', $candidateRef)) {
            $this->botRef = $candidateRef;
            $this->botName = $candidateRef;
        }
        // The chart is deliberately independent from live-bot databases.
        // Legacy bot_id links still open the chart, but never make rendering fail.
    }

    public static function canAccess(): bool
    {
        return \Illuminate\Support\Facades\Auth::check();
    }

    protected function getViewData(): array
    {
        $dbSymbols = $this->loadAvailableSymbols();

        $currentExchange = strtolower($this->exchange);
        if (! isset($dbSymbols[$currentExchange])) {
            $dbSymbols[$currentExchange] = [];
        }
        if (! in_array($this->symbol, $dbSymbols[$currentExchange], true)) {
            $dbSymbols[$currentExchange][] = $this->symbol;
        }

        // Sort exchanges and symbols predictably
        ksort($dbSymbols);
        foreach ($dbSymbols as &$syms) {
            sort($syms);
        }
        unset($syms);

        return [
            'symbolsByExchange' => $dbSymbols,          // ['okx' => ['HYPE/USDT:USDT', ...], ...]
            'exchange'          => $this->exchange,
            'symbol'            => $this->symbol,
            'tf'                => $this->tf,
            'botId'             => $this->botId,
            'botName'           => $this->botName,
            'botRef'            => $this->botRef,
            'sessionStart'      => null,
        ];
    }

    /** The chart reads only its dedicated market-data store. */
    private function loadAvailableSymbols(): array
    {
        if (! file_exists(self::MARKET_DATA_DB)) {
            return [];
        }
        try {
            $pdo = new PDO('sqlite:' . self::MARKET_DATA_DB);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $rows = $pdo->query(
                "SELECT exchange, symbol FROM instruments
                 WHERE active = 1 ORDER BY exchange, symbol"
            )->fetchAll(PDO::FETCH_ASSOC);
            $out = [];
            foreach ($rows as $row) {
                $exchange = strtolower((string) ($row['exchange'] ?? ''));
                $symbol = (string) ($row['symbol'] ?? '');
                if ($exchange !== '' && $symbol !== '') {
                    $out[$exchange][] = $symbol;
                }
            }
            return $out;
        } catch (\Throwable) {
            return [];
        }
    }
}
