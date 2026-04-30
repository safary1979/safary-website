<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CandleController;

Route::get('/', function () {
    return view('welcome');
});

// Chart API — candle data (public market data, no auth needed)
Route::prefix('chart-api')->group(function () {
    Route::get('/candles', [CandleController::class, 'candles']);
    // Trades: only when authenticated (personal P&L data)
    Route::get('/trades', [CandleController::class, 'trades'])
        ->middleware(\Filament\Http\Middleware\Authenticate::class);
});
