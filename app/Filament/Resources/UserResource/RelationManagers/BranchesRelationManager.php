<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\Branch;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class BranchesRelationManager extends RelationManager
{
    protected static string $relationship = 'branches';

    protected static ?string $title = 'Branch Assignments';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('pin')
                ->label('POS PIN (4-6 digits)')
                ->password()
                ->revealable()
                ->numeric()
                ->minLength(4)
                ->maxLength(6)
                ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                ->dehydrated(fn ($state) => filled($state)),
            Forms\Components\Select::make('employment_type')
                ->options([
                    'full_time' => 'Full Time',
                    'part_time' => 'Part Time',
                    'contract' => 'Contract',
                ])
                ->default('full_time')
                ->required(),
            Forms\Components\DatePicker::make('hired_at')->default(now()),
            Forms\Components\DatePicker::make('ended_at'),
            Forms\Components\Toggle::make('is_active')->default(true),
            Forms\Components\Toggle::make('is_primary')
                ->helperText('Primary branch for this staff member.'),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('code')->badge()->color('primary'),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('pivot.employment_type')
                    ->label('Type')
                    ->badge(),
                Tables\Columns\TextColumn::make('pivot.hired_at')->date()->label('Hired'),
                Tables\Columns\IconColumn::make('pivot.is_active')->boolean()->label('Active'),
                Tables\Columns\IconColumn::make('pivot.is_primary')->boolean()->label('Primary'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action) => [
                        $action->getRecordSelect(),
                        Forms\Components\TextInput::make('pin')
                            ->password()
                            ->numeric()
                            ->minLength(4)
                            ->maxLength(6)
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null),
                        Forms\Components\Select::make('employment_type')
                            ->options([
                                'full_time' => 'Full Time',
                                'part_time' => 'Part Time',
                                'contract' => 'Contract',
                            ])
                            ->default('full_time')
                            ->required(),
                        Forms\Components\DatePicker::make('hired_at')->default(now()),
                        Forms\Components\Toggle::make('is_active')->default(true),
                        Forms\Components\Toggle::make('is_primary'),
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('resetPin')
                    ->label('Reset PIN')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Reset POS PIN')
                    ->modalDescription('Generates a new 6-digit PIN and shows it once.')
                    ->action(function (Branch $record) {
                        $owner = $this->ownerUser();
                        $newPin = (string) random_int(100000, 999999);
                        $owner->branches()->updateExistingPivot($record->getKey(), [
                            'pin' => Hash::make($newPin),
                        ]);

                        Notification::make()
                            ->title("New PIN for {$owner->name} @ {$record->name}")
                            ->body("PIN: {$newPin} — share securely. This will not be shown again.")
                            ->warning()
                            ->persistent()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }

    protected function ownerUser(): User
    {
        /** @var User $user */
        $user = $this->getOwnerRecord();

        return $user;
    }
}
