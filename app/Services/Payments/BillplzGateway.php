<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BillplzGateway implements PaymentGateway
{
    public const SANDBOX_URL = 'https://www.billplz-sandbox.com/api/v3';

    public const LIVE_URL = 'https://www.billplz.com/api/v3';

    public function __construct(
        protected ?string $apiKey,
        protected ?string $collectionId,
        protected ?string $signatureKey,
        protected bool $sandbox = true,
    ) {}

    public function createBill(Order $order): PaymentBill
    {
        if (! $this->apiKey || ! $this->collectionId) {
            throw new RuntimeException('Billplz is not configured: missing API key or collection ID.');
        }

        $snapshot = (array) ($order->customer_snapshot ?? []);
        $name = (string) ($snapshot['name'] ?? 'Star Coffee Customer');
        $email = (string) ($snapshot['email'] ?? '');
        $phone = (string) ($snapshot['phone'] ?? '');

        if ($email === '' && $phone === '') {
            throw new RuntimeException('Billplz requires either an email or a mobile number.');
        }

        $response = Http::withBasicAuth($this->apiKey, '')
            ->asForm()
            ->acceptJson()
            ->timeout(20)
            ->post($this->baseUrl().'/bills', array_filter([
                'collection_id' => $this->collectionId,
                'email' => $email !== '' ? $email : null,
                'mobile' => $email === '' ? $phone : null,
                'name' => $name,
                'amount' => (int) round((float) $order->total * 100), // sen
                'description' => "Star Coffee order {$order->number}",
                'callback_url' => route('billplz.webhook'),
                'redirect_url' => route('billplz.return', ['order' => $order]),
                'reference_1_label' => 'Order',
                'reference_1' => $order->number,
            ], fn ($v) => $v !== null && $v !== ''));

        if (! $response->successful()) {
            Log::warning('Billplz createBill failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'order_id' => $order->id,
            ]);
            throw new RuntimeException('Billplz refused the bill creation.');
        }

        $body = $response->json();
        if (! is_array($body) || empty($body['id']) || empty($body['url'])) {
            throw new RuntimeException('Billplz returned an unexpected response.');
        }

        return new PaymentBill(
            reference: (string) $body['id'],
            url: (string) $body['url'],
            method: 'billplz',
        );
    }

    public function verifyWebhook(array $payload, ?string $signature): ?PaymentBillUpdate
    {
        if (! $this->signatureKey) {
            return null;
        }

        // Billplz sends x_signature inside the payload for callbacks
        // and as a separate value for redirects. Accept either.
        $sig = $signature ?? (isset($payload['x_signature']) ? (string) $payload['x_signature'] : null);
        if (! $sig) {
            return null;
        }

        $expected = $this->computeSignature($payload);
        if (! hash_equals($expected, $sig)) {
            Log::warning('Billplz signature mismatch', ['payload' => $payload]);

            return null;
        }

        $reference = (string) ($payload['id'] ?? $payload['billplz']['id'] ?? '');
        if ($reference === '') {
            return null;
        }

        $paid = $this->coerceBool($payload['paid'] ?? $payload['billplz']['paid'] ?? false);
        $state = (string) ($payload['state'] ?? '');

        $status = match (true) {
            $paid => PaymentStatus::Paid,
            $state === 'overdue' || $state === 'unpaid' => PaymentStatus::Failed,
            default => PaymentStatus::Unpaid,
        };

        return new PaymentBillUpdate(reference: $reference, status: $status);
    }

    /**
     * Billplz signing scheme: take all top-level keys EXCEPT x_signature, sort
     * alphabetically, concatenate "{key}{value}" with "|" separators, then
     * HMAC-SHA256 with the X-Signature key.
     *
     * @param  array<string, mixed>  $payload
     */
    public function computeSignature(array $payload): string
    {
        unset($payload['x_signature']);
        ksort($payload);

        $parts = [];
        foreach ($payload as $key => $value) {
            if (is_array($value) || is_object($value)) {
                continue;
            }
            $parts[] = $key.($value === null ? '' : (is_bool($value) ? ($value ? 'true' : 'false') : (string) $value));
        }

        return hash_hmac('sha256', implode('|', $parts), (string) $this->signatureKey);
    }

    protected function baseUrl(): string
    {
        return $this->sandbox ? self::SANDBOX_URL : self::LIVE_URL;
    }

    protected function coerceBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        $s = strtolower((string) $value);

        return in_array($s, ['1', 'true', 'yes'], true);
    }
}
