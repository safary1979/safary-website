<?php

namespace App\Filament\Resources\BotResource\Pages;

use App\Filament\Resources\BotResource;
use Filament\Resources\Pages\ViewRecord;

class ViewBot extends ViewRecord
{
    protected static string $resource = BotResource::class;

    protected function resolveRecord($key): \App\Models\Bot
    {
        return \App\Models\Bot::with('state')->findOrFail($key);
    }
}
