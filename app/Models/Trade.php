<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    protected $connection = 'sqlite_bot';
    protected $table = 'trades';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = [
        'raw_json' => 'array',
        'entry_time' => 'datetime',
        'exit_time' => 'datetime',
    ];

    public function bot()
    {
        return $this->belongsTo(\App\Models\Bot::class, 'bot_id');
    }
}
