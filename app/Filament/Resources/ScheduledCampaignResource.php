<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduledCampaignResource\Pages;
use App\Jobs\SendScheduledCampaign;
use App\Models\ScheduledCampaign;

use App\Models\Voucher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ScheduledCampaignResource extends Resource
{
    protected static ?string $model = ScheduledCampaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?string $navigationLabel = 'Scheduled Push';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    // Restricted to super admin for now — hidden from the nav AND blocked at
    // the route level for everyone else.
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Notification')
                ->schema([
                    Forms\Components\Select::make('trigger_type')
                        ->label('Type')
                        ->required()
                        ->default('schedule')
                        ->live()
                        ->options([
                            'schedule' => 'Scheduled campaign',
                            'abandoned_cart' => 'Abandoned cart reminder (event-driven)',
                            'location' => 'Location / proximity (near an outlet)',
                        ])
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('name')
                        ->label('Campaign name (internal)')
                        ->required()
                        ->maxLength(120),
                    Forms\Components\TextInput::make('title')
                        ->label('Push title')
                        ->required()
                        ->maxLength(80),
                    Forms\Components\Textarea::make('body')
                        ->label('Push message')
                        ->required()
                        ->maxLength(180)
                        ->rows(2)
                        ->helperText('Placeholders: {name} = customer\'s first name. {branch} = outlet name (location & abandoned-cart campaigns only). {usual} = their most-bought item (usual-order reminders only).')
                        ->columnSpanFull(),
                    Forms\Components\Select::make('url_quick_pick')
                        ->label('Quick pick (auto-fills URL)')
                        ->options(fn () => self::urlOptions())
                        ->searchable()
                        ->dehydrated(false)
                        ->live()
                        ->afterStateUpdated(fn ($state, Forms\Set $set) => $state ? $set('url', $state) : null)
                        ->helperText('Pick a page or voucher. Notifications open the link directly, so only branch-independent destinations are listed — type a custom /branches/{id}/… URL if you need a specific branch.'),
                    Forms\Components\TextInput::make('url')
                        ->label('Deep-link URL')
                        ->default('/')
                        ->maxLength(200)
                        ->helperText('Where the customer lands when they tap it.'),
                ])
                ->columns(2),

            Forms\Components\Section::make('Audience')
                ->visible(fn (Forms\Get $get) => $get('trigger_type') === 'schedule')
                ->schema([
                    Forms\Components\Select::make('audience')
                        ->required()
                        ->default('all')
                        ->live()
                        ->options([
                            'all' => 'All opted-in customers',
                            'inactive' => 'Inactive customers (re-engagement)',
                            'usual' => 'Usual order reminder (come back & buy)',
                            'voucher_expiry' => 'Voucher expiring soon',
                            'birthday' => 'Birthday this month',
                        ])
                        ->columnSpanFull(),
                    Forms\Components\Select::make('voucher_id')
                        ->label('Birthday voucher (optional)')
                        ->options(fn () => Voucher::query()->where('status', 'active')->orderBy('code')->pluck('name', 'id'))
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set): void {
                            $code = $state ? Voucher::find($state)?->code : null;
                            if ($code) {
                                $set('url', '/vouchers?code='.$code);
                            }
                        })
                        ->helperText('Link the birthday-month offer. Customers who have already claimed it are skipped, so the reminders stop once they grab it. Leave empty for a plain birthday greeting.')
                        ->visible(fn (Forms\Get $get) => $get('audience') === 'birthday')
                        ->columnSpanFull(),
                    Forms\Components\Select::make('inactivity_signal')
                        ->label('Inactivity based on')
                        ->options([
                            'last_order' => 'No order placed',
                            'last_seen' => 'No app activity',
                        ])
                        ->default('last_order')
                        ->required(fn (Forms\Get $get) => $get('audience') === 'inactive')
                        ->visible(fn (Forms\Get $get) => $get('audience') === 'inactive'),
                    Forms\Components\TextInput::make('inactivity_days')
                        ->label(fn (Forms\Get $get) => match ($get('audience')) {
                            'voucher_expiry' => 'Days before expiry',
                            'usual' => 'Remind if no order in the last',
                            default => 'Days inactive',
                        })
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(365)
                        ->suffix('days')
                        ->helperText(fn (Forms\Get $get) => match ($get('audience')) {
                            'voucher_expiry' => 'Fires once, this many days before an unused voucher expires (e.g. 3, 1, or 0 for expiry day).',
                            'usual' => 'Only nudges customers who haven\'t ordered in this many days, featuring their most-bought item (e.g. 14, 30).',
                            default => 'Fires once — the day a customer reaches this many days inactive (e.g. 7, 14, 30).',
                        })
                        ->required(fn (Forms\Get $get) => in_array($get('audience'), ['inactive', 'usual', 'voucher_expiry'], true))
                        ->visible(fn (Forms\Get $get) => in_array($get('audience'), ['inactive', 'usual', 'voucher_expiry'], true)),
                    Forms\Components\Toggle::make('inactivity_repeat')
                        ->label('Keep reminding while inactive')
                        ->default(false)
                        ->live()
                        ->helperText('Off: nudge once on the day they reach the threshold (stack 7/14/30-day campaigns for a drip). On: re-send every scan while they stay inactive, throttled by the cooldown below.')
                        ->visible(fn (Forms\Get $get) => $get('audience') === 'inactive')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('inactivity_cooldown_days')
                        ->label('Re-send cooldown')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(365)
                        ->default(fn (Forms\Get $get) => $get('audience') === 'usual' ? 14 : 7)
                        ->suffix('days')
                        ->helperText('Minimum gap before the same customer is reminded again.')
                        ->required(fn (Forms\Get $get) => $get('audience') === 'usual' || ($get('audience') === 'inactive' && $get('inactivity_repeat')))
                        ->visible(fn (Forms\Get $get) => $get('audience') === 'usual' || ($get('audience') === 'inactive' && $get('inactivity_repeat'))),
                ])
                ->columns(2),

            Forms\Components\Section::make('Location')
                ->visible(fn (Forms\Get $get) => $get('trigger_type') === 'location')
                ->schema([
                    Forms\Components\Select::make('branch_id')
                        ->label('Outlet')
                        ->relationship('branch', 'name')
                        ->searchable()
                        ->preload()
                        ->required(fn (Forms\Get $get) => $get('trigger_type') === 'location'),
                    Forms\Components\TextInput::make('radius_meters')
                        ->label('Radius')
                        ->numeric()
                        ->minValue(50)
                        ->maxValue(20000)
                        ->default(500)
                        ->suffix('metres')
                        ->helperText('Notify customers within this distance of the outlet.')
                        ->required(fn (Forms\Get $get) => $get('trigger_type') === 'location'),
                ])
                ->columns(2),

            Forms\Components\Section::make('Schedule & status')
                ->schema([
                    Forms\Components\TextInput::make('delay_minutes')
                        ->label(fn (Forms\Get $get) => $get('trigger_type') === 'location' ? 'Cooldown' : 'Remind after')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(10080)
                        ->default(fn (Forms\Get $get) => $get('trigger_type') === 'location' ? 360 : 15)
                        ->suffix('minutes')
                        ->helperText(fn (Forms\Get $get) => $get('trigger_type') === 'location'
                            ? 'Minimum gap before the same customer is nudged again near this outlet.'
                            : 'How long a cart sits untouched before the reminder fires.')
                        ->required(fn (Forms\Get $get) => in_array($get('trigger_type'), ['abandoned_cart', 'location'], true))
                        ->visible(fn (Forms\Get $get) => in_array($get('trigger_type'), ['abandoned_cart', 'location'], true)),
                    Forms\Components\Select::make('frequency')
                        ->required(fn (Forms\Get $get) => $get('trigger_type') === 'schedule')
                        ->default('once')
                        ->live()
                        ->options([
                            'once' => 'Once — on a specific date & time',
                            'daily' => 'Daily — every day at a set time',
                        ])
                        ->visible(fn (Forms\Get $get) => $get('trigger_type') === 'schedule' && $get('audience') === 'all'),
                    Forms\Components\DateTimePicker::make('scheduled_at')
                        ->label('Send at')
                        ->seconds(false)
                        ->required(fn (Forms\Get $get) => $get('trigger_type') === 'schedule' && $get('audience') === 'all' && $get('frequency') === 'once')
                        ->visible(fn (Forms\Get $get) => $get('trigger_type') === 'schedule' && $get('audience') === 'all' && $get('frequency') === 'once'),
                    Forms\Components\TimePicker::make('run_time')
                        ->label(fn (Forms\Get $get) => in_array($get('audience'), ['inactive', 'usual', 'voucher_expiry', 'birthday'], true) ? 'Daily scan time' : 'Time of day')
                        ->seconds(false)
                        ->required(fn (Forms\Get $get) => $get('trigger_type') === 'schedule' && (in_array($get('audience'), ['inactive', 'usual', 'voucher_expiry', 'birthday'], true) || $get('frequency') === 'daily'))
                        ->visible(fn (Forms\Get $get) => $get('trigger_type') === 'schedule' && (in_array($get('audience'), ['inactive', 'usual', 'voucher_expiry', 'birthday'], true) || $get('frequency') === 'daily')),
                    Forms\Components\CheckboxList::make('run_days')
                        ->label('Days (peak days)')
                        ->options([
                            1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu',
                            5 => 'Fri', 6 => 'Sat', 0 => 'Sun',
                        ])
                        ->columns(4)
                        ->helperText('Leave empty to fire every day. Pick days for peak-hour targeting (e.g. Fri–Sun).')
                        ->visible(fn (Forms\Get $get) => $get('trigger_type') === 'schedule' && $get('audience') === 'all' && $get('frequency') === 'daily')
                        ->columnSpanFull(),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('trigger_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'abandoned_cart' => 'info',
                        'location' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'abandoned_cart' => 'Abandoned cart',
                        'location' => 'Location',
                        default => 'Scheduled',
                    }),
                Tables\Columns\TextColumn::make('audience')
                    ->label('Target')
                    ->badge()
                    ->color(fn (?string $state) => $state === 'inactive' ? 'warning' : 'gray')
                    ->formatStateUsing(fn (?string $state, ScheduledCampaign $r) => match (true) {
                        $r->trigger_type === 'location' => $r->branch?->name ?? 'Outlet',
                        $r->trigger_type === 'abandoned_cart' => '—',
                        $state === 'inactive' => 'Inactive · '.($r->inactivity_signal === 'last_seen' ? 'no activity' : 'no order').' '.$r->inactivity_days.'d'.($r->inactivity_repeat ? ' · repeat '.$r->inactivity_cooldown_days.'d' : ''),
                        $state === 'usual' => 'Usual order · no order '.$r->inactivity_days.'d · every '.$r->inactivity_cooldown_days.'d',
                        $state === 'voucher_expiry' => 'Voucher expiry · '.$r->inactivity_days.'d before',
                        $state === 'birthday' => 'Birthday'.($r->voucher_id ? ' · voucher' : ''),
                        default => 'All customers',
                    }),
                Tables\Columns\TextColumn::make('schedule')
                    ->label('When')
                    ->state(fn (ScheduledCampaign $r) => match (true) {
                        $r->trigger_type === 'location' => 'Within '.$r->radius_meters.'m',
                        $r->trigger_type === 'abandoned_cart' => 'After '.$r->delay_minutes.'m idle',
                        $r->frequency === 'daily' => (empty($r->run_days)
                            ? 'Daily'
                            : collect($r->run_days)->map(fn ($d) => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][(int) $d] ?? '')->implode(',')
                        ).' · '.\Illuminate\Support\Str::of((string) $r->run_time)->substr(0, 5),
                        default => $r->scheduled_at?->format('d M Y, H:i') ?? '—',
                    }),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
                Tables\Columns\TextColumn::make('deliveries_count')
                    ->counts('deliveries')
                    ->label('Sent')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('last_sent_at')
                    ->label('Last sent')
                    ->dateTime('d M, H:i')
                    ->placeholder('Never'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([
                Tables\Actions\Action::make('sendNow')
                    ->label('Send now')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn (ScheduledCampaign $record) => $record->trigger_type === 'schedule')
                    ->requiresConfirmation()
                    ->modalDescription('Send this push to all opted-in customers right now?')
                    ->action(function (ScheduledCampaign $record): void {
                        SendScheduledCampaign::dispatch($record->id);
                        $record->forceFill(['last_sent_at' => now()])->save();
                        Notification::make()
                            ->title('Campaign queued')
                            ->body('It\'s being sent to opted-in customers now.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    /**
     * Inactive campaigns are always a daily scan — force the schedule fields
     * so the once/datetime inputs (hidden for this audience) can't leak stale
     * values into the row.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeSchedule(array $data): array
    {
        $type = $data['trigger_type'] ?? 'schedule';

        // Event-driven types (abandoned_cart, location) don't use the cron
        // schedule/audience fields — clear them so nothing stale leaks in and
        // the cron scan never picks them up.
        if ($type === 'abandoned_cart' || $type === 'location') {
            $data['audience'] = 'all';
            $data['voucher_id'] = null;
            $data['inactivity_signal'] = null;
            $data['inactivity_days'] = null;
            $data['inactivity_repeat'] = false;
            $data['inactivity_cooldown_days'] = null;
            $data['scheduled_at'] = null;
            $data['run_time'] = null;
            $data['run_days'] = null;
            if ($type === 'abandoned_cart') {
                $data['branch_id'] = null;
                $data['radius_meters'] = null;
            }

            return $data;
        }

        // Scheduled campaign — clear the event-only fields.
        $data['delay_minutes'] = null;
        $data['branch_id'] = null;
        $data['radius_meters'] = null;
        $audience = $data['audience'] ?? 'all';
        if (in_array($audience, ['inactive', 'usual', 'voucher_expiry', 'birthday'], true)) {
            // Daily-scan audiences run every day — no weekday filter.
            $data['frequency'] = 'daily';
            $data['scheduled_at'] = null;
            $data['run_days'] = null;
            if ($audience === 'inactive') {
                // Repeat/cooldown only apply to the inactive audience; drop the
                // cooldown unless the admin turned repeat reminders on.
                if (empty($data['inactivity_repeat'])) {
                    $data['inactivity_repeat'] = false;
                    $data['inactivity_cooldown_days'] = null;
                }
            } elseif ($audience === 'usual') {
                // Usual reminder always throttles by the cooldown; it doesn't
                // use the signal or the repeat toggle.
                $data['inactivity_signal'] = null;
                $data['inactivity_repeat'] = false;
            } else {
                $data['inactivity_repeat'] = false;
                $data['inactivity_cooldown_days'] = null;
            }
            if ($audience === 'voucher_expiry') {
                $data['inactivity_signal'] = null; // not used for voucher expiry
            }
            if ($audience === 'birthday') {
                $data['inactivity_signal'] = null; // birthday uses neither inactivity field
                $data['inactivity_days'] = null;
            }
        } else {
            $data['voucher_id'] = null; // only the birthday audience carries a voucher
            $data['inactivity_signal'] = null;
            $data['inactivity_days'] = null;
            $data['inactivity_repeat'] = false;
            $data['inactivity_cooldown_days'] = null;
        }

        // run_days only applies to the daily 'all' broadcast (peak days).
        if (! ($audience === 'all' && ($data['frequency'] ?? null) === 'daily')) {
            $data['run_days'] = null;
        }

        return $data;
    }

    /**
     * Push-safe quick-pick destinations. Notifications open the URL directly
     * with no branch context, so only branch-independent (absolute) routes are
     * offered — plus every active voucher. Branch-relative pages (menu, cart,
     * checkout, product/category) are intentionally excluded; they'd 404.
     *
     * @return array<string, array<string, string>>
     */
    public static function urlOptions(): array
    {
        $pages = [
            '/' => 'Home',
            '/branches' => 'Browse branches / menu',
            '/vouchers' => 'My vouchers',
            '/wallet' => 'Wallet',
            '/orders' => 'My orders',
            '/loyalty' => 'Loyalty & rewards',
            '/rewards' => 'Rewards catalogue',
            '/spin' => 'Spin & Win',
            '/check-in' => 'Daily Check-in',
            '/favourites' => 'Favourites',
            '/referral' => 'Referral',
            '/notifications' => 'Notifications',
            '/profile' => 'Profile',
        ];

        $vouchers = Voucher::query()
            ->where('status', 'active')
            ->orderBy('code')
            ->get(['code', 'name'])
            ->mapWithKeys(fn (Voucher $v) => ["/vouchers?code={$v->code}" => "Voucher: {$v->code} — {$v->name}"])
            ->all();

        return array_filter([
            'Pages' => $pages,
            'Vouchers' => $vouchers,
        ]);
    }

    public static function getRelations(): array
    {
        return [
            ScheduledCampaignResource\RelationManagers\DeliveriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScheduledCampaigns::route('/'),
            'create' => Pages\CreateScheduledCampaign::route('/create'),
            'edit' => Pages\EditScheduledCampaign::route('/{record}/edit'),
        ];
    }
}
