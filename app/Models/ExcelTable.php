<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExcelTable extends Model
{
    protected $fillable = [
        'created_by',
        'title',
        'source_filename',
        'sheet_name',
        'column_headers',
        'rows_json',
        'row_count',
    ];

    protected function casts(): array
    {
        return [
            'column_headers' => 'array',
            'rows_json' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
