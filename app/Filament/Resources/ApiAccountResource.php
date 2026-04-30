<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApiAccountResource\Pages;
use App\Models\ApiAccount;
use App\Services\BybitApiService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ApiAccountResource extends Resource
{
    protected static ?string $model = ApiAccount::class;
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationLabel = 'API Ключі';
    protected static ?string $modelLabel = 'Акаунт';
    protected static ?string $pluralModelLabel = 'API Акаунти';
    protected static ?int $navigationSort = 30;

    public static function canAccess(): bool
    {
        return app(\App\Services\TenantService::class)->canControl();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Акаунт')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Назва (slug)')->required()->unique(ignoreRecord: true)
                    ->placeholder('bybit-live')
                    ->helperText('Латиниця, цифри, дефіс. Використовується при запуску бота.'),
                Forms\Components\Select::make('exchange')
                    ->label('Біржа')->required()
                    ->options(['bybit' => 'Bybit', 'okx' => 'OKX', 'binance' => 'Binance'])
                    ->default('bybit')
                    ->live(),
                Forms\Components\TextInput::make('note')
                    ->label('Примітка')->placeholder('Мій live акаунт'),
                Forms\Components\Toggle::make('is_demo')
                    ->label('Demo Trading')
                    ->helperText('Увімкни, якщо ключі від демо-акаунту Bybit (api-demo.bybit.com)')
                    ->default(false)
                    ->inline(false)
                    ->live(),
            ])->columns(2),

            Forms\Components\Section::make('API Ключі')->schema([
                Forms\Components\TextInput::make('api_key')
                    ->label('API Key')
                    ->required(fn ($operation) => $operation === 'create')
                    ->password()->revealable()
                    ->placeholder(fn ($operation) => $operation === 'edit' ? '(не змінено)' : '')
                    ->helperText('Вводиш один раз — зберігається зашифрованим.'),
                Forms\Components\TextInput::make('api_secret')
                    ->label('API Secret')
                    ->required(fn ($operation) => $operation === 'create')
                    ->password()->revealable()
                    ->placeholder(fn ($operation) => $operation === 'edit' ? '(не змінено)' : ''),
                Forms\Components\TextInput::make('api_passphrase')
                    ->label('Passphrase')->password()->revealable()
                    ->placeholder('Тільки для OKX')
                    ->visible(fn (Forms\Get $get) => $get('exchange') === 'okx'),

                Forms\Components\Actions::make([
                    Forms\Components\Actions\Action::make('test_connection')
                        ->label('Перевірити з\'єднання')
                        ->icon('heroicon-o-signal')
                        ->color('gray')
                        ->action(function (Forms\Get $get, ?ApiAccount $record) {
                            $exchange  = $get('exchange') ?? 'bybit';
                            $apiKey    = $get('api_key')    ?: ($record?->api_key    ?? '');
                            $apiSecret = $get('api_secret') ?: ($record?->api_secret ?? '');
                            $isDemo    = (bool) $get('is_demo');

                            if (! $apiKey || ! $apiSecret) {
                                Notification::make()
                                    ->title('Введи API Key та API Secret')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            if ($exchange === 'binance') {
                                Notification::make()
                                    ->title('Перевірка недоступна')
                                    ->body('Перевірка з\'єднання для Binance ще не реалізована')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            $passphrase = $get('api_passphrase') ?: ($record?->api_passphrase ?? null);

                            $result = app(BybitApiService::class)
                                ->testConnection($exchange, $apiKey, $apiSecret, $isDemo, $passphrase);

                            if ($result['success']) {
                                $env  = $isDemo ? '🧪 Demo' : '🟢 Live';
                                $usdt = $result['usdt'] !== null
                                    ? number_format($result['usdt'], 2) . ' USDT'
                                    : 'баланс недоступний';

                                Notification::make()
                                    ->title('З\'єднання успішне')
                                    ->body("{$env} · Баланс: {$usdt}")
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Помилка з\'єднання')
                                    ->body($result['message'])
                                    ->danger()
                                    ->send();
                            }
                        }),
                ]),
            ])->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Назва')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('exchange')->label('Біржа')->badge()
                    ->color(fn ($state) => match($state) {
                        'bybit'   => 'warning',
                        'okx'     => 'info',
                        'binance' => 'success',
                        default   => 'gray',
                    }),
                Tables\Columns\TextColumn::make('api_key_masked')
                    ->label('API Key')
                    ->getStateUsing(fn (ApiAccount $r) => $r->maskedKey()),
                Tables\Columns\IconColumn::make('is_demo')->label('Demo')->boolean(),
                Tables\Columns\TextColumn::make('note')->label('Примітка')->placeholder('—'),
                Tables\Columns\TextColumn::make('updated_at')->label('Оновлено')->since()->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()->iconButton(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListApiAccounts::route('/'),
            'create' => Pages\CreateApiAccount::route('/create'),
            'edit'   => Pages\EditApiAccount::route('/{record}/edit'),
        ];
    }
}
