<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PointRewardClaimResource\Pages;
use App\Models\PointRewardClaim;
use App\Services\Rewards\PointRewardService;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Throwable;

class PointRewardClaimResource extends Resource
{
    protected static ?string $model = PointRewardClaim::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Loyalty';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Reward Pickups';

    protected static ?string $recordTitleAttribute = 'pickup_code';

    public static function form(Form $form): Form
    {
        // Read-only resource — fulfilment is handled via the table action.
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(PointRewardClaim::query()->with(['pointReward', 'user']))
            ->columns([
                Tables\Columns\TextColumn::make('pickup_code')
                    ->label('Code')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('warning')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Large),
                Tables\Columns\TextColumn::make('pointReward.name')->label('Reward')->searchable(),
                Tables\Columns\TextColumn::make('pointReward.kind')->label('Kind')->badge(),
                Tables\Columns\TextColumn::make('user.name')->label('Customer')->searchable(),
                Tables\Columns\TextColumn::make('user.phone')->label('Phone')->toggleable(),
                Tables\Columns\TextColumn::make('points_spent')->suffix(' pts')->sortable(),
                Tables\Columns\TextColumn::make('claimed_at')
                    ->dateTime('M d, H:i')
                    ->sortable(),
                Tables\Columns\IconColumn::make('fulfilled_at')
                    ->label('Fulfilled')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),
                Tables\Columns\TextColumn::make('fulfilled_at')
                    ->dateTime('M d, H:i')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('pending')
                    ->label('Pending only')
                    ->default()
                    ->query(fn ($query) => $query->whereNull('fulfilled_at')),
                Filter::make('fulfilled')
                    ->label('Fulfilled only')
                    ->query(fn ($query) => $query->whereNotNull('fulfilled_at')),
            ])
            ->defaultSort('claimed_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('fulfil')
                    ->label('Mark fulfilled')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (PointRewardClaim $record) => "Hand over {$record->pointReward?->name}?")
                    ->modalDescription('Confirms that this customer has received their reward. The pickup code can no longer be used after this.')
                    ->modalSubmitActionLabel('Yes, fulfilled')
                    ->visible(fn (PointRewardClaim $record) => $record->fulfilled_at === null)
                    ->action(function (PointRewardClaim $record, PointRewardService $service): void {
                        try {
                            $service->fulfil($record);
                            Notification::make()
                                ->title("Pickup {$record->pickup_code} marked fulfilled")
                                ->success()
                                ->send();
                        } catch (Throwable $e) {
                            Notification::make()
                                ->title('Could not mark fulfilled')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPointRewardClaims::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = PointRewardClaim::query()->whereNull('fulfilled_at')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
