<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ModifierGroupResource\Pages;
use App\Filament\Resources\ModifierGroupResource\RelationManagers;
use App\Models\ModifierGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ModifierGroupResource extends Resource
{
    protected static ?string $model = ModifierGroup::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state ?? ''))),
            Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true),
            Forms\Components\Select::make('selection_type')
                ->options(['single' => 'Single', 'multiple' => 'Multiple'])
                ->required()
                ->default('single'),
            Forms\Components\Toggle::make('is_required'),
            Forms\Components\TextInput::make('min_select')->numeric()->default(0),
            Forms\Components\TextInput::make('max_select')->numeric()->default(1),
            Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('selection_type')->badge(),
                Tables\Columns\IconColumn::make('is_required')->boolean(),
                Tables\Columns\TextColumn::make('options_count')->counts('options')->label('Options')->badge(),
                Tables\Columns\TextColumn::make('products_count')->counts('products')->label('Products')->badge(),
                Tables\Columns\TextColumn::make('sort_order')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\OptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListModifierGroups::route('/'),
            'create' => Pages\CreateModifierGroup::route('/create'),
            'edit' => Pages\EditModifierGroup::route('/{record}/edit'),
        ];
    }
}
