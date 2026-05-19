<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SpinWheelSegmentResource\Pages;
use App\Models\SpinWheelSegment;
use App\Models\Voucher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SpinWheelSegmentResource extends Resource
{
    protected static ?string $model = SpinWheelSegment::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'Loyalty';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Spin Wheel';

    protected static ?string $recordTitleAttribute = 'label';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Segment')
                ->schema([
                    Forms\Components\TextInput::make('label')->required()->maxLength(40)
                        ->helperText('Text shown on the slice — keep it short, e.g. "5 pts", "Free latte".'),
                    Forms\Components\ColorPicker::make('color')->default('#f59e0b'),
                    Forms\Components\TextInput::make('weight')
                        ->numeric()->minValue(1)->default(1)->required()
                        ->helperText('Relative odds. e.g. 3 weight is 3x as likely as 1 weight.'),
                    Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
                    Forms\Components\Toggle::make('is_active')->default(true),
                ])
                ->columns(2),

            Forms\Components\Section::make('Prize')
                ->schema([
                    Forms\Components\Select::make('prize_type')
                        ->options(['points' => 'Loyalty points', 'voucher' => 'Voucher', 'none' => 'No prize (better luck next time)'])
                        ->default('points')
                        ->live()
                        ->required(),
                    Forms\Components\TextInput::make('prize_points')
                        ->numeric()->minValue(1)->suffix('pts')
                        ->visible(fn (Forms\Get $get) => $get('prize_type') === 'points')
                        ->required(fn (Forms\Get $get) => $get('prize_type') === 'points'),
                    Forms\Components\Select::make('voucher_id')
                        ->label('Voucher to auto-claim')
                        ->searchable()
                        ->preload()
                        ->options(fn () => Voucher::query()->where('status', 'active')->pluck('name', 'id')->all())
                        ->visible(fn (Forms\Get $get) => $get('prize_type') === 'voucher')
                        ->required(fn (Forms\Get $get) => $get('prize_type') === 'voucher')
                        ->helperText('Winner gets this voucher claimed automatically. Reuse a voucher template — same eligibility rules apply.'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ColorColumn::make('color'),
                Tables\Columns\TextColumn::make('label')->searchable(),
                Tables\Columns\TextColumn::make('prize_type')->badge(),
                Tables\Columns\TextColumn::make('prize_points')->suffix(' pts')->placeholder('—'),
                Tables\Columns\TextColumn::make('voucher.name')->placeholder('—'),
                Tables\Columns\TextColumn::make('weight')->sortable(),
                Tables\Columns\TextColumn::make('sort_order')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSpinWheelSegments::route('/'),
            'create' => Pages\CreateSpinWheelSegment::route('/create'),
            'edit' => Pages\EditSpinWheelSegment::route('/{record}/edit'),
        ];
    }
}
