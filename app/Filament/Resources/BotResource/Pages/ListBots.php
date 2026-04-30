<?php

namespace App\Filament\Resources\BotResource\Pages;

use App\Filament\Resources\BotResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

class ListBots extends ListRecords
{
    protected static string $resource = BotResource::class;

    public ?string $pollInterval = null; // null=off, '5s', '15s'

    public function table(Table $table): Table
    {
        return parent::table($table)->poll($this->pollInterval);
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getTableQuery()->with('state');
    }

    public function setPollInterval(?string $v): void
    {
        $this->pollInterval = in_array($v, ['5s', '15s'], true) ? $v : null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('update')
                ->label('Update')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => null), // Livewire re-renders the page → table refetches

            Actions\ActionGroup::make([
                Actions\Action::make('off')->label('Off')
                    ->icon($this->pollInterval === null ? 'heroicon-m-check' : '')
                    ->action(fn () => $this->setPollInterval(null)),
                Actions\Action::make('5s')->label('5 sec')
                    ->icon($this->pollInterval === '5s' ? 'heroicon-m-check' : '')
                    ->action(fn () => $this->setPollInterval('5s')),
                Actions\Action::make('15s')->label('15 sec')
                    ->icon($this->pollInterval === '15s' ? 'heroicon-m-check' : '')
                    ->action(fn () => $this->setPollInterval('15s')),
            ])
                ->label(fn () => $this->pollInterval ?? 'Off')
                ->icon('heroicon-m-chevron-down')
                ->button()
                ->color('gray'),
        ];
    }
}
