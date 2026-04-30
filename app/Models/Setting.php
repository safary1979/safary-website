<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['tenant_id', 'key', 'value'];

    public static function get(int $tenantId, string $key): ?string
    {
        return static::where('tenant_id', $tenantId)->where('key', $key)->value('value');
    }

    public static function set(int $tenantId, string $key, ?string $value): void
    {
        static::updateOrCreate(
            ['tenant_id' => $tenantId, 'key' => $key],
            ['value' => $value]
        );
    }
}
