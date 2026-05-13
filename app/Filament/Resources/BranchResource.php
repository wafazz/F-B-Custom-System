<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchResource\Pages;
use App\Models\Branch;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Branch Identity')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),
                    Forms\Components\TextInput::make('code')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(20)
                        ->placeholder('SC-KLCC'),
                    Forms\Components\Select::make('status')
                        ->options([
                            'active' => 'Active',
                            'closed' => 'Temporarily Closed',
                            'maintenance' => 'Maintenance',
                        ])
                        ->required()
                        ->default('active')
                        ->native(false),
                    Forms\Components\Toggle::make('accepts_orders')
                        ->default(true)
                        ->helperText('When off, customers cannot place orders to this branch.'),
                    Forms\Components\TextInput::make('sort_order')
                        ->numeric()
                        ->default(0),
                ])
                ->columns(3),

            Forms\Components\Section::make('Contact')
                ->schema([
                    Forms\Components\TextInput::make('phone')
                        ->tel()
                        ->maxLength(20)
                        ->placeholder('+603 1234 5678'),
                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->maxLength(255),
                ])
                ->columns(2),

            Forms\Components\Section::make('Address & Location')
                ->schema([
                    Forms\Components\Textarea::make('address')
                        ->required()
                        ->rows(2)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('city')->maxLength(100),
                    Forms\Components\TextInput::make('state')->maxLength(100),
                    Forms\Components\TextInput::make('postal_code')->maxLength(10),
                    Forms\Components\TextInput::make('latitude')
                        ->numeric()
                        ->step(0.0000001)
                        ->placeholder('3.1579'),
                    Forms\Components\TextInput::make('longitude')
                        ->numeric()
                        ->step(0.0000001)
                        ->placeholder('101.7117'),
                    Forms\Components\TextInput::make('pickup_radius_meters')
                        ->label('Pickup radius (m)')
                        ->numeric()
                        ->default(1000)
                        ->helperText('Customer detection radius for "nearest branch".'),
                ])
                ->columns(3),

            Forms\Components\Section::make('Operating Hours')
                ->description('Set per-day hours. Disable a day to mark closed.')
                ->schema([
                    Forms\Components\Repeater::make('operating_hours')
                        ->label('')
                        ->schema([
                            Forms\Components\Hidden::make('day'),
                            Forms\Components\Placeholder::make('day_label')
                                ->label('Day')
                                ->content(fn (callable $get) => ucfirst((string) $get('day'))),
                            Forms\Components\Toggle::make('enabled')->inline(false)->default(true),
                            Forms\Components\TimePicker::make('open')->seconds(false)->default('08:00'),
                            Forms\Components\TimePicker::make('close')->seconds(false)->default('22:00'),
                        ])
                        ->columns(4)
                        ->default(fn () => collect(Branch::defaultOperatingHours())
                            ->map(fn ($v, $k) => array_merge($v, ['day' => $k]))
                            ->values()
                            ->all())
                        ->afterStateHydrated(function ($state, callable $set) {
                            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                            $rows = is_array($state) ? array_values($state) : [];
                            $normalized = [];
                            foreach ($days as $i => $day) {
                                $row = is_array($rows[$i] ?? null) ? $rows[$i] : [];
                                $normalized[] = [
                                    'day' => $day,
                                    'enabled' => $row['enabled'] ?? true,
                                    'open' => $row['open'] ?? '08:00',
                                    'close' => $row['close'] ?? '22:00',
                                ];
                            }
                            $set('operating_hours', $normalized);
                        })
                        ->dehydrateStateUsing(function ($state) {
                            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                            $rows = array_values((array) $state);
                            $out = [];
                            foreach ($days as $i => $day) {
                                $row = is_array($rows[$i] ?? null) ? $rows[$i] : [];
                                $out[$day] = [
                                    'enabled' => $row['enabled'] ?? true,
                                    'open' => $row['open'] ?? '08:00',
                                    'close' => $row['close'] ?? '22:00',
                                ];
                            }
                            return $out;
                        })
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false),
                ])
                ->collapsible(),

            Forms\Components\Section::make('Tax & Service Charge')
                ->description('Both rates apply to this branch only. SST + service charge are added on top of the (post-discount) subtotal.')
                ->schema([
                    Forms\Components\TextInput::make('sst_rate')
                        ->label('SST rate (%)')
                        ->numeric()
                        ->default(6.00)
                        ->step(0.01)
                        ->suffix('%'),
                    Forms\Components\Toggle::make('sst_enabled')
                        ->label('SST enabled')
                        ->default(true)
                        ->inline(false),
                    Forms\Components\TextInput::make('service_charge_rate')
                        ->label('Service charge (%)')
                        ->numeric()
                        ->default(0)
                        ->step(0.01)
                        ->suffix('%'),
                    Forms\Components\Toggle::make('service_charge_enabled')
                        ->label('Service charge enabled')
                        ->default(false)
                        ->inline(false),
                ])
                ->columns(2)
                ->collapsed(),

            Forms\Components\Section::make('Receipt')
                ->schema([
                    Forms\Components\Textarea::make('receipt_header')->rows(2),
                    Forms\Components\Textarea::make('receipt_footer')->rows(2),
                ])
                ->columns(2)
                ->collapsed(),

            Forms\Components\Section::make('Barista Sticker Labels')
                ->description('Auto-print thermal sticker labels for each item when an order moves to "Preparing" on the POS. Labels are sent to whatever printer is set as default in the POS device\'s OS.')
                ->schema([
                    Forms\Components\Toggle::make('auto_print_labels')
                        ->label('Auto-print on "Start preparing"')
                        ->default(false),
                    Forms\Components\TextInput::make('label_copies')
                        ->label('Copies per item')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(5)
                        ->default(1)
                        ->helperText('1 = one sticker per drink. 2 = duplicate (cup + bag).'),
                    Forms\Components\Select::make('label_size')
                        ->label('Label size')
                        ->options(['58mm' => '58mm (small)', '80mm' => '80mm (large)'])
                        ->default('58mm'),
                ])
                ->columns(3)
                ->collapsed(),

            Forms\Components\Section::make('Branding')
                ->schema([
                    Forms\Components\FileUpload::make('cover_image')
                        ->image()
                        ->directory('branches/covers')
                        ->maxSize(2048),
                    Forms\Components\FileUpload::make('logo')
                        ->image()
                        ->directory('branches/logos')
                        ->maxSize(1024),
                ])
                ->columns(2)
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('city')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('phone')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('staff_count')
                    ->label('Staff')
                    ->counts('staff')
                    ->numeric()
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'active' => 'success',
                        'closed' => 'danger',
                        'maintenance' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('accepts_orders')
                    ->label('Orders')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'active' => 'Active',
                    'closed' => 'Closed',
                    'maintenance' => 'Maintenance',
                ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('toggleAcceptsOrders')
                    ->label(fn (Branch $r) => $r->accepts_orders ? 'Stop Orders' : 'Resume Orders')
                    ->icon(fn (Branch $r) => $r->accepts_orders ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn (Branch $r) => $r->accepts_orders ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->action(fn (Branch $r) => $r->update(['accepts_orders' => ! $r->accepts_orders])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            BranchResource\RelationManagers\StaffRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranch::route('/create'),
            'edit' => Pages\EditBranch::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
        /** @var User|null $user */
        $user = auth()->user();

        if ($user?->hasRole('branch_manager')
            && ! $user->hasAnyRole(['super_admin', 'hq_admin', 'ops_manager'])) {
            $query->whereIn('id', $user->branches()->pluck('branches.id'));
        }

        return $query;
    }
}
