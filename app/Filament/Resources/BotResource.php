<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BotResource\Pages;
use App\Models\ApiAccount;
use App\Models\Bot;
use App\Services\BotCommandService;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
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

    public static function canAccess(): bool
    {
        return app(\App\Services\TenantService::class)->canControl();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    // ── Helpers ──────────────────────────────────────────────

    public static function positionLabel(Bot $record): string
    {
        $pos = $record->state?->position_json;
        if (!$pos) return 'flat';
        $side = strtoupper($pos['side_str'] ?? '');
        $pnl  = (float) ($pos['pnl_pct'] ?? 0);
        $sign = $pnl >= 0 ? '+' : '';
        return "{$side} {$sign}" . number_format($pnl, 2) . '%';
    }

    public static function positionColor(Bot $record): string
    {
        $pos = $record->state?->position_json;
        if (!$pos) return 'gray';
        $side = strtolower($pos['side_str'] ?? '');
        $pnl  = (float) ($pos['pnl_pct'] ?? 0);
        if ($side === 'long')  return $pnl >= 0 ? 'success' : 'warning';
        if ($side === 'short') return $pnl >= 0 ? 'success' : 'warning';
        return 'gray';
    }

    // ── Form (view-only) ────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('slug')->disabled(),
            Forms\Components\TextInput::make('status')->disabled(),
        ]);
    }

    // ── Infolist (ViewBot page) ─────────────────────────────

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Бот')->schema([
                Grid::make(3)->schema([
                    TextEntry::make('slug')->label('Slug'),
                    TextEntry::make('pair')->label('Pair'),
                    TextEntry::make('exchange')->label('Exchange')->badge(),
                    TextEntry::make('direction')->label('Direction')->badge()
                        ->color(fn ($state) => $state === 'long' ? 'success' : 'danger'),
                    TextEntry::make('timeframe')->label('TF'),
                    TextEntry::make('algorithm')->label('Algo'),
                    TextEntry::make('account')->label('API key')->badge(),
                    TextEntry::make('status')->label('Статус процесу')->badge()
                        ->color(fn ($state) => match ($state) {
                            'running' => 'success', 'paused' => 'warning',
                            'stopped' => 'gray', 'error' => 'danger', default => 'gray',
                        }),
                    TextEntry::make('pid')->label('PID'),
                ]),
            ]),

            Section::make('Поточна позиція')->schema([
                Grid::make(4)->schema([
                    TextEntry::make('position_side')->label('Сторона')
                        ->getStateUsing(fn (Bot $r) => strtoupper($r->state?->position_json['side_str'] ?? '—'))
                        ->badge()
                        ->color(fn ($state) => match($state) {
                            'LONG' => 'success', 'SHORT' => 'danger', default => 'gray'
                        }),
                    TextEntry::make('position_pnl')->label('PnL %')
                        ->getStateUsing(function (Bot $r) {
                            $pos = $r->state?->position_json;
                            if (!$pos) return '—';
                            $pnl = (float)($pos['pnl_pct'] ?? 0);
                            return ($pnl >= 0 ? '+' : '') . number_format($pnl, 2) . '%';
                        })
                        ->color(fn ($state) => match(true) {
                            str_starts_with($state, '+') => 'success',
                            str_starts_with($state, '-') => 'danger',
                            default => 'gray',
                        }),
                    TextEntry::make('position_pnl_usdt')->label('PnL USDT')
                        ->getStateUsing(function (Bot $r) {
                            $pos = $r->state?->position_json;
                            if (!$pos) return '—';
                            $pnl = (float)($pos['pnl_usdt'] ?? 0);
                            return ($pnl >= 0 ? '+' : '') . number_format($pnl, 2) . ' USDT';
                        }),
                    TextEntry::make('position_entry')->label('Entry price')
                        ->getStateUsing(fn (Bot $r) => $r->state?->position_json
                            ? number_format((float)($r->state->position_json['avg_price'] ?? 0), 4)
                            : '—'),
                    TextEntry::make('position_qty')->label('Qty')
                        ->getStateUsing(fn (Bot $r) => $r->state?->position_json
                            ? ($r->state->position_json['qty'] ?? '—')
                            : '—'),
                    TextEntry::make('position_dca')->label('DCA count')
                        ->getStateUsing(fn (Bot $r) => $r->state?->position_json
                            ? ($r->state->position_json['dca_count'] ?? 0)
                            : '—'),
                    TextEntry::make('position_bars')->label('Bars held')
                        ->getStateUsing(fn (Bot $r) => $r->state?->position_json
                            ? ($r->state->position_json['bars_held'] ?? 0)
                            : '—'),
                    TextEntry::make('position_margin')->label('Margin USDT')
                        ->getStateUsing(fn (Bot $r) => $r->state?->position_json
                            ? number_format((float)($r->state->position_json['margin'] ?? 0), 2)
                            : '—'),
                ]),
            ]),

            Section::make('Стан бота')->schema([
                Grid::make(3)->schema([
                    TextEntry::make('state.equity')->label('Equity USDT')
                        ->getStateUsing(fn (Bot $r) => $r->state
                            ? number_format((float)$r->state->equity, 2) . ' USDT'
                            : '—'),
                    TextEntry::make('state.mark_price')->label('Mark price')
                        ->getStateUsing(fn (Bot $r) => $r->state
                            ? number_format((float)$r->state->mark_price, 4)
                            : '—'),
                    TextEntry::make('state.cycle_state')->label('Cycle state')->badge(),
                    TextEntry::make('state.bars_processed')->label('Bars processed')
                        ->getStateUsing(fn (Bot $r) => $r->state?->bars_processed ?? '—'),
                    TextEntry::make('state.trades_count')->label('Trades')
                        ->getStateUsing(fn (Bot $r) => $r->state?->trades_count ?? '—'),
                    TextEntry::make('state.updated_at')->label('Оновлено')
                        ->getStateUsing(fn (Bot $r) => $r->state?->updated_at ?? '—'),
                ]),
            ]),
        ]);
    }

    // ── Table ───────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('slug')->label('Назва')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('symbol')->label('Pair')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('exchange')->badge(),
                Tables\Columns\TextColumn::make('account')->label('API key')->badge()->toggleable(),
                Tables\Columns\TextColumn::make('direction')->badge()
                    ->color(fn ($state) => $state === 'long' ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('status')->label('Процес')->badge()
                    ->color(fn ($state) => match ($state) {
                        'running' => 'success',
                        'paused'  => 'warning',
                        'stopped' => 'gray',
                        'error'   => 'danger',
                        default   => 'gray',
                    }),
                Tables\Columns\TextColumn::make('position')->label('Позиція')
                    ->getStateUsing(fn (Bot $record) => static::positionLabel($record))
                    ->badge()
                    ->color(fn ($state, Bot $record) => static::positionColor($record)),
                Tables\Columns\TextColumn::make('updated_at')->label('Оновлено')
                    ->since()->sortable()->toggleable(),
            ])
            ->defaultSort('id')
            ->filters([
                Tables\Filters\SelectFilter::make('exchange')
                    ->options(fn () => Bot::query()->whereNotNull('exchange')->distinct()->pluck('exchange', 'exchange')->all()),
                Tables\Filters\SelectFilter::make('pair')
                    ->options(fn () => Bot::query()->whereNotNull('pair')->distinct()->pluck('pair', 'pair')->all()),
                Tables\Filters\SelectFilter::make('account')->label('API key')
                    ->options(fn () => Bot::query()->whereNotNull('account')->distinct()->pluck('account', 'account')->all()),
                Tables\Filters\SelectFilter::make('direction')
                    ->options(['long' => 'long', 'short' => 'short']),
                Tables\Filters\SelectFilter::make('status')
                    ->options(['running' => 'running', 'paused' => 'paused', 'stopped' => 'stopped', 'error' => 'error']),
                Tables\Filters\TernaryFilter::make('in_position')->label('В угоді')
                    ->trueLabel('Так')->falseLabel('Ні')
                    ->queries(
                        true:  fn ($q) => $q->whereHas('state', fn ($s) => $s->whereNotNull('position_json')),
                        false: fn ($q) => $q->where(fn ($q) =>
                            $q->whereHas('state', fn ($s) => $s->whereNull('position_json'))
                              ->orWhereDoesntHave('state')
                        ),
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('pause')->iconButton()
                    ->icon('heroicon-o-pause')->color('warning')
                    ->visible(fn (Bot $record) => $record->status === 'running')
                    ->requiresConfirmation()
                    ->action(fn (Bot $record) => static::command($record->id, 'pause')),
                Tables\Actions\Action::make('resume')->iconButton()
                    ->icon('heroicon-o-play')->color('success')
                    ->visible(fn (Bot $record) => $record->status === 'paused')
                    ->requiresConfirmation()
                    ->action(fn (Bot $record) => static::command($record->id, 'resume')),
                Tables\Actions\Action::make('start')->iconButton()
                    ->icon('heroicon-o-rocket-launch')->color('success')
                    ->visible(fn (Bot $record) => in_array($record->status, ['stopped', 'error', null], true))
                    ->form([
                        Select::make('account')->label('API key / акаунт')
                            ->options(fn () => ApiAccount::orderBy('name')
                                ->pluck('name', 'name')->toArray())
                            ->default(fn (Bot $record) => $record->account)
                            ->required(),
                    ])
                    ->action(fn (Bot $record, array $data) => static::command(
                        $record->id, 'start',
                        ['config_path' => $record->config_path, 'account' => $data['account']]
                    )),
                Tables\Actions\Action::make('stop')->iconButton()
                    ->icon('heroicon-o-stop')->color('danger')
                    ->visible(fn (Bot $record) => in_array($record->status, ['running', 'paused'], true))
                    ->requiresConfirmation()
                    ->modalDescription('Зупинити бота?')
                    ->action(fn (Bot $record) => static::command($record->id, 'stop')),
                Tables\Actions\ViewAction::make()->iconButton(),
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
