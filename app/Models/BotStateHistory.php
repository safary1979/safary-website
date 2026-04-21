<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotStateHistory extends Model
{
    protected $connection = 'sqlite_bot';
    protected $table = 'bot_state_history';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = [
        'position_json' => 'array',
    ];
}
