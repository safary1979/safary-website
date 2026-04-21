<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotCommand extends Model
{
    protected $connection = 'sqlite_bot';
    protected $table = 'bot_commands';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = [
        'payload_json' => 'array',
        'issued_at' => 'datetime',
        'executed_at' => 'datetime',
    ];
}
