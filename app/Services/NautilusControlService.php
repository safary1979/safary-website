<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use RuntimeException;

class NautilusControlService
{
    private const SCHEMA = 1;
    private const INBOX = '/home/ubuntu/website/storage/app/nautilus-control/inbox';
    private const KEY_PATH = '/etc/nautilus-control.key';
    private const BOTS = ['FreqShort25', 'SolCusum45Short'];
    private const ACTIONS = ['start', 'stop', 'restart', 'status'];

    public function issue(string $botId, string $action): string
    {
        if (! in_array($botId, self::BOTS, true)) {
            throw new RuntimeException('Невідомий Nautilus-бот.');
        }
        if (! in_array($action, self::ACTIONS, true)) {
            throw new RuntimeException('Непідтримувана команда Nautilus.');
        }
        if (! is_dir(self::INBOX) || ! is_writable(self::INBOX)) {
            throw new RuntimeException('Контролер Nautilus недоступний.');
        }

        $requestId = $this->uuidV4();
        $document = [
            'schema' => self::SCHEMA,
            'request_id' => $requestId,
            'bot_id' => $botId,
            'action' => $action,
            'issued_at_utc' => now('UTC')->toIso8601String(),
            'issued_by' => Auth::user()?->email ?? 'website',
        ];
        $key = @file_get_contents(self::KEY_PATH);
        if (! is_string($key) || strlen(trim($key)) < 32) {
            throw new RuntimeException('Ключ контролера Nautilus недоступний.');
        }
        $canonical = implode("\n", [
            $document['schema'], $document['request_id'], $document['bot_id'],
            $document['action'], $document['issued_at_utc'], $document['issued_by'],
        ]);
        $document['signature'] = hash_hmac('sha256', $canonical, trim($key));
        $payload = json_encode($document, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $temporary = self::INBOX.'/.' . $requestId.'.'.getmypid().'.tmp';
        $destination = self::INBOX.'/'.$requestId.'.json';
        $handle = @fopen($temporary, 'x');
        if ($handle === false) {
            throw new RuntimeException('Не вдалося створити команду Nautilus.');
        }
        try {
            if (fwrite($handle, $payload) !== strlen($payload) || ! fflush($handle)) {
                throw new RuntimeException('Не вдалося записати команду Nautilus.');
            }
        } finally {
            fclose($handle);
        }
        if (! @chmod($temporary, 0644)) {
            @unlink($temporary);
            throw new RuntimeException('Не вдалося встановити права команди Nautilus.');
        }
        if (! @rename($temporary, $destination)) {
            @unlink($temporary);
            throw new RuntimeException('Не вдалося надіслати команду Nautilus.');
        }

        return $requestId;
    }

    private function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);
        return substr($hex, 0, 8).'-'.substr($hex, 8, 4).'-'.substr($hex, 12, 4).'-'.substr($hex, 16, 4).'-'.substr($hex, 20);
    }
}
