<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ComboResource\Pages;
use App\Models\Branch;
use App\Models\Combo;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ComboResource extends Resource
{
    protected static ?string $model = Combo::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Combo')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(
                            fn ($state, callable $set) => $set('slug', Str::slug($state ?? '')),
                        ),
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                    Forms\Components\TextInput::make('price')
                        ->required()
                        ->numeric()
                        ->prefix('RM')
                        ->step(0.01)
                        ->helperText('The flat price customers pay for the whole combo.'),
                    Forms\Components\Select::make('status')
                        ->options(['active' => 'Active', 'hidden' => 'Hidden'])
                        ->default('active')
                        ->required(),
                    Forms\Components\TextInput::make('sort_order')
                        ->numeric()
                        ->default(0),
                    Forms\Components\Textarea::make('description')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(3),

            Forms\Components\Section::make('Image')
                ->schema([
                    Forms\Components\FileUpload::make('image')
                        ->image()
                        ->directory('combos')
                        ->maxSize(2048),
                ])
                ->collapsed(),

            Forms\Components\Section::make('Included products')
                ->description('Pick the products that come with this combo. Quantity defaults to 1.')
                ->schema([
                    Forms\Components\Repeater::make('products')
                        ->relationship('products')
                        ->schema([
                            Forms\Components\Select::make('product_id')
                                ->label('Product')
                                ->options(Product::query()->where('status', 'active')->orderBy('name')->pluck('name', 'id'))
                                ->required()
                                ->searchable(),
                            Forms\Components\TextInput::make('quantity')
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->required(),
                            Forms\Components\TextInput::make('sort_order')
                                ->numeric()
                                ->default(0),
                        ])
                        ->columns(3)
                        ->orderColumn('sort_order')
                        ->minItems(1)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Where it shows')
                ->schema([
                    Forms\Components\CheckboxList::make('branch_ids')
                        ->label('Available branches')
                        ->helperText('Leave empty to show this combo at every branch.')
                        ->options(fn () => Branch::query()->orderBy('name')->pluck('name', 'id'))
                        ->bulkToggleable()
                        ->columns(2),
                ])
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')->square(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('price')->money('MYR')->sortable(),
                Tables\Columns\TextColumn::make('products_count')
                    ->counts('products')
                    ->label('Items')
                    ->badge(),
                Tables\Columns\TextColumn::make('sort_order')->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => $state === 'active' ? 'success' : 'gray'),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['active' => 'Active', 'hidden' => 'Hidden']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCombos::route('/'),
            'create' => Pages\CreateCombo::route('/create'),
            'edit' => Pages\EditCombo::route('/{record}/edit'),
        ];
    }
}
