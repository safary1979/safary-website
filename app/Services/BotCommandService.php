<?php

namespace App\Services;

use App\Models\BotCommand;
use Illuminate\Support\Facades\Auth;

class BotCommandService
{
    public function issue(int $botId, string $command, array $payload = []): BotCommand
    {
        return BotCommand::create([
            'bot_id' => $botId,
            'command' => $command,
            'payload_json' => $payload ?: null, // model casts to array → Eloquent json_encodes
            'status' => 'pending',
            'issued_by' => Auth::user()?->email ?? 'system',
            'issued_at' => now(),
        ]);
    }

    public function pause(int $botId): BotCommand    { return $this->issue($botId, 'pause'); }
    public function resume(int $botId): BotCommand   { return $this->issue($botId, 'resume'); }
    public function stop(int $botId): BotCommand     { return $this->issue($botId, 'stop'); }
    public function start(int $botId, ?string $configPath = null): BotCommand
    {
        return $this->issue($botId, 'start', $configPath ? ['config_path' => $configPath] : []);
    }

    public function closePosition(int $botId): BotCommand { return $this->issue($botId, 'close_position'); }
}
