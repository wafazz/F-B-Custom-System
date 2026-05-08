<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MembershipTierResource\Pages;
use App\Models\MembershipTier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MembershipTierResource extends Resource
{
    protected static ?string $model = MembershipTier::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationGroup = 'Loyalty';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('min_lifetime_spend')->numeric()->prefix('RM')->required(),
            Forms\Components\TextInput::make('earn_multiplier')->numeric()->step(0.01)->default(1.00)->required(),
            Forms\Components\TextInput::make('color')->placeholder('#cd7f32'),
            Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('min_lifetime_spend')->money('MYR')->sortable(),
                Tables\Columns\TextColumn::make('earn_multiplier')
                    ->formatStateUsing(fn ($state) => $state.'×')
                    ->sortable(),
                Tables\Columns\TextColumn::make('memberships_count')->counts('memberships')->label('Members')->badge(),
                Tables\Columns\TextColumn::make('sort_order')->sortable(),
            ])
            ->defaultSort('min_lifetime_spend')
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMembershipTiers::route('/'),
            'create' => Pages\CreateMembershipTier::route('/create'),
            'edit' => Pages\EditMembershipTier::route('/{record}/edit'),
        ];
    }
}
