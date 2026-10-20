<?php

namespace App\Console\Commands;

use App\Models\ExcelTable;
use App\Services\ExcelTableImporter;
use Illuminate\Console\Command;

class ImportExcelTableCommand extends Command
{
    protected $signature = 'excel:import-table
                            {path : Path to an .xlsx or .csv file}
                            {--sheet= : Worksheet name for .xlsx files}
                            {--title= : Display title for the table}';

    protected $description = 'Import one worksheet or CSV file as a filterable Excel table';

    public function handle(ExcelTableImporter $importer): int
    {
        $path = realpath((string) $this->argument('path'));
        if ($path === false || ! is_file($path)) {
            $this->error('Файл не знайдено.');
            return self::FAILURE;
        }

        try {
            $parsed = $importer->parse($path, $this->option('sheet') ?: null);
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $title = trim((string) $this->option('title'))
            ?: pathinfo($path, PATHINFO_FILENAME);

        $table = ExcelTable::create([
            'title' => mb_substr($title, 0, 180),
            'source_filename' => basename($path),
            'sheet_name' => $parsed['sheet_name'],
            'column_headers' => $parsed['headers'],
            'rows_json' => $parsed['rows'],
            'row_count' => count($parsed['rows']),
        ]);

        $this->info(sprintf(
            'Imported table #%d: %s (%d rows, %d columns).',
            $table->id,
            $table->title,
            $table->row_count,
            count($parsed['headers']),
        ));

        return self::SUCCESS;
    }
}
