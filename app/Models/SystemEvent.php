<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemEvent extends Model
{
    protected $connection = 'sqlite_bot';
    protected $table = 'system_events';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = [
        'payload_json' => 'array',
        'ts' => 'datetime',
    ];
}
