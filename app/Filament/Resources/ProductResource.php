<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identity')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state ?? ''))),
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('sku')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->placeholder('SC-LAT'),
                    Forms\Components\Select::make('category_id')
                        ->relationship('category', 'name')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Forms\Components\Textarea::make('description')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Pricing & Tax')
                ->schema([
                    Forms\Components\TextInput::make('base_price')
                        ->required()
                        ->numeric()
                        ->prefix('RM')
                        ->step(0.01),
                    Forms\Components\Toggle::make('sst_applicable')->default(true),
                    Forms\Components\TextInput::make('calories')
                        ->numeric()
                        ->suffix('kcal'),
                    Forms\Components\TextInput::make('prep_time_minutes')
                        ->numeric()
                        ->default(5)
                        ->suffix('min'),
                ])
                ->columns(4),

            Forms\Components\Section::make('Visibility')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->options([
                            'active' => 'Active',
                            'hidden' => 'Hidden',
                            'discontinued' => 'Discontinued',
                        ])
                        ->required()
                        ->default('active'),
                    Forms\Components\Toggle::make('is_featured'),
                    Forms\Components\TextInput::make('sort_order')
                        ->numeric()
                        ->default(0),
                ])
                ->columns(3),

            Forms\Components\Section::make('Channel availability')
                ->description('Toggle which online channels this product appears on. POS walk-in always shows every product regardless of these flags.')
                ->schema([
                    Forms\Components\Toggle::make('available_web')
                        ->label('Web')
                        ->helperText('Browser ordering')
                        ->default(true)
                        ->inline(false),
                    Forms\Components\Toggle::make('available_pwa')
                        ->label('PWA')
                        ->helperText('Installed PWA')
                        ->default(true)
                        ->inline(false),
                    Forms\Components\Toggle::make('available_mobile')
                        ->label('Mobile app')
                        ->helperText('Native app (Phase 2)')
                        ->default(true)
                        ->inline(false),
                ])
                ->columns(3),

            Forms\Components\Section::make('Media')
                ->schema([
                    Forms\Components\FileUpload::make('image')
                        ->image()
                        ->directory('products/images')
                        ->maxSize(2048),
                    Forms\Components\FileUpload::make('gallery')
                        ->image()
                        ->multiple()
                        ->reorderable()
                        ->directory('products/gallery')
                        ->maxFiles(6)
                        ->maxSize(2048),
                ])
                ->columns(2)
                ->collapsed(),

            Forms\Components\Section::make('Modifier Groups')
                ->schema([
                    Forms\Components\Select::make('modifier_groups')
                        ->relationship('modifierGroups', 'name')
                        ->multiple()
                        ->preload()
                        ->helperText('Pick reusable groups (Size, Sugar, Milk, Add-ons). Order them by editing the pivot.'),
                ])
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')->square(),
                Tables\Columns\TextColumn::make('sku')->badge()->color('gray')->searchable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category.name')->badge()->color('primary')->sortable(),
                Tables\Columns\TextColumn::make('base_price')
                    ->money('MYR')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_featured')->boolean()->label('Feat.'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'active' => 'success',
                        'hidden' => 'gray',
                        'discontinued' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('branches_count')->counts('branches')->label('Branches')->badge(),
                Tables\Columns\TextColumn::make('sort_order')->sortable()->toggleable(),
            ])
            ->defaultSort('category_id')
            ->filters([
                Tables\Filters\SelectFilter::make('category')->relationship('category', 'name'),
                Tables\Filters\SelectFilter::make('status')->options([
                    'active' => 'Active',
                    'hidden' => 'Hidden',
                    'discontinued' => 'Discontinued',
                ]),
                Tables\Filters\TernaryFilter::make('is_featured'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('toggleFeatured')
                    ->label(fn (Product $r) => $r->is_featured ? 'Unfeature' : 'Feature')
                    ->icon('heroicon-o-star')
                    ->color(fn (Product $r) => $r->is_featured ? 'gray' : 'warning')
                    ->action(fn (Product $r) => $r->update(['is_featured' => ! $r->is_featured])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
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
            RelationManagers\BranchAvailabilityRelationManager::class,
            RelationManagers\StockRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
