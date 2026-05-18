<?php

namespace App\Filament\Pages;

use App\Services\Mail\BrevoMailer;
use App\Services\Settings\SettingsRepository;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * @property Form $form
 */
class BrevoSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Email (Brevo)';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.brevo-settings';

    /** @var array<string, mixed> */
    public array $data = [];

    public string $testEmail = '';

    public function mount(SettingsRepository $settings): void
    {
        $this->form->fill([
            'api_key' => $settings->get('brevo.api_key', (string) config('services.brevo.api_key')),
            'sender_email' => $settings->get('brevo.sender_email', (string) config('services.brevo.sender_email')),
            'sender_name' => $settings->get('brevo.sender_name', (string) config('services.brevo.sender_name')),
        ]);

        $user = auth()->user();
        $this->testEmail = $user !== null ? (string) $user->email : '';
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Section::make('Brevo API credentials')
                    ->description('Find your key at Brevo dashboard → SMTP & API → API keys. Format starts with xkeysib-.')
                    ->schema([
                        TextInput::make('api_key')
                            ->label('API key')
                            ->password()
                            ->revealable()
                            ->placeholder('xkeysib-...')
                            ->helperText('Stored encrypted in the settings table. Never exposed in the UI after save.')
                            ->required(),
                    ]),

                Section::make('Sender identity')
                    ->description('The "From" address customers see. The email must match a domain verified in Brevo or delivery will be blocked.')
                    ->schema([
                        TextInput::make('sender_email')
                            ->label('Sender email')
                            ->email()
                            ->placeholder('noreply@starcoffee.my')
                            ->required(),
                        TextInput::make('sender_name')
                            ->label('Sender name')
                            ->placeholder('Star Coffee')
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public function save(SettingsRepository $settings): void
    {
        $data = $this->form->getState();

        $settings->setMany([
            'brevo.api_key' => ['value' => $data['api_key'] ?? null, 'encrypted' => true],
            'brevo.sender_email' => ['value' => $data['sender_email'] ?? null],
            'brevo.sender_name' => ['value' => $data['sender_name'] ?? null],
        ]);

        config([
            'services.brevo.api_key' => $data['api_key'] ?? null,
            'services.brevo.sender_email' => $data['sender_email'] ?? null,
            'services.brevo.sender_name' => $data['sender_name'] ?? null,
        ]);

        Notification::make()
            ->title('Brevo settings saved')
            ->body('Transactional emails will use the new credentials immediately.')
            ->success()
            ->send();
    }

    public function sendTest(BrevoMailer $mailer): void
    {
        // Save first so the test uses the current form values, not stale config.
        $this->save(app(SettingsRepository::class));

        if (! filter_var($this->testEmail, FILTER_VALIDATE_EMAIL)) {
            Notification::make()
                ->title('Enter a valid recipient email')
                ->danger()
                ->send();

            return;
        }

        $html = '<p>Hello from Star Coffee 👋</p>'
            .'<p>This is a test message from your admin panel. If you can read this, your Brevo API key + sender domain are wired correctly.</p>'
            .'<p style="color:#92400e">— Star Coffee</p>';

        $result = $mailer->send($this->testEmail, 'Brevo test from Star Coffee', $html);

        if ($result['ok']) {
            Notification::make()
                ->title('Test email sent')
                ->body('Delivered to '.$this->testEmail.'. Check the inbox (and spam folder for first-time sends).'
                    .(! empty($result['message_id']) ? ' Message ID: '.$result['message_id'] : ''))
                ->success()
                ->send();

            return;
        }

        Notification::make()
            ->title('Brevo rejected the request (HTTP '.($result['status'] ?? '?').')')
            ->body($result['error'] ?? 'Unknown error. Check Brevo dashboard → Statistics → Logs for details.')
            ->danger()
            ->persistent()
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
