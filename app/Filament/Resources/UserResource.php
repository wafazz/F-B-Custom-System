<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers\BranchesRelationManager;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Profile')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                    Forms\Components\TextInput::make('phone')
                        ->tel()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(20),
                    Forms\Components\DatePicker::make('date_of_birth')
                        ->maxDate(now()->subYears(13)),
                    Forms\Components\Select::make('gender')
                        ->options([
                            'male' => 'Male',
                            'female' => 'Female',
                            'other' => 'Other',
                        ])
                        ->native(false),
                    Forms\Components\FileUpload::make('photo')
                        ->image()
                        ->directory('users/photos')
                        ->maxSize(1024),
                ])
                ->columns(2),

            Forms\Components\Section::make('Account')
                ->schema([
                    Forms\Components\TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->dehydrated(fn ($state) => filled($state))
                        ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                        ->required(fn (string $context) => $context === 'create')
                        ->confirmed()
                        ->minLength(8),
                    Forms\Components\TextInput::make('password_confirmation')
                        ->password()
                        ->revealable()
                        ->minLength(8)
                        ->dehydrated(false),
                    Forms\Components\Select::make('roles')
                        ->relationship(
                            'roles',
                            'name',
                            modifyQueryUsing: function (Builder $query) {
                                if (! auth()->user()?->hasRole('super_admin')) {
                                    $query->whereNotIn('name', ['super_admin', 'hq_admin']);
                                }
                            },
                        )
                        ->multiple()
                        ->preload()
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Consent & Locale')
                ->schema([
                    Forms\Components\Toggle::make('marketing_consent'),
                    Forms\Components\Toggle::make('whatsapp_consent')->default(true),
                    Forms\Components\Toggle::make('push_consent')->default(true),
                    Forms\Components\Select::make('locale')->options([
                        'en' => 'English',
                        'ms' => 'Bahasa Malaysia',
                    ])->default('en'),
                ])
                ->columns(4)
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name='.urlencode($record->name).'&background=7c4a1e&color=fff'),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('phone')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->separator(',')
                    ->color('primary'),
                Tables\Columns\TextColumn::make('branches_count')
                    ->counts('branches')
                    ->label('Branches')
                    ->badge(),
                Tables\Columns\TextColumn::make('referral_code')
                    ->badge()
                    ->color('warning')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->preload()
                    ->multiple(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('toggleBan')
                    ->label(fn (User $r) => $r->trashed() ? 'Unban' : 'Ban')
                    ->icon(fn (User $r) => $r->trashed() ? 'heroicon-o-lock-open' : 'heroicon-o-no-symbol')
                    ->color(fn (User $r) => $r->trashed() ? 'success' : 'danger')
                    ->requiresConfirmation()
                    ->action(fn (User $r) => $r->trashed() ? $r->restore() : $r->delete()),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            BranchesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);

        if (! auth()->user()?->hasRole('super_admin')) {
            $query->whereDoesntHave('roles', fn (Builder $q) => $q->where('name', 'super_admin'));
        }

        return $query;
    }
}
