<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VoucherResource\Pages;
use App\Models\Branch;
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
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
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
