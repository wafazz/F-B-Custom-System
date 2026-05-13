<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Illuminate\Validation\Rule;

class EditProfile extends BaseEditProfile
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Basic information')
                    ->description('Update your personal details. Email is fixed for security and can only be changed by a super admin.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('phone')
                            ->label('Phone')
                            ->tel()
                            ->maxLength(20)
                            ->rule(Rule::unique('users', 'phone')->ignore($this->getUser()->getKey())),
                        DatePicker::make('date_of_birth')
                            ->label('Date of birth')
                            ->native(false)
                            ->maxDate(now()->subDay()),
                        Select::make('gender')
                            ->label('Gender')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                                'other' => 'Other',
                            ]),
                    ])
                    ->columns(2),

                Section::make('Address')
                    ->schema([
                        TextInput::make('address_line')
                            ->label('Address line')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('city')
                            ->label('City')
                            ->maxLength(80),
                        TextInput::make('postcode')
                            ->label('Postcode')
                            ->maxLength(10)
                            ->rule('regex:/^[0-9]{4,6}$/'),
                        TextInput::make('state')
                            ->label('State')
                            ->maxLength(60),
                    ])
                    ->columns(2),
            ]);
    }
}
