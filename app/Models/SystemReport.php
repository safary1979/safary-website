<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemReport extends Model
{
    protected $fillable = ['created_by', 'status', 'gateway_generated_at', 'payload', 'published_at'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'gateway_generated_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
