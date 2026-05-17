<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HomeSlideResource\Pages;
use App\Models\Branch;
use App\Models\Category;
use App\Models\HomeSlide;
use App\Models\Product;
use App\Models\Voucher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HomeSlideResource extends Resource
{
    protected static ?string $model = HomeSlide::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?string $navigationLabel = 'Home Slider';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Slide content')
                ->schema([
                    Forms\Components\Select::make('placement')
                        ->label('Banner slot')
                        ->required()
                        ->default('hero')
                        ->options([
                            'hero' => 'Top — Hero carousel (above categories)',
                            'rewards' => 'Bottom — Rewards / promo carousel',
                        ])
                        ->helperText('Pick which storefront banner this slide belongs to. The two carousels rotate independently.'),
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(80)
                        ->placeholder('e.g. Try our new Pistachio Latte'),
                    Forms\Components\TextInput::make('subtitle')
                        ->maxLength(120)
                        ->placeholder('e.g. Available all day, only RM 17'),
                    Forms\Components\FileUpload::make('image')
                        ->image()
                        ->directory('home-slides')
                        ->disk('public')
                        ->visibility('public')
                        ->maxSize(4096)
                        ->imagePreviewHeight('200')
                        ->helperText('Recommended 1600×900 px (16:9). Crop before uploading. Stored under storage/app/public/home-slides.'),
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('cta_label')
                            ->label('Button label')
                            ->maxLength(30)
                            ->placeholder('Order now'),
                        Forms\Components\Select::make('cta_target')
                            ->label('Quick pick (auto-fills URL)')
                            ->options(fn () => self::ctaOptions())
                            ->searchable()
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(
                                fn ($state, callable $set) => $state ? $set('cta_url', $state) : null,
                            )
                            ->helperText('Search for a product, category, voucher or page. Or type a custom URL below.'),
                    ]),
                    Forms\Components\TextInput::make('cta_url')
                        ->label('Button link URL')
                        ->maxLength(255)
                        ->placeholder('/menu, menu?product=5, https://...'),
                ]),

            Forms\Components\Section::make('Where it shows')
                ->description('A slide can run on all branches or on specific ones only.')
                ->schema([
                    Forms\Components\Toggle::make('is_global')
                        ->label('Show on all branches')
                        ->default(true)
                        ->live()
                        ->helperText('Turn off to pick branches individually below.'),
                    Forms\Components\CheckboxList::make('branches')
                        ->relationship('branches', 'name')
                        ->options(fn () => Branch::query()->orderBy('name')->pluck('name', 'id'))
                        ->columns(2)
                        ->bulkToggleable()
                        ->visible(fn (Forms\Get $get): bool => ! $get('is_global'))
                        ->required(fn (Forms\Get $get): bool => ! $get('is_global'))
                        ->helperText('Tick every branch where this slide should appear.'),
                ]),

            Forms\Components\Section::make('Schedule & status')
                ->columns(2)
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                    Forms\Components\TextInput::make('sort_order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Lower numbers show first.'),
                    Forms\Components\DateTimePicker::make('starts_at')
                        ->label('Starts at')
                        ->helperText('Leave empty to start immediately.'),
                    Forms\Components\DateTimePicker::make('ends_at')
                        ->label('Ends at')
                        ->helperText('Leave empty for no end date.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->disk('public')
                    ->square()
                    ->size(48),
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('placement')
                    ->label('Slot')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'rewards' ? 'Bottom' : 'Top')
                    ->color(fn (string $state): string => $state === 'rewards' ? 'warning' : 'info'),
                Tables\Columns\TextColumn::make('subtitle')->limit(40)->toggleable(),
                Tables\Columns\IconColumn::make('is_global')
                    ->label('All branches')
                    ->boolean(),
                Tables\Columns\TextColumn::make('branches_count')
                    ->counts('branches')
                    ->label('Branches')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\IconColumn::make('is_active')->label('Active')->boolean(),
                Tables\Columns\TextColumn::make('sort_order')->label('Order')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('placement')
                    ->label('Slot')
                    ->options([
                        'hero' => 'Top — Hero',
                        'rewards' => 'Bottom — Rewards',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
                Tables\Filters\TernaryFilter::make('is_global')->label('Global'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHomeSlides::route('/'),
            'create' => Pages\CreateHomeSlide::route('/create'),
            'edit' => Pages\EditHomeSlide::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'hq_admin', 'mkt_manager']) ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'hq_admin', 'mkt_manager']) ?? false;
    }

    /** @return array<string, array<string, string>> */
    protected static function ctaOptions(): array
    {
        $pages = [
            'menu' => 'Menu — full catalog',
            'cart' => 'Cart',
            '/loyalty' => 'Loyalty & rewards',
            '/wallet' => 'Wallet',
            '/referral' => 'Referral',
            '/branches' => 'Branch picker',
            '/register' => 'Sign up — registration',
            '/login' => 'Sign in — login',
        ];

        $categories = Category::query()
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get(['slug', 'name'])
            ->mapWithKeys(fn (Category $c) => ["menu?category={$c->slug}" => "Category: {$c->name}"])
            ->all();

        $products = Product::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'base_price'])
            ->mapWithKeys(fn (Product $p) => [
                "menu?product={$p->id}" => sprintf('Product: %s — RM %.2f', $p->name, (float) $p->base_price),
            ])
            ->all();

        $vouchers = Voucher::query()
            ->where('status', 'active')
            ->orderBy('code')
            ->get(['code', 'name'])
            ->mapWithKeys(fn (Voucher $v) => ["/loyalty?voucher={$v->code}" => "Voucher: {$v->code} — {$v->name}"])
            ->all();

        return array_filter([
            'Pages' => $pages,
            'Categories' => $categories,
            'Products' => $products,
            'Vouchers' => $vouchers,
        ]);
    }
}
