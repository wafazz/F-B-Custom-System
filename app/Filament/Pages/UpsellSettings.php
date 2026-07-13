<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Services\Settings\SettingsRepository;
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

        $this->form->fill([
            'enabled' => $settings->get('upsell.enabled', '0') === '1',
            'title' => $settings->get('upsell.title', 'Add something extra?'),
            'product_ids' => is_array($ids) ? array_map('intval', $ids) : [],
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
                    ->description('Pick the products offered as an upsell. They are added at their normal price with no options — keep them to simple add-ons (snacks, water, extra shots).')
                    ->schema([
                        Select::make('product_ids')
                            ->label('Products')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(fn () => Product::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->helperText('Products already in the customer\'s cart are skipped automatically.'),
                    ]),
            ]);
    }

    public function save(SettingsRepository $settings): void
    {
        $data = $this->form->getState();

        $ids = array_values(array_map('intval', $data['product_ids'] ?? []));

        $settings->setMany([
            'upsell.enabled' => ['value' => ! empty($data['enabled']) ? '1' : '0'],
            'upsell.title' => ['value' => (string) ($data['title'] ?? 'Add something extra?')],
            'upsell.product_ids' => ['value' => json_encode($ids)],
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
