<?php

namespace App\Services\Push;

use App\Models\DeviceToken;
use Firebase\JWT\JWT;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends pushes via FCM HTTP v1. Uses a service-account JWT exchanged for a
 * short-lived OAuth2 access token (cached). Tokens that come back with
 * NOT_FOUND / UNREGISTERED are pruned from device_tokens.
 */
class FcmService
{
    private const ACCESS_TOKEN_CACHE_KEY = 'fcm:access_token';

    private const SEND_ENDPOINT = 'https://fcm.googleapis.com/v1/projects/%s/messages:send';

    public function isConfigured(): bool
    {
        $projectId = (string) config('services.fcm.project_id');
        $credentials = (string) config('services.fcm.credentials_path');

        return $projectId !== '' && $credentials !== '' && is_file($credentials);
    }

    /**
     * Send the same payload to every token. Returns counts for logging.
     *
     * @param  list<string>  $tokens
     * @param  array{title: string, body: string, data?: array<string, string>, channel_id?: string}  $payload
     * @return array{sent: int, pruned: int, failed: int}
     */
    public function sendToTokens(array $tokens, array $payload): array
    {
        if (! $this->isConfigured() || $tokens === []) {
            return ['sent' => 0, 'pruned' => 0, 'failed' => 0];
        }

        $accessToken = $this->accessToken();
        if ($accessToken === null) {
            return ['sent' => 0, 'pruned' => 0, 'failed' => count($tokens)];
        }

        $url = sprintf(self::SEND_ENDPOINT, config('services.fcm.project_id'));
        $sent = $pruned = $failed = 0;

        foreach (array_unique($tokens) as $token) {
            $message = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $payload['title'],
                        'body' => $payload['body'],
                    ],
                    'android' => [
                        'priority' => 'HIGH',
                        'notification' => [
                            'channel_id' => $payload['channel_id'] ?? 'orders',
                            'sound' => 'default',
                        ],
                    ],
                    'data' => $payload['data'] ?? [],
                ],
            ];

            try {
                $response = Http::withToken($accessToken)
                    ->acceptJson()
                    ->asJson()
                    ->timeout(8)
                    ->post($url, $message);

                if ($response->successful()) {
                    $sent++;
                    continue;
                }

                // 404 / 400 with errorCode UNREGISTERED → token is dead, prune it.
                $errorCode = (string) ($response->json('error.details.0.errorCode')
                    ?? $response->json('error.status') ?? '');
                if ($response->status() === 404 || $errorCode === 'UNREGISTERED') {
                    DeviceToken::query()->where('token', $token)->delete();
                    $pruned++;
                    continue;
                }

                $failed++;
                Log::warning('[fcm] send failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);
            } catch (RequestException $e) {
                $failed++;
                Log::warning('[fcm] http exception', ['error' => $e->getMessage()]);
            }
        }

        return compact('sent', 'pruned', 'failed');
    }

    private function accessToken(): ?string
    {
        $cached = Cache::get(self::ACCESS_TOKEN_CACHE_KEY);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $credentials = $this->loadCredentials();
        if ($credentials === null) {
            return null;
        }

        $now = time();
        $assertion = JWT::encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ], $credentials['private_key'], 'RS256');

        try {
            $response = Http::asForm()->timeout(8)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]);
        } catch (RequestException $e) {
            Log::error('[fcm] access token http error', ['error' => $e->getMessage()]);
            return null;
        }

        if (! $response->successful()) {
            Log::error('[fcm] access token rejected', ['body' => $response->body()]);
            return null;
        }

        $token = (string) $response->json('access_token');
        $expiresIn = (int) ($response->json('expires_in') ?? 3600);
        if ($token === '') {
            return null;
        }

        // Refresh 60 s before actual expiry so we never present a stale token.
        Cache::put(self::ACCESS_TOKEN_CACHE_KEY, $token, max(60, $expiresIn - 60));

        return $token;
    }

    /** @return array{client_email: string, private_key: string}|null */
    private function loadCredentials(): ?array
    {
        $path = (string) config('services.fcm.credentials_path');
        if (! is_file($path)) {
            return null;
        }

        $json = file_get_contents($path);
        if ($json === false) {
            return null;
        }

        $data = json_decode($json, true);
        if (! is_array($data) || empty($data['client_email']) || empty($data['private_key'])) {
            Log::error('[fcm] service account json missing client_email/private_key');
            return null;
        }

        return [
            'client_email' => (string) $data['client_email'],
            'private_key' => (string) $data['private_key'],
        ];
    }
}
