<?php

namespace App\Services\Push;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoPushService
{
    protected const ENDPOINT = 'https://exp.host/--/api/v2/push/send';

    public function isEnabled(): bool
    {
        return (bool) config('services.expo.push_enabled', true);
    }

    /**
     * Send a push to all of a user's mobile (Expo) device tokens, gated by the
     * customer's push_consent toggle. Tokens Expo reports as unregistered are
     * pruned. Never throws — push is best-effort.
     *
     * @param  array<string, mixed>  $payload  ['title','body','url'?,'tag'?,'data'?,'channel_id'?]
     * @return array{sent: int, pruned: int, delivered: list<string>, failures: list<array{endpoint: string, reason: string, status: int|null}>}
     */
    public function sendToUser(int $userId, array $payload): array
    {
        $empty = ['sent' => 0, 'pruned' => 0, 'delivered' => [], 'failures' => []];

        if (! $this->isEnabled()) {
            return $empty;
        }

        $user = User::query()->find($userId);
        if ($user === null || ! (bool) $user->push_consent) {
            return $empty; // respect the customer's push toggle
        }

        $tokens = DeviceToken::query()
            ->where('user_id', $userId)
            ->where('scope', DeviceToken::SCOPE_CUSTOMER)
            ->whereNotNull('token')
            ->get();
        if ($tokens->isEmpty()) {
            return $empty;
        }

        $data = $this->buildData($payload);
        $messages = [];
        foreach ($tokens as $row) {
            if (! $this->looksLikeExpoToken((string) $row->token)) {
                continue;
            }
            $messages[] = [
                'to' => $row->token,
                'title' => (string) ($payload['title'] ?? 'Star Coffee'),
                'body' => (string) ($payload['body'] ?? ''),
                'sound' => 'default',
                'channelId' => (string) ($payload['channel_id'] ?? 'default'),
                'priority' => 'high',
                'data' => $data,
            ];
        }
        if ($messages === []) {
            return $empty;
        }

        $sent = 0;
        $pruned = 0;
        $delivered = [];
        $failures = [];

        // Expo accepts up to 100 messages per request.
        foreach (array_chunk($messages, 100) as $chunk) {
            try {
                $response = Http::asJson()->acceptJson()->timeout(15)->post(self::ENDPOINT, $chunk);
            } catch (\Throwable $e) {
                Log::warning('Expo push request failed', ['reason' => $e->getMessage()]);
                foreach ($chunk as $m) {
                    $failures[] = ['endpoint' => (string) $m['to'], 'reason' => $e->getMessage(), 'status' => null];
                }

                continue;
            }

            $status = $response->status();
            /** @var array<int, mixed> $tickets */
            $tickets = $response->json('data') ?? [];

            foreach ($chunk as $i => $m) {
                $token = (string) $m['to'];
                $ticket = $tickets[$i] ?? null;

                if (is_array($ticket) && ($ticket['status'] ?? null) === 'ok') {
                    $sent++;
                    $delivered[] = 'Mobile (…'.substr($token, -8).')';

                    continue;
                }

                $reason = is_array($ticket) ? (string) ($ticket['message'] ?? 'Unknown error') : 'No delivery ticket';
                $errorCode = is_array($ticket) ? ($ticket['details']['error'] ?? null) : null;

                if ($errorCode === 'DeviceNotRegistered') {
                    DeviceToken::query()->where('token', $token)->delete();
                    $pruned++;
                }

                Log::warning('Expo push delivery failed', ['token' => substr($token, -8), 'reason' => $reason, 'status' => $status]);
                $failures[] = ['endpoint' => $token, 'reason' => $reason, 'status' => $status];
            }
        }

        DeviceToken::query()
            ->whereIn('token', array_column($messages, 'to'))
            ->update(['last_seen_at' => now()]);

        return ['sent' => $sent, 'pruned' => $pruned, 'delivered' => $delivered, 'failures' => $failures];
    }

    /**
     * Map the shared web-push payload to Expo `data`, surfacing a relative
     * `path` (and `order_id` when present) so the app can deep-link the tap.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function buildData(array $payload): array
    {
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        $url = (string) ($payload['url'] ?? '');
        if ($url !== '') {
            $path = parse_url($url, PHP_URL_PATH);
            $path = is_string($path) && $path !== '' ? $path : $url;
            $data['path'] = $path;
            $data['url'] = $url;
            if (preg_match('#/orders/(\d+)#', $path, $matches) === 1) {
                $data['order_id'] = (int) $matches[1];
            }
        }

        if (isset($payload['tag'])) {
            $data['tag'] = (string) $payload['tag'];
        }

        return $data;
    }

    protected function looksLikeExpoToken(string $token): bool
    {
        return str_starts_with($token, 'ExponentPushToken[') || str_starts_with($token, 'ExpoPushToken[');
    }
}
