<?php

namespace App\Filament\Pages;

use App\Models\Bot;
use App\Services\BotCommandService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class BotConfigs extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-folder-open';
    protected static ?string $navigationLabel = 'Конфіги ботів';
    protected static ?string $title = 'Конфіги ботів';
    protected static ?int $navigationSort = 20;
    protected static string $view = 'filament.pages.bot-configs';

    public ?string $selectedPath = null;
    public ?string $content = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Textarea::make('content')
                ->label('Вміст конфіга')
                ->rows(25)
                ->extraInputAttributes(['style' => 'font-family: monospace; font-size: 13px;']),
        ]);
    }

    public function getConfigTree(): array
    {
        $root = rtrim((string) env('BOT_CONFIGS_PATH', '/home/ubuntu/SafaryEngine/live/configs'), '/');
        if (!is_dir($root)) {
            return [];
        }
        $tree = [];
        foreach (glob($root . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $files = [];
            foreach (glob($dir . '/*.{yaml,yml,json,toml}', GLOB_BRACE) ?: [] as $file) {
                $files[] = ['name' => basename($file), 'path' => $file];
            }
            $tree[] = ['name' => basename($dir), 'path' => $dir, 'files' => $files];
        }
        return $tree;
    }

    public function openFile(string $path): void
    {
        $root = realpath((string) env('BOT_CONFIGS_PATH'));
        $real = realpath($path);
        if (!$root || !$real || !str_starts_with($real, $root)) {
            Notification::make()->title('Недозволений шлях')->danger()->send();
            return;
        }
        $this->selectedPath = $real;
        $this->content = file_get_contents($real) ?: '';
        $this->form->fill(['content' => $this->content]);
    }

    public function save(): void
    {
        if (!$this->selectedPath) {
            return;
        }
        $data = $this->form->getState();
        file_put_contents($this->selectedPath, $data['content']);
        Notification::make()->title('Збережено')->success()->send();
    }

    public function launchBot(): void
    {
        if (!$this->selectedPath) {
            Notification::make()->title('Оберіть конфіг')->warning()->send();
            return;
        }
        $bot = Bot::where('config_path', $this->selectedPath)->first();
        if (!$bot) {
            Notification::make()
                ->title('Бот з цим конфігом не зареєстрований у БД')
                ->body('Додай бота до таблиці bots з config_path=' . $this->selectedPath)
                ->warning()
                ->send();
            return;
        }
        app(BotCommandService::class)->start($bot->id, $this->selectedPath);
        Notification::make()
            ->title("Команда start надіслана боту #{$bot->id}")
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')->label('Зберегти')->icon('heroicon-o-document-check')
                ->color('primary')->action('save')
                ->visible(fn () => (bool) $this->selectedPath),
            Action::make('launch')->label('Запустити бота')->icon('heroicon-o-rocket-launch')
                ->color('success')->requiresConfirmation()->action('launchBot')
                ->visible(fn () => (bool) $this->selectedPath),
        ];
    }
}
