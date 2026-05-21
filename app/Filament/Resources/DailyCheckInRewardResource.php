<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DailyCheckInRewardResource\Pages;
use App\Models\DailyCheckInReward;
use App\Models\DailyCheckInSetting;
use App\Models\Voucher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DailyCheckInRewardResource extends Resource
{
    protected static ?string $model = DailyCheckInReward::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Loyalty';

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationLabel = 'Daily Check-in';

    protected static ?string $recordTitleAttribute = 'label';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Day')
                ->schema([
                    Forms\Components\TextInput::make('day_number')
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText('Streak day this reward unlocks on. Set the max days under Settings → Daily Check-in.'),
                    Forms\Components\TextInput::make('label')
                        ->maxLength(60)
                        ->placeholder('e.g. Welcome bonus, Bonus weekend')
                        ->helperText('Optional display name shown on the customer streak card.'),
                    Forms\Components\Toggle::make('is_active')
                        ->default(true),
                ])
                ->columns(3),

            Forms\Components\Section::make('Reward')
                ->schema([
                    Forms\Components\Select::make('reward_type')
                        ->options(['points' => 'Loyalty points', 'voucher' => 'Voucher'])
                        ->default('points')
                        ->live()
                        ->required()
                        ->helperText('For a "free menu item" reward, create a 100%-off voucher scoped to that product first (mark it Check-in only), then pick it here as a Voucher reward.'),
                    Forms\Components\TextInput::make('points')
                        ->numeric()
                        ->minValue(1)
                        ->suffix('pts')
                        ->visible(fn (Forms\Get $get) => $get('reward_type') === 'points')
                        ->required(fn (Forms\Get $get) => $get('reward_type') === 'points'),
                    Forms\Components\Select::make('voucher_id')
                        ->label('Voucher to auto-claim')
                        ->searchable()
                        ->preload()
                        ->options(fn () => Voucher::query()->where('status', 'active')->pluck('name', 'id')->all())
                        ->visible(fn (Forms\Get $get) => $get('reward_type') === 'voucher')
                        ->required(fn (Forms\Get $get) => $get('reward_type') === 'voucher')
                        ->helperText('Winner gets this voucher claimed automatically when they hit this streak day.'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('day_number')->label('Day')->sortable(),
                Tables\Columns\TextColumn::make('label')->placeholder('—'),
                Tables\Columns\TextColumn::make('reward_type')->badge(),
                Tables\Columns\TextColumn::make('points')->suffix(' pts')->placeholder('—'),
                Tables\Columns\TextColumn::make('voucher.name')->placeholder('—'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('day_number')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('settings')
                    ->label('Streak settings')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->form([
                        Forms\Components\TextInput::make('max_days')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(60)
                            ->required()
                            ->helperText('Length of the streak. Day rewards beyond this number are ignored.'),
                        Forms\Components\Toggle::make('reset_on_skip')
                            ->label('Reset streak when a day is skipped')
                            ->helperText('On: missing a day sends the customer back to Day 1. Off: they pick up where they left off.'),
                    ])
                    ->fillForm(function (): array {
                        $s = DailyCheckInSetting::current();
                        return [
                            'max_days' => $s->max_days,
                            'reset_on_skip' => $s->reset_on_skip,
                        ];
                    })
                    ->action(function (array $data): void {
                        $s = DailyCheckInSetting::current();
                        $s->fill($data)->save();
                    })
                    ->modalSubmitActionLabel('Save settings'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDailyCheckInRewards::route('/'),
            'create' => Pages\CreateDailyCheckInReward::route('/create'),
            'edit' => Pages\EditDailyCheckInReward::route('/{record}/edit'),
        ];
    }
}
