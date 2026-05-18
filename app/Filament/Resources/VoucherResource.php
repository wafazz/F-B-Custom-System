<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VoucherResource\Pages;
use App\Models\Branch;
use App\Models\Combo;
use App\Models\MembershipTier;
use App\Models\Product;
use App\Models\Voucher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class VoucherResource extends Resource
{
    protected static ?string $model = Voucher::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Loyalty';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identity')
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(40)
                        ->default(fn () => 'STAR-'.strtoupper(Str::random(6)))
                        ->afterStateUpdated(fn ($state, callable $set) => $set('code', strtoupper((string) $state))),
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\Textarea::make('description')->columnSpanFull()->rows(2),
                    Forms\Components\FileUpload::make('banner_image')
                        ->label('Banner image')
                        ->image()
                        ->imageEditor()
                        ->imageResizeMode('cover')
                        ->imageCropAspectRatio('16:9')
                        ->directory('vouchers/banners')
                        ->maxSize(2048)
                        ->helperText('Shown above the voucher card on the storefront. 16:9 works best (e.g. 1280×720). Max 2 MB.')
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Discount')
                ->schema([
                    Forms\Components\Select::make('discount_type')
                        ->options(['percentage' => 'Percentage (%)', 'fixed' => 'Fixed (RM)'])
                        ->default('percentage')
                        ->required(),
                    Forms\Components\TextInput::make('discount_value')->numeric()->required()->step(0.01),
                    Forms\Components\TextInput::make('min_subtotal')->numeric()->prefix('RM')->default(0),
                    Forms\Components\TextInput::make('max_discount')->numeric()->prefix('RM')->helperText('Cap for percentage vouchers'),
                ])
                ->columns(2),

            Forms\Components\Section::make('Limits & Validity')
                ->schema([
                    Forms\Components\TextInput::make('max_uses')->numeric()->helperText('Total uses (leave blank for unlimited)'),
                    Forms\Components\TextInput::make('max_uses_per_user')->numeric()->default(1),
                    Forms\Components\DateTimePicker::make('valid_from'),
                    Forms\Components\DateTimePicker::make('valid_until'),
                    Forms\Components\Select::make('branch_ids')
                        ->multiple()
                        ->relationship('redemptions.order.branch', 'name')
                        ->options(Branch::pluck('name', 'id'))
                        ->helperText('Leave empty for all branches'),
                    Forms\Components\Select::make('status')
                        ->options(['active' => 'Active', 'paused' => 'Paused', 'expired' => 'Expired'])
                        ->default('active')
                        ->required(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Eligibility')
                ->description('Restrict who can claim this voucher. Leave a field empty to allow everyone on that dimension.')
                ->schema([
                    Forms\Components\Toggle::make('new_users_only')
                        ->label('New customers only')
                        ->helperText('Only customers who have not placed any orders yet can claim. Great for welcome offers.')
                        ->columnSpanFull(),
                    Forms\Components\Select::make('tier_ids')
                        ->label('Member tiers')
                        ->multiple()
                        ->preload()
                        ->options(fn () => MembershipTier::query()->orderBy('min_lifetime_spend')->pluck('name', 'id')->all())
                        ->helperText('Only members of these tiers can claim this voucher.'),
                    Forms\Components\Select::make('birthday_months')
                        ->label('Birthday months')
                        ->multiple()
                        ->options([
                            1 => 'January',
                            2 => 'February',
                            3 => 'March',
                            4 => 'April',
                            5 => 'May',
                            6 => 'June',
                            7 => 'July',
                            8 => 'August',
                            9 => 'September',
                            10 => 'October',
                            11 => 'November',
                            12 => 'December',
                        ])
                        ->helperText('Only customers whose birthday falls in these months can claim.'),
                    Forms\Components\Select::make('product_ids')
                        ->label('Specific menu items')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(fn () => Product::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->helperText('When set, the discount applies only to the subtotal of these items in the cart.')
                        ->columnSpanFull(),
                    Forms\Components\Select::make('combo_ids')
                        ->label('Specific combos')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(fn () => Combo::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->helperText('Combine with menu items above (matching is OR) or use alone. Leave both empty to apply to the whole order.')
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('banner_image')->label('Banner')->size(56)->square(),
                Tables\Columns\TextColumn::make('code')->searchable()->copyable()->badge()->color('primary'),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('discount_type')->badge(),
                Tables\Columns\TextColumn::make('discount_value')
                    ->formatStateUsing(fn ($state, Voucher $r) => $r->discount_type === 'percentage' ? "{$state}%" : "RM{$state}"),
                Tables\Columns\TextColumn::make('used_count')->sortable()->label('Used'),
                Tables\Columns\TextColumn::make('max_uses')->placeholder('∞'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'active' => 'success',
                        'paused' => 'warning',
                        'expired' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('valid_until')->dateTime()->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(['active' => 'Active', 'paused' => 'Paused', 'expired' => 'Expired']),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVouchers::route('/'),
            'create' => Pages\CreateVoucher::route('/create'),
            'edit' => Pages\EditVoucher::route('/{record}/edit'),
        ];
    }
}
