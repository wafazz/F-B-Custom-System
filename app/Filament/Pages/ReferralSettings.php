<?php

namespace App\Filament\Pages;

use App\Services\Settings\SettingsRepository;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
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
class ReferralSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Referral Program';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.referral-settings';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(SettingsRepository $settings): void
    {
        $this->form->fill([
            'enabled' => $settings->get('referral.enabled', '1') === '1',
            'referrer_points' => (int) $settings->get(
                'referral.referrer_points',
                (string) config('services.referral.referrer_bonus_points', 100),
            ),
            'referee_points' => (int) $settings->get(
                'referral.referee_points',
                (string) config('services.referral.referee_bonus_points', 100),
            ),
            'min_first_order_amount' => (float) $settings->get('referral.min_first_order_amount', '0'),
            'share_text' => $settings->get(
                'referral.share_text',
                "Join me on Star Coffee — use my code {code} to get {points} bonus points on your first order: {url}",
            ),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Section::make('Program status')
                    ->description('Toggle the referral program on or off. When off, new sign-ups can still enter a code but no bonuses are awarded.')
                    ->schema([
                        Toggle::make('enabled')
                            ->label('Referral program enabled')
                            ->helperText('Disable to pause the program without losing existing history or codes.')
                            ->default(true),
                    ]),

                Section::make('Reward amounts')
                    ->description('Points awarded when a referred customer completes their first paid order. Set 0 to skip either side.')
                    ->schema([
                        TextInput::make('referrer_points')
                            ->label('Reward for the referrer')
                            ->helperText('Points credited to the person who shared their code.')
                            ->numeric()
                            ->minValue(0)
                            ->step(1)
                            ->suffix('pts')
                            ->required(),
                        TextInput::make('referee_points')
                            ->label('Reward for the new register (referee)')
                            ->helperText('Welcome bonus credited to the new customer after their first paid order.')
                            ->numeric()
                            ->minValue(0)
                            ->step(1)
                            ->suffix('pts')
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Qualification')
                    ->description('Optional guardrail to stop tiny orders from triggering the bonus.')
                    ->schema([
                        TextInput::make('min_first_order_amount')
                            ->label('Minimum first-order total')
                            ->helperText('Referee\'s first paid order must be at least this amount before the bonus fires. Set 0 to allow any amount.')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->prefix('RM')
                            ->required(),
                    ]),

                Section::make('Share message')
                    ->description('The message customers send when they tap the Share button on the referral page. Keep it short and engaging — WhatsApp + native share sheets favour 1–2 sentences.')
                    ->schema([
                        Textarea::make('share_text')
                            ->label('Share text template')
                            ->rows(4)
                            ->helperText('Available placeholders: {code} (the referrer\'s code), {points} (the new-register bonus), {url} (the signup link with code attached).')
                            ->required(),
                    ]),
            ]);
    }

    public function save(SettingsRepository $settings): void
    {
        $data = $this->form->getState();

        $settings->setMany([
            'referral.enabled' => ['value' => ! empty($data['enabled']) ? '1' : '0'],
            'referral.referrer_points' => ['value' => (string) (int) ($data['referrer_points'] ?? 0)],
            'referral.referee_points' => ['value' => (string) (int) ($data['referee_points'] ?? 0)],
            'referral.min_first_order_amount' => ['value' => (string) (float) ($data['min_first_order_amount'] ?? 0)],
            'referral.share_text' => ['value' => (string) ($data['share_text'] ?? '')],
        ]);

        Notification::make()
            ->title('Referral settings saved')
            ->body('New referrals will use the updated values immediately. Already-awarded bonuses are not affected.')
            ->success()
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
