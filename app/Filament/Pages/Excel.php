<?php

namespace App\Filament\Pages;

use App\Models\ExcelTable;
use App\Services\ExcelTableImporter;
use App\Services\TenantService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\WithFileUploads;

class Excel extends Page
{
    use WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';
    protected static ?string $navigationLabel = 'Excel';
    protected static ?string $title = 'Excel';
    protected static ?string $slug = 'excel';
    protected static ?int $navigationSort = 32;
    protected static string $view = 'filament.pages.excel';

    public ?int $selectedTableId = null;
    public $upload = null;
    public string $tableTitle = '';

    public static function canAccess(): bool
    {
        $tenant = app(TenantService::class);

        return $tenant->canSwitch()
            && $tenant->viewingTenantId() === TenantService::PRIMARY_USER_ID;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function canManageTables(): bool
    {
        return (int) auth()->id() === TenantService::PRIMARY_USER_ID && static::canAccess();
    }

    public function mount(): void
    {
        $this->selectedTableId = ExcelTable::query()->latest('id')->value('id');
    }

    public function getViewData(): array
    {
        $tables = ExcelTable::query()
            ->select(['id', 'title', 'source_filename', 'sheet_name', 'row_count', 'created_at'])
            ->latest('id')
            ->get();

        $selectedId = $tables->firstWhere('id', $this->selectedTableId)?->id
            ?? $tables->first()?->id;
        $this->selectedTableId = $selectedId;
        $selectedTable = $selectedId
            ? ExcelTable::query()->find($selectedId)
            : null;

        if (! $selectedTable) {
            $this->selectedTableId = null;
        }

        return [
            'tables' => $tables,
            'selectedTable' => $selectedTable,
        ];
    }

    public function selectTable(int $id): void
    {
        abort_unless(static::canAccess(), 403);

        $this->selectedTableId = ExcelTable::query()->whereKey($id)->value('id');
        abort_if($this->selectedTableId === null, 404);
    }

    public function importTable(ExcelTableImporter $importer): void
    {
        abort_unless($this->canManageTables(), 403);
        $this->validate([
            'upload' => ['required', 'file', 'max:20480'],
            'tableTitle' => ['nullable', 'string', 'max:180'],
        ]);

        $originalName = $this->upload->getClientOriginalName();
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (! in_array($extension, ['xlsx', 'csv'], true)) {
            $this->addError('upload', 'Оберіть файл .xlsx або .csv.');
            return;
        }

        try {
            $parsed = $importer->parse($this->upload->getRealPath());
        } catch (\Throwable $exception) {
            $this->addError('upload', $exception->getMessage());
            return;
        }

        $title = trim($this->tableTitle)
            ?: pathinfo($originalName, PATHINFO_FILENAME);

        $table = ExcelTable::create([
            'created_by' => auth()->id(),
            'title' => mb_substr($title, 0, 180),
            'source_filename' => mb_substr($originalName, 0, 255),
            'sheet_name' => $parsed['sheet_name'],
            'column_headers' => $parsed['headers'],
            'rows_json' => $parsed['rows'],
            'row_count' => count($parsed['rows']),
        ]);

        $this->selectedTableId = $table->id;
        $this->upload = null;
        $this->tableTitle = '';
        $this->resetValidation();

        Notification::make()
            ->title('Таблицю додано')
            ->body($table->title . ' · ' . $table->row_count . ' рядків')
            ->success()
            ->send();
    }

    public function deleteTable(int $id): void
    {
        abort_unless($this->canManageTables(), 403);

        $table = ExcelTable::query()->findOrFail($id);
        $title = $table->title;
        $table->delete();

        if ($this->selectedTableId === $id) {
            $this->selectedTableId = ExcelTable::query()->latest('id')->value('id');
        }

        Notification::make()
            ->title('Таблицю видалено')
            ->body($title)
            ->success()
            ->send();
    }
}
