<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\Voucher;
use App\Services\Settings\SettingsRepository;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * @property Form $form
 */
class UpsellSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Checkout Upsell';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.upsell-settings';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(SettingsRepository $settings): void
    {
        $ids = json_decode($settings->get('upsell.product_ids', '[]') ?? '[]', true);
        $prices = json_decode($settings->get('upsell.prices', '{}') ?? '{}', true);
        $prices = is_array($prices) ? $prices : [];

        $items = [];
        foreach (is_array($ids) ? $ids : [] as $id) {
            $items[] = [
                'product_id' => (int) $id,
                'price' => $prices[(string) $id] ?? null,
            ];
        }

        $voucherIds = json_decode($settings->get('upsell.voucher_ids', '[]') ?? '[]', true);

        $this->form->fill([
            'enabled' => $settings->get('upsell.enabled', '0') === '1',
            'title' => $settings->get('upsell.title', 'Add something extra?'),
            'items' => $items,
            'voucher_ids' => is_array($voucherIds) ? array_map('intval', $voucherIds) : [],
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Section::make('Upsell status')
                    ->description('When on, customers are offered these products in a pop-up before checkout. They can add any of them or skip straight to checkout.')
                    ->schema([
                        Toggle::make('enabled')
                            ->label('Show upsell before checkout')
                            ->helperText('Disable to hide the upsell step without losing your product selection.')
                            ->default(false),
                        TextInput::make('title')
                            ->label('Pop-up heading')
                            ->helperText('Shown at the top of the upsell pop-up.')
                            ->maxLength(80)
                            ->required(),
                    ]),

                Section::make('Upsell products')
                    ->description('Pick the products offered as an upsell and set a special upsell price for each. They are added with no options — keep them to simple add-ons (snacks, water, extra shots).')
                    ->schema([
                        Repeater::make('items')
                            ->label('Products')
                            ->addActionLabel('Add product')
                            ->reorderable(false)
                            ->defaultItems(0)
                            ->columns(3)
                            ->schema([
                                Select::make('product_id')
                                    ->label('Product')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->distinct()
                                    ->options(fn () => Product::query()->orderBy('name')->pluck('name', 'id')->all())
                                    ->columnSpan(2),
                                TextInput::make('price')
                                    ->label('Upsell price')
                                    ->prefix('RM')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step('0.01')
                                    ->helperText('Blank = normal price.'),
                            ])
                            ->helperText('Products already in the customer\'s cart are skipped automatically.'),
                    ]),

                Section::make('Upsell vouchers')
                    ->description('Vouchers offered in the same pop-up. Customers claim them into their wallet with one tap, then apply them at checkout — a good nudge to spend up to the minimum.')
                    ->schema([
                        Select::make('voucher_ids')
                            ->label('Vouchers')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(fn () => Voucher::query()
                                ->where('is_spin_only', false)
                                ->where('is_check_in_only', false)
                                ->orderBy('name')
                                ->get(['id', 'name', 'code'])
                                ->mapWithKeys(fn (Voucher $v) => [$v->id => "{$v->name} ({$v->code})"])
                                ->all())
                            ->helperText('Only shown to signed-in customers who are eligible and have not claimed it yet. Expired, fully-used and other-branch vouchers are hidden automatically.'),
                    ]),
            ]);
    }

    public function save(SettingsRepository $settings): void
    {
        $data = $this->form->getState();

        $ids = [];
        $prices = [];
        foreach ($data['items'] ?? [] as $item) {
            $id = (int) ($item['product_id'] ?? 0);
            if ($id === 0 || in_array($id, $ids, true)) {
                continue;
            }
            $ids[] = $id;
            $price = $item['price'] ?? null;
            if ($price !== null && $price !== '') {
                $prices[(string) $id] = round((float) $price, 2);
            }
        }

        $settings->setMany([
            'upsell.enabled' => ['value' => ! empty($data['enabled']) ? '1' : '0'],
            'upsell.title' => ['value' => (string) ($data['title'] ?? 'Add something extra?')],
            'upsell.product_ids' => ['value' => json_encode($ids)],
            'upsell.prices' => ['value' => json_encode((object) $prices)],
            'upsell.voucher_ids' => ['value' => json_encode(array_values(array_map('intval', $data['voucher_ids'] ?? [])))],
        ]);

        Notification::make()
            ->title('Upsell settings saved')
            ->body('Changes apply to new checkouts immediately.')
            ->success()
            ->send();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'hq_admin']) ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'hq_admin']) ?? false;
    }
}
