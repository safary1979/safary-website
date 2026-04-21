<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotState extends Model
{
    protected $connection = 'sqlite_bot';
    protected $table = 'bot_state';
    protected $primaryKey = 'bot_id';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = [
        'position_json' => 'array',
    ];
}
