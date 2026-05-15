<?php

namespace App\Providers;

use App\Services\Payments\BillplzGateway;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\StubGateway;
use App\Services\Settings\SettingsRepository;
use Illuminate\Support\ServiceProvider;
use Throwable;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsRepository::class);

        // Concrete Billplz binding — always available regardless of the active
        // driver, because wallet top-ups and the webhook callback are
        // Billplz-only flows.
        $this->app->bind(BillplzGateway::class, function () {
            return new BillplzGateway(
                apiKey: (string) config('services.billplz.api_key'),
                collectionId: (string) config('services.billplz.collection_id'),
                signatureKey: (string) config('services.billplz.x_signature'),
                sandbox: (bool) config('services.billplz.sandbox', true),
            );
        });

        $this->app->bind(PaymentGateway::class, function ($app) {
            return match ($this->resolveDriver()) {
                'billplz' => $app->make(BillplzGateway::class),
                default => new StubGateway,
            };
        });
    }

    public function boot(): void
    {
        // Hydrate config('services.billplz.*') from the encrypted settings table
        // so the BillplzGateway (when ready) reads the latest credentials.
        try {
            /** @var SettingsRepository $repo */
            $repo = $this->app->make(SettingsRepository::class);
            config([
                'services.billplz.api_key' => $repo->get('billplz.api_key', config('services.billplz.api_key')),
                'services.billplz.collection_id' => $repo->get('billplz.collection_id', config('services.billplz.collection_id')),
                'services.billplz.x_signature' => $repo->get('billplz.x_signature', config('services.billplz.x_signature')),
                'services.billplz.sandbox' => ($repo->get('billplz.sandbox') ?? (config('services.billplz.sandbox') ? '1' : '0')) === '1',
                'services.webpush.subject' => $repo->get('webpush.subject', config('services.webpush.subject')),
                'services.webpush.public_key' => $repo->get('webpush.public_key', config('services.webpush.public_key')),
                'services.webpush.private_key' => $repo->get('webpush.private_key', config('services.webpush.private_key')),
            ]);
        } catch (Throwable) {
            // Settings table may not exist yet (during initial migrate); fall back to env.
        }
    }

    protected function resolveDriver(): string
    {
        try {
            /** @var SettingsRepository $repo */
            $repo = $this->app->make(SettingsRepository::class);
            $stored = $repo->get('payment.driver');
            if ($stored) {
                return $stored;
            }
        } catch (Throwable) {
        }

        return (string) config('services.payment.driver', 'stub');
    }
}
