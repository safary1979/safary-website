<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Account slug, e.g. bybit-demo');
            $table->string('exchange')->default('bybit');
            $table->text('api_key_enc')->comment('Encrypted API key');
            $table->text('api_secret_enc')->comment('Encrypted API secret');
            $table->text('api_passphrase_enc')->nullable()->comment('Encrypted passphrase (OKX etc)');
            $table->boolean('is_demo')->default(false);
            $table->boolean('is_testnet')->default(false);
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_accounts');
    }
};
