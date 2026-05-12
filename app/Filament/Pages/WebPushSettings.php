<?php

namespace App\Filament\Pages;

use App\Services\Settings\SettingsRepository;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Minishlink\WebPush\VAPID;
use Throwable;

/**
 * @property Form $form
 */
class WebPushSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Web Push';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.web-push-settings';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(SettingsRepository $settings): void
    {
        $this->form->fill([
            'subject' => $settings->get('webpush.subject', (string) config('services.webpush.subject')),
            'public_key' => $settings->get('webpush.public_key', (string) config('services.webpush.public_key')),
            'private_key' => $settings->get('webpush.private_key', (string) config('services.webpush.private_key')),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Section::make('VAPID identity')
                    ->description('The VAPID subject identifies who is sending notifications. Use a mailto: URL or an https: URL controlled by your team.')
                    ->schema([
                        TextInput::make('subject')
                            ->label('Subject')
                            ->placeholder('mailto:admin@starcoffee.my')
                            ->helperText('Browsers reject pushes with an invalid subject.')
                            ->required(),
                    ]),

                Section::make('VAPID keypair')
                    ->description('The public key is sent to browsers when they subscribe. The private key signs outgoing notifications and never leaves the server. Both are stored encrypted in the settings table.')
                    ->schema([
                        TextInput::make('public_key')
                            ->label('Public key')
                            ->placeholder('B...')
                            ->helperText('Base64url-encoded, ~88 characters. Safe to expose.')
                            ->required(),
                        Textarea::make('private_key')
                            ->label('Private key')
                            ->rows(2)
                            ->placeholder('...')
                            ->helperText('Base64url-encoded, ~43 characters. Never share.')
                            ->required(),
                    ])
                    ->columns(1),
            ]);
    }

    public function save(SettingsRepository $settings): void
    {
        $data = $this->form->getState();

        $settings->setMany([
            'webpush.subject' => ['value' => $data['subject'] ?? null],
            'webpush.public_key' => ['value' => $data['public_key'] ?? null],
            'webpush.private_key' => ['value' => $data['private_key'] ?? null, 'encrypted' => true],
        ]);

        config([
            'services.webpush.subject' => $data['subject'] ?? null,
            'services.webpush.public_key' => $data['public_key'] ?? null,
            'services.webpush.private_key' => $data['private_key'] ?? null,
        ]);

        Notification::make()
            ->title('Web Push settings saved')
            ->body('New subscriptions will use the updated public key immediately. Existing subscribers stay valid as long as the keypair did not change.')
            ->success()
            ->send();
    }

    public function generateKeys(): void
    {
        try {
            $keys = VAPID::createVapidKeys();
        } catch (Throwable $e) {
            Notification::make()
                ->title('Could not generate keys')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->data['public_key'] = $keys['publicKey'];
        $this->data['private_key'] = $keys['privateKey'];

        Notification::make()
            ->title('Fresh keypair generated')
            ->body('Review the values and click Save to apply. Rotating keys invalidates all existing push subscriptions.')
            ->warning()
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
