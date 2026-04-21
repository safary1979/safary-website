<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Bot extends Model
{
    protected $connection = 'sqlite_bot';
    protected $table = 'bots';
    protected $guarded = [];
    public $timestamps = false;

    public function state(): HasOne
    {
        return $this->hasOne(BotState::class, 'bot_id');
    }

    public function trades(): HasMany
    {
        return $this->hasMany(Trade::class, 'bot_id');
    }

    public function commands(): HasMany
    {
        return $this->hasMany(BotCommand::class, 'bot_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(SystemEvent::class, 'bot_id');
    }
}
