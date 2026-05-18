<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PointRewardResource\Pages;
use App\Models\PointReward;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PointRewardResource extends Resource
{
    protected static ?string $model = PointReward::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Loyalty';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Point Rewards';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Reward')
                ->schema([
                    Forms\Components\TextInput::make('name')->required()->maxLength(120),
                    Forms\Components\Textarea::make('description')->rows(2)->columnSpanFull(),
                    Forms\Components\FileUpload::make('banner_image')
                        ->label('Banner image')
                        ->image()
                        ->imageEditor()
                        ->imageResizeMode('cover')
                        ->imageCropAspectRatio('16:9')
                        ->directory('point-rewards/banners')
                        ->maxSize(2048)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('points')
                        ->numeric()
                        ->minValue(1)
                        ->suffix('pts')
                        ->required()
                        ->helperText('Points credited to the customer when they tap Claim.'),
                ])
                ->columns(2),

            Forms\Components\Section::make('Limits & Validity')
                ->schema([
                    Forms\Components\TextInput::make('max_claims_per_user')
                        ->numeric()
                        ->minValue(1)
                        ->default(1)
                        ->required()
                        ->helperText('How many times each customer can claim.'),
                    Forms\Components\TextInput::make('max_total_claims')
                        ->numeric()
                        ->minValue(1)
                        ->helperText('Optional global cap across all customers. Leave blank for unlimited.'),
                    Forms\Components\DateTimePicker::make('valid_from'),
                    Forms\Components\DateTimePicker::make('valid_until'),
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
                Tables\Columns\ImageColumn::make('banner_image')->label('Banner')->size(56)->square(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('points')->suffix(' pts')->sortable(),
                Tables\Columns\TextColumn::make('claimed_count')->label('Claimed')->sortable(),
                Tables\Columns\TextColumn::make('max_total_claims')->label('Cap')->placeholder('∞'),
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
            ->defaultSort('created_at', 'desc')
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPointRewards::route('/'),
            'create' => Pages\CreatePointReward::route('/create'),
            'edit' => Pages\EditPointReward::route('/{record}/edit'),
        ];
    }
}
