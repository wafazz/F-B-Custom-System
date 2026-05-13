<?php

namespace App\Filament\Pages;

use Filament\Facades\Filament;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * @property Form $form
 */
class ChangePassword extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $slug = 'change-password';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.change-password';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Section::make('Change password')
                    ->description('Enter your current password, then choose a new one.')
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Current password')
                            ->password()
                            ->revealable(filament()->arePasswordsRevealable())
                            ->required()
                            ->currentPassword()
                            ->autocomplete('current-password'),
                        TextInput::make('password')
                            ->label('New password')
                            ->password()
                            ->revealable(filament()->arePasswordsRevealable())
                            ->required()
                            ->rule(Password::default())
                            ->autocomplete('new-password')
                            ->same('passwordConfirmation'),
                        TextInput::make('passwordConfirmation')
                            ->label('Confirm new password')
                            ->password()
                            ->revealable(filament()->arePasswordsRevealable())
                            ->required()
                            ->dehydrated(false)
                            ->autocomplete('new-password'),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $user = Filament::auth()->user();

        if ($user === null) {
            return;
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
        ])->save();

        if (request()->hasSession()) {
            request()->session()->put([
                'password_hash_' . Filament::getAuthGuard() => $user->getAuthPassword(),
            ]);
        }

        $this->form->fill();

        Notification::make()
            ->title('Password updated')
            ->success()
            ->send();
    }

    public function getTitle(): string
    {
        return 'Change password';
    }
}
