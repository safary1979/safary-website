<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ApiAccount extends Model
{
    protected $fillable = [
        'name', 'exchange',
        'api_key_enc', 'api_secret_enc', 'api_passphrase_enc',
        'api_key', 'api_secret', 'api_passphrase',  // virtual — trigger mutators
        'is_demo', 'is_testnet', 'note',
    ];

    protected $casts = [
        'is_demo'    => 'boolean',
        'is_testnet' => 'boolean',
    ];

    // ── Accessors (decrypt on read) ──────────────────────

    public function getApiKeyAttribute(): string
    {
        return $this->api_key_enc ? Crypt::decryptString($this->api_key_enc) : '';
    }

    public function getApiSecretAttribute(): string
    {
        return $this->api_secret_enc ? Crypt::decryptString($this->api_secret_enc) : '';
    }

    public function getApiPassphraseAttribute(): ?string
    {
        return $this->api_passphrase_enc ? Crypt::decryptString($this->api_passphrase_enc) : null;
    }

    // ── Mutators (encrypt on write) ──────────────────────

    public function setApiKeyAttribute(string $value): void
    {
        if ($value !== '') {
            $this->attributes['api_key_enc'] = Crypt::encryptString($value);
        }
    }

    public function setApiSecretAttribute(string $value): void
    {
        if ($value !== '') {
            $this->attributes['api_secret_enc'] = Crypt::encryptString($value);
        }
    }

    public function setApiPassphraseAttribute(?string $value): void
    {
        $this->attributes['api_passphrase_enc'] = $value ? Crypt::encryptString($value) : null;
    }

    /** Masked key for display, e.g. sk-****AbCd */
    public function maskedKey(): string
    {
        $k = $this->api_key;
        return strlen($k) > 6 ? substr($k, 0, 4) . '****' . substr($k, -4) : '****';
    }
}
