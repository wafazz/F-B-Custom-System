<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchDisplayTokenResource\Pages;
use App\Models\BranchDisplayToken;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class BranchDisplayTokenResource extends Resource
{
    protected static ?string $model = BranchDisplayToken::class;

    protected static ?string $navigationIcon = 'heroicon-o-tv';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'TV Display Token';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('branch_id')
                ->relationship('branch', 'name')
                ->required()
                ->searchable(),
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(60)
                ->placeholder('Counter 1 / Pickup Area'),
            Forms\Components\Toggle::make('is_active')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('branch.code')->badge()->color('primary'),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('token')
                    ->copyable()
                    ->copyMessage('Token copied')
                    ->limit(16)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
                Tables\Columns\TextColumn::make('last_seen_at')->dateTime()->since()->placeholder('—'),
            ])
            ->actions([
                Tables\Actions\Action::make('copyUrl')
                    ->label('Copy URL')
                    ->icon('heroicon-o-link')
                    ->color('info')
                    ->action(fn (BranchDisplayToken $r) => Notification::make()
                        ->title('URL ready to copy')
                        ->body(route('display.show', ['branch' => $r->branch_id]).'?token='.$r->token)
                        ->persistent()
                        ->send()),
                Tables\Actions\Action::make('regenerate')
                    ->label('Regenerate')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (BranchDisplayToken $r) {
                        $r->update(['token' => Str::random(48)]);
                        Notification::make()->title('Token regenerated — old URL revoked')->success()->send();
                    }),
                Tables\Actions\Action::make('toggleActive')
                    ->label(fn (BranchDisplayToken $r) => $r->is_active ? 'Disable' : 'Enable')
                    ->icon('heroicon-o-power')
                    ->color(fn (BranchDisplayToken $r) => $r->is_active ? 'danger' : 'success')
                    ->action(fn (BranchDisplayToken $r) => $r->update(['is_active' => ! $r->is_active])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBranchDisplayTokens::route('/'),
            'create' => Pages\CreateBranchDisplayToken::route('/create'),
            'edit' => Pages\EditBranchDisplayToken::route('/{record}/edit'),
        ];
    }
}
