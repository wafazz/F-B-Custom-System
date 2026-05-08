<?php

namespace App\Services\Push;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PushService
{
    /** @var array<string, mixed>|null */
    protected ?array $auth = null;

    public function isConfigured(): bool
    {
        $public = (string) config('services.webpush.public_key');
        $private = (string) config('services.webpush.private_key');

        return $public !== '' && $private !== '';
    }

    /**
     * Send a notification to all of a user's push subscriptions.
     * Dead/expired subscriptions are pruned.
     *
     * @param  array<string, mixed>  $payload
     */
    public function sendToUser(int $userId, array $payload): int
    {
        if (! $this->isConfigured()) {
            return 0;
        }

        $subscriptions = PushSubscription::query()->where('user_id', $userId)->get();
        if ($subscriptions->isEmpty()) {
            return 0;
        }

        $webPush = new WebPush(['VAPID' => $this->vapidConfig()]);
        $body = json_encode($payload, JSON_THROW_ON_ERROR);

        foreach ($subscriptions as $row) {
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $row->endpoint,
                    'publicKey' => $row->public_key,
                    'authToken' => $row->auth_token,
                    'contentEncoding' => $row->content_encoding,
                ]),
                $body,
            );
        }

        $sent = 0;
        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();
            if ($report->isSuccess()) {
                $sent++;
                PushSubscription::query()->where('endpoint', $endpoint)->update(['last_used_at' => now()]);

                continue;
            }
            // 410 Gone / 404 → drop the subscription
            if ($report->isSubscriptionExpired()) {
                PushSubscription::query()->where('endpoint', $endpoint)->delete();

                continue;
            }
            Log::warning('Push delivery failed', [
                'endpoint' => $endpoint,
                'reason' => $report->getReason(),
            ]);
        }

        return $sent;
    }

    /** @return array<string, string> */
    protected function vapidConfig(): array
    {
        return $this->auth ??= [
            'subject' => (string) config('services.webpush.subject'),
            'publicKey' => (string) config('services.webpush.public_key'),
            'privateKey' => (string) config('services.webpush.private_key'),
        ];
    }
}
