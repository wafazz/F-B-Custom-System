<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VoucherResource\Pages;
use App\Models\Branch;
use App\Models\Combo;
use App\Models\MembershipTier;
use App\Models\Product;
use App\Models\User;
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
                        ->options([
                            'percentage' => 'Percentage (%)',
                            'fixed' => 'Fixed (RM)',
                            'buy_x_get_y' => 'Buy X Get Y free',
                        ])
                        ->default('percentage')
                        ->required()
                        ->live(),
                    Forms\Components\TextInput::make('discount_value')
                        ->numeric()
                        ->step(0.01)
                        ->default(0)
                        ->required(fn (Forms\Get $get) => $get('discount_type') !== 'buy_x_get_y')
                        ->visible(fn (Forms\Get $get) => $get('discount_type') !== 'buy_x_get_y'),
                    Forms\Components\TextInput::make('min_subtotal')->numeric()->prefix('RM')->default(0),
                    Forms\Components\TextInput::make('max_discount')
                        ->numeric()
                        ->prefix('RM')
                        ->helperText('Cap for percentage vouchers')
                        ->visible(fn (Forms\Get $get) => $get('discount_type') === 'percentage'),
                    Forms\Components\TextInput::make('points_cost')
                        ->label('Points cost (rewards catalogue)')
                        ->numeric()
                        ->minValue(1)
                        ->suffix('pts')
                        ->helperText('Set to make this a rewards-catalogue redemption — customers pay this many loyalty points to claim. Leave blank for a free voucher.')
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Buy X Get Y free')
                ->description('Customer buys N qualifying items and gets M free. Pick the PAID items here too — these become the products customers see on the promo picker page.')
                ->visible(fn (Forms\Get $get) => $get('discount_type') === 'buy_x_get_y')
                ->schema([
                    Forms\Components\TextInput::make('bxgy_buy_qty')
                        ->label('Buy quantity (N)')
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->default(2),
                    Forms\Components\TextInput::make('bxgy_free_qty')
                        ->label('Free quantity (M)')
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->default(1),
                    Forms\Components\Select::make('product_ids')
                        ->label('Qualifying paid items')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->required()
                        ->options(fn () => Product::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->helperText('Customers must add at least N of these to their cart to redeem. Required — without this the picker page shows empty grids.')
                        ->columnSpanFull(),
                    Forms\Components\Select::make('combo_ids')
                        ->label('Qualifying paid combos')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(fn () => Combo::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->helperText('Optional — combos also count toward the N paid quantity.')
                        ->columnSpanFull(),
                    Forms\Components\Select::make('bxgy_free_scope')
                        ->label('Free items scope')
                        ->options([
                            'same' => 'Same as paid items above',
                            'cross' => 'Cross-sell (pick products below)',
                            'any' => 'Any item in the cart (cheapest auto-picked)',
                        ])
                        ->required()
                        ->live()
                        // Must dehydrate so normaliseBxgyPayload() can read the
                        // chosen scope; the matching unset() strips it before
                        // save so Eloquent doesn't see a non-column field.
                        ->afterStateHydrated(function (Forms\Components\Select $component, ?Voucher $record): void {
                            if (! $record) {
                                $component->state('same');
                                return;
                            }
                            $component->state(match (true) {
                                $record->bxgy_free_product_ids === null && $record->bxgy_free_combo_ids === null => 'same',
                                $record->bxgy_free_product_ids === [] && $record->bxgy_free_combo_ids === [] => 'any',
                                default => 'cross',
                            });
                        })
                        ->columnSpanFull(),
                    Forms\Components\Select::make('bxgy_free_product_ids')
                        ->label('Free products (cross-sell)')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(fn () => Product::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->visible(fn (Forms\Get $get) => $get('bxgy_free_scope') === 'cross')
                        ->columnSpanFull(),
                    Forms\Components\Select::make('bxgy_free_combo_ids')
                        ->label('Free combos (cross-sell)')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(fn () => Combo::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->visible(fn (Forms\Get $get) => $get('bxgy_free_scope') === 'cross')
                        ->columnSpanFull(),
                ])
                ->columns(2),


            Forms\Components\Section::make('Limits & Validity')
                ->schema([
                    Forms\Components\TextInput::make('max_uses')->numeric()->helperText('Total uses (leave blank for unlimited)'),
                    Forms\Components\TextInput::make('max_uses_per_user')->numeric()->default(1),
                    Forms\Components\DateTimePicker::make('valid_from'),
                    Forms\Components\DateTimePicker::make('valid_until'),
                    Forms\Components\TimePicker::make('valid_from_time')
                        ->label('Daily window — from')
                        ->seconds(false)
                        ->helperText('Recurring start time each day. Leave blank for no daily restriction.'),
                    Forms\Components\TimePicker::make('valid_until_time')
                        ->label('Daily window — until')
                        ->seconds(false)
                        ->helperText('Recurring end time each day. e.g. set 10:00 → 12:00 for a happy-hour voucher.'),
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
                    Forms\Components\Toggle::make('is_spin_only')
                        ->label('Spin wheel reward only')
                        ->helperText('Hide from the public /vouchers page. Voucher can only be obtained by winning it on the Spin Wheel.')
                        ->columnSpanFull(),
                    Forms\Components\Toggle::make('is_check_in_only')
                        ->label('Daily check-in reward only')
                        ->helperText('Hide from the public /vouchers page. Voucher can only be obtained by earning it through the Daily Check-in streak.')
                        ->columnSpanFull(),
                    Forms\Components\Toggle::make('staff_only')
                        ->label('Staff only')
                        ->helperText('Only users with a staff role (cashier, barista, manager, admin, etc.) can claim. Customers are excluded.')
                        ->columnSpanFull(),
                    Forms\Components\Select::make('user_ids')
                        ->label('Specific customers')
                        ->multiple()
                        ->searchable()
                        ->searchDebounce(300)
                        ->searchPrompt('Type at least 2 characters — name, email, or phone')
                        ->getSearchResultsUsing(function (string $search): array {
                            if (strlen(trim($search)) < 2) {
                                return [];
                            }

                            return User::query()
                                ->whereHas('roles', fn ($q) => $q->where('name', 'customer'))
                                ->where(function ($q) use ($search) {
                                    $q->where('name', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%")
                                        ->orWhere('phone', 'like', "%{$search}%");
                                })
                                ->orderBy('name')
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn (User $u) => [
                                    $u->id => trim("{$u->name} — {$u->email}".($u->phone ? " · {$u->phone}" : '')),
                                ])
                                ->all();
                        })
                        ->getOptionLabelsUsing(fn (array $values): array => User::query()
                            ->whereIn('id', $values)
                            ->get()
                            ->mapWithKeys(fn (User $u) => [
                                $u->id => trim("{$u->name} — {$u->email}".($u->phone ? " · {$u->phone}" : '')),
                            ])
                            ->all())
                        ->helperText('Search by name, email, or phone (2+ chars). Picks individual customers who can claim this voucher. Leave empty for no per-user restriction.')
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
                    // For BxGy these live in the "Buy X Get Y free" section
                    // above (same column, prettier label). Hide here to avoid
                    // confusing admins about where to pick paid items.
                    Forms\Components\Select::make('product_ids')
                        ->label('Specific menu items')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(fn () => Product::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->helperText('When set, the discount applies only to the subtotal of these items in the cart.')
                        ->visible(fn (Forms\Get $get) => $get('discount_type') !== 'buy_x_get_y')
                        ->columnSpanFull(),
                    Forms\Components\Select::make('combo_ids')
                        ->label('Specific combos')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(fn () => Combo::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->helperText('Combine with menu items above (matching is OR) or use alone. Leave both empty to apply to the whole order.')
                        ->visible(fn (Forms\Get $get) => $get('discount_type') !== 'buy_x_get_y')
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

    /**
     * Translate the form-only `bxgy_free_scope` select into the persisted
     * bxgy_free_product_ids + bxgy_free_combo_ids columns:
     *   - 'same'  → both null   (free pool reuses product_ids/combo_ids)
     *   - 'any'   → both []     (free pool = entire cart)
     *   - 'cross' → keep what the admin picked in the two multiselects
     *
     * Also wipes the bxgy_* fields when the voucher isn't a BxGy type, so a
     * later switch back to percentage/fixed doesn't leave stale data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normaliseBxgyPayload(array $data): array
    {
        if (($data['discount_type'] ?? null) !== 'buy_x_get_y') {
            $data['bxgy_buy_qty'] = null;
            $data['bxgy_free_qty'] = null;
            $data['bxgy_free_product_ids'] = null;
            $data['bxgy_free_combo_ids'] = null;
            // bxgy vouchers don't use discount_value, but other types need it.
            return $data;
        }

        // BxGy doesn't use discount_value — store 0 to satisfy the NOT NULL
        // decimal column (we hid the input on the form so admins don't see it).
        if (! isset($data['discount_value'])) {
            $data['discount_value'] = 0;
        }

        $scope = $data['bxgy_free_scope'] ?? 'same';
        unset($data['bxgy_free_scope']); // form-only, not a model column
        match ($scope) {
            'same' => $data['bxgy_free_product_ids'] = $data['bxgy_free_combo_ids'] = null,
            'any' => $data['bxgy_free_product_ids'] = $data['bxgy_free_combo_ids'] = [],
            default => null, // 'cross' — keep whatever the multiselects produced
        };

        return $data;
    }
}
