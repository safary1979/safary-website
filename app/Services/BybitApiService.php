<?php

namespace App\Services;

class BybitApiService
{
    public function testConnection(string $exchange, string $apiKey, string $apiSecret, bool $isDemo = false, ?string $passphrase = null): array
    {
        return match($exchange) {
            'okx'   => $this->testOkx($apiKey, $apiSecret, $passphrase),
            default => $this->testBybit($apiKey, $apiSecret, $isDemo),
        };
    }

    // ── Bybit ────────────────────────────────────────────────────────────────

    private function testBybit(string $apiKey, string $apiSecret, bool $isDemo): array
    {
        $baseUrl     = $isDemo ? 'https://api-demo.bybit.com' : 'https://api.bybit.com';
        $timestamp   = (int)(microtime(true) * 1000);
        $recvWindow  = 5000;
        $queryString = 'accountType=UNIFIED';

        $sign = hash_hmac('sha256', $timestamp . $apiKey . $recvWindow . $queryString, $apiSecret);

        $data = $this->get($baseUrl . '/v5/account/wallet-balance?' . $queryString, [
            'X-BAPI-API-KEY: '     . $apiKey,
            'X-BAPI-SIGN: '        . $sign,
            'X-BAPI-SIGN-TYPE: 2',
            'X-BAPI-TIMESTAMP: '   . $timestamp,
            'X-BAPI-RECV-WINDOW: ' . $recvWindow,
        ]);

        if (! $data['ok']) return $data;

        $body = $data['body'];

        if ($body['retCode'] !== 0) {
            $msg = match($body['retCode']) {
                10003 => 'Невірний API Key',
                10004 => 'Невірний підпис (перевір API Secret)',
                10005 => 'Заборонено: перевір дозволи ключа на Bybit',
                default => $body['retMsg'] ?? 'Помилка ' . $body['retCode'],
            };
            return ['success' => false, 'message' => $msg];
        }

        $usdt = null;
        foreach ($body['result']['list'][0]['coin'] ?? [] as $coin) {
            if ($coin['coin'] === 'USDT') { $usdt = (float)$coin['walletBalance']; break; }
        }

        return ['success' => true, 'usdt' => $usdt, 'is_demo' => $isDemo];
    }

    // ── OKX ─────────────────────────────────────────────────────────────────

    private function testOkx(string $apiKey, string $apiSecret, ?string $passphrase): array
    {
        if (! $passphrase) {
            return ['success' => false, 'message' => 'Для OKX обов\'язкова Passphrase'];
        }

        $timestamp = gmdate('Y-m-d\TH:i:s') . '.' . sprintf('%03d', (int)(microtime(true) * 1000) % 1000) . 'Z';
        $path      = '/api/v5/account/balance';
        $sign      = base64_encode(hash_hmac('sha256', $timestamp . 'GET' . $path, $apiSecret, true));

        $data = $this->get('https://www.okx.com' . $path, [
            'OK-ACCESS-KEY: '         . $apiKey,
            'OK-ACCESS-SIGN: '        . $sign,
            'OK-ACCESS-TIMESTAMP: '   . $timestamp,
            'OK-ACCESS-PASSPHRASE: '  . $passphrase,
        ]);

        if (! $data['ok']) return $data;

        $body = $data['body'];

        if (($body['code'] ?? '') !== '0') {
            $msg = match($body['code'] ?? '') {
                '50111' => 'Невірний API Key',
                '50112' => 'Невірний підпис (перевір API Secret)',
                '50113' => 'Невірна Passphrase',
                default => $body['msg'] ?? 'Помилка ' . ($body['code'] ?? '?'),
            };
            return ['success' => false, 'message' => $msg];
        }

        $usdt = null;
        foreach ($body['data'][0]['details'] ?? [] as $coin) {
            if ($coin['ccy'] === 'USDT') { $usdt = (float)$coin['cashBal']; break; }
        }

        return ['success' => true, 'usdt' => $usdt, 'is_demo' => false];
    }

    // ── HTTP helper ──────────────────────────────────────────────────────────

    private function get(string $url, array $headers): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => array_merge($headers, ['Content-Type: application/json']),
        ]);
        $response = curl_exec($ch);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) return ['ok' => false, 'success' => false, 'message' => 'Мережева помилка: ' . $curlErr];

        $body = json_decode($response, true);
        if (! $body)  return ['ok' => false, 'success' => false, 'message' => 'Некоректна відповідь від біржі'];

        return ['ok' => true, 'body' => $body];
    }
}
