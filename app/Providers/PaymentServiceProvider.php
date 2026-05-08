<?php

namespace App\Providers;

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

        $this->app->bind(PaymentGateway::class, function () {
            return match ($this->resolveDriver()) {
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
