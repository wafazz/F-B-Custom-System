<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use App\Models\WalletTopup;
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

    /** Create a Billplz bill for a wallet top-up (different redirect target). */
    public function createTopupBill(WalletTopup $topup, User $user): PaymentBill
    {
        if (! $this->apiKey || ! $this->collectionId) {
            throw new RuntimeException('Billplz is not configured: missing API key or collection ID.');
        }
        if (! $user->email && ! $user->phone) {
            throw new RuntimeException('Billplz requires either an email or a mobile number.');
        }

        $response = Http::withBasicAuth($this->apiKey, '')
            ->asForm()
            ->acceptJson()
            ->timeout(20)
            ->post($this->baseUrl().'/bills', array_filter([
                'collection_id' => $this->collectionId,
                'email' => $user->email ?: null,
                'mobile' => $user->email ? null : $user->phone,
                'name' => $user->name ?: 'Star Coffee Customer',
                'amount' => (int) round((float) $topup->amount * 100),
                'description' => "Star Coffee wallet top-up #{$topup->id}",
                'callback_url' => route('billplz.webhook'),
                'redirect_url' => route('wallet.topup-return', ['topup' => $topup]),
                'reference_1_label' => 'Topup',
                'reference_1' => 'WT-'.$topup->id,
            ], fn ($v) => $v !== null && $v !== ''));

        if (! $response->successful()) {
            Log::warning('Billplz createTopupBill failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'topup_id' => $topup->id,
            ]);
            throw new RuntimeException('Billplz refused the top-up bill creation.');
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
     * Billplz signing scheme.
     *
     * Webhook callbacks: top-level keys, sorted, concatenated as "{key}{value}"
     * joined by "|" — e.g. "amount200|idB1|paidtrue".
     *
     * Browser redirects: fields nested under `billplz[...]` are flattened to
     * "billplz{subkey}" before sorting — e.g. "billplzidB1|billplzpaidtrue".
     *
     * @param  array<string, mixed>  $payload
     */
    public function computeSignature(array $payload): string
    {
        unset($payload['x_signature']);

        if (isset($payload['billplz']) && is_array($payload['billplz'])) {
            $flat = [];
            foreach ($payload['billplz'] as $k => $v) {
                $flat['billplz'.$k] = $v;
            }
            $payload = $flat;
        }

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

    /**
     * Validate credentials by calling /check_balance. Returns the account
     * balance in sen on success; throws RuntimeException with a useful
     * message on any failure so the admin UI can surface it.
     */
    public function ping(): int
    {
        if (! $this->apiKey) {
            throw new RuntimeException('Billplz API key is not configured.');
        }

        $response = Http::withBasicAuth($this->apiKey, '')
            ->acceptJson()
            ->timeout(10)
            ->get($this->baseUrl().'/check_balance');

        if ($response->status() === 401) {
            throw new RuntimeException('Billplz rejected the API key (HTTP 401). Double-check the key matches your '.($this->sandbox ? 'sandbox' : 'live').' account.');
        }

        if (! $response->successful()) {
            throw new RuntimeException(sprintf('Billplz returned HTTP %d: %s', $response->status(), substr((string) $response->body(), 0, 200)));
        }

        $body = $response->json();
        if (! is_array($body) || ! array_key_exists('balance', $body)) {
            throw new RuntimeException('Billplz returned an unexpected response shape.');
        }

        return (int) $body['balance'];
    }

    /**
     * Verify the configured collection ID exists and is reachable with the
     * current API key. Returns the raw collection payload from Billplz.
     *
     * @return array<string, mixed>
     */
    public function verifyCollection(): array
    {
        if (! $this->apiKey || ! $this->collectionId) {
            throw new RuntimeException('Billplz API key or collection ID is not configured.');
        }

        $response = Http::withBasicAuth($this->apiKey, '')
            ->acceptJson()
            ->timeout(10)
            ->get($this->baseUrl().'/collections/'.$this->collectionId);

        if ($response->status() === 404) {
            throw new RuntimeException('Billplz collection ID not found. Check the value matches one in your dashboard.');
        }

        if (! $response->successful()) {
            throw new RuntimeException(sprintf('Billplz returned HTTP %d on collection lookup.', $response->status()));
        }

        return (array) $response->json();
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
