<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BotResource\Pages;
use App\Models\Bot;
use App\Services\BotCommandService;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class BotResource extends Resource
{
    protected static ?string $model = Bot::class;
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'Всі боти';
    protected static ?string $modelLabel = 'Бот';
    protected static ?string $pluralModelLabel = 'Боти';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('slug')->disabled(),
            Forms\Components\TextInput::make('symbol')->disabled(),
            Forms\Components\TextInput::make('pair')->disabled(),
            Forms\Components\TextInput::make('exchange')->disabled(),
            Forms\Components\TextInput::make('direction')->disabled(),
            Forms\Components\TextInput::make('timeframe')->disabled(),
            Forms\Components\TextInput::make('algorithm')->disabled(),
            Forms\Components\TextInput::make('config_path')->disabled(),
            Forms\Components\TextInput::make('log_path')->disabled(),
            Forms\Components\TextInput::make('status')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('slug')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('symbol')->searchable(),
                Tables\Columns\TextColumn::make('pair')->searchable(),
                Tables\Columns\TextColumn::make('exchange')->badge(),
                Tables\Columns\TextColumn::make('direction')->badge()
                    ->color(fn ($state) => $state === 'long' ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('timeframe'),
                Tables\Columns\TextColumn::make('algorithm'),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn ($state) => match ($state) {
                        'running' => 'success',
                        'paused'  => 'warning',
                        'stopped' => 'gray',
                        'error'   => 'danger',
                        default   => 'secondary',
                    }),
                Tables\Columns\TextColumn::make('pid'),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->defaultSort('id')
            ->actions([
                Tables\Actions\Action::make('pause')
                    ->icon('heroicon-o-pause')->color('warning')
                    ->visible(fn (Bot $record) => $record->status === 'running')
                    ->requiresConfirmation()
                    ->action(fn (Bot $record) => static::command($record->id, 'pause')),
                Tables\Actions\Action::make('resume')
                    ->icon('heroicon-o-play')->color('success')
                    ->visible(fn (Bot $record) => $record->status === 'paused')
                    ->requiresConfirmation()
                    ->action(fn (Bot $record) => static::command($record->id, 'resume')),
                Tables\Actions\Action::make('start')
                    ->icon('heroicon-o-rocket-launch')->color('success')
                    ->visible(fn (Bot $record) => in_array($record->status, ['stopped', 'error', null], true))
                    ->requiresConfirmation()
                    ->action(fn (Bot $record) => static::command($record->id, 'start', ['config_path' => $record->config_path])),
                Tables\Actions\Action::make('stop')
                    ->icon('heroicon-o-stop')->color('danger')
                    ->visible(fn (Bot $record) => in_array($record->status, ['running', 'paused'], true))
                    ->requiresConfirmation()
                    ->modalDescription('Зупинити бота?')
                    ->action(fn (Bot $record) => static::command($record->id, 'stop')),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    protected static function command(int $botId, string $cmd, array $payload = []): void
    {
        app(BotCommandService::class)->issue($botId, $cmd, $payload);
        Notification::make()
            ->title("Команда «{$cmd}» надіслана боту #{$botId}")
            ->success()
            ->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBots::route('/'),
            'view'  => Pages\ViewBot::route('/{record}'),
        ];
    }

    public static function canCreate(): bool { return false; }
}
