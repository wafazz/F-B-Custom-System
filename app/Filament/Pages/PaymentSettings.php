<?php

namespace App\Filament\Pages;

use App\Services\Settings\SettingsRepository;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * @property Form $form
 */
class PaymentSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Payment Gateway';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.payment-settings';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(SettingsRepository $settings): void
    {
        $this->form->fill([
            'driver' => $settings->get('payment.driver', config('services.payment.driver', 'stub')),
            'sandbox' => $settings->get('billplz.sandbox', '1') === '1',
            'api_key' => $settings->get('billplz.api_key'),
            'collection_id' => $settings->get('billplz.collection_id'),
            'x_signature' => $settings->get('billplz.x_signature'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Section::make('Driver')
                    ->description('Stub returns a dev URL that auto-marks the order paid. Switch to Billplz once your credentials are entered below and validated.')
                    ->schema([
                        Select::make('driver')
                            ->options([
                                'stub' => 'Stub (development only)',
                                'billplz' => 'Billplz',
                            ])
                            ->required()
                            ->native(false),
                    ]),

                Section::make('Billplz credentials')
                    ->description('Stored encrypted in the settings table. Get these from your Billplz dashboard.')
                    ->schema([
                        Toggle::make('sandbox')
                            ->label('Sandbox mode')
                            ->default(true)
                            ->helperText('Use https://www.billplz-sandbox.com endpoints. Switch off for live transactions only after testing.'),
                        TextInput::make('api_key')
                            ->label('API key')
                            ->password()
                            ->revealable()
                            ->autocomplete('off')
                            ->placeholder('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx'),
                        TextInput::make('collection_id')
                            ->label('Collection ID')
                            ->placeholder('e.g. abc12345'),
                        TextInput::make('x_signature')
                            ->label('X-Signature key')
                            ->password()
                            ->revealable()
                            ->autocomplete('off')
                            ->helperText('Used to verify Billplz webhook callbacks.'),
                    ])
                    ->columns(1),
            ]);
    }

    public function save(SettingsRepository $settings): void
    {
        $data = $this->form->getState();

        $settings->setMany([
            'payment.driver' => ['value' => $data['driver'] ?? 'stub'],
            'billplz.sandbox' => ['value' => ($data['sandbox'] ?? true) ? '1' : '0'],
            'billplz.api_key' => ['value' => $data['api_key'] ?? null, 'encrypted' => true],
            'billplz.collection_id' => ['value' => $data['collection_id'] ?? null],
            'billplz.x_signature' => ['value' => $data['x_signature'] ?? null, 'encrypted' => true],
        ]);

        Notification::make()
            ->title('Payment settings saved')
            ->success()
            ->send();
    }

    public function testConnection(SettingsRepository $settings): void
    {
        $apiKey = $settings->get('billplz.api_key');
        $collectionId = $settings->get('billplz.collection_id');

        if (! $apiKey || ! $collectionId) {
            Notification::make()
                ->title('Missing credentials')
                ->body('Save your API key + Collection ID first.')
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Saved — live test stubbed')
            ->body('Billplz client adapter not yet implemented. Once `BillplzGateway::ping()` is wired this button will hit /api/v3/check_balance and report the response.')
            ->info()
            ->send();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'hq_admin']) ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'hq_admin']) ?? false;
    }
}
