<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletResource\Pages;
use App\Models\Wallet;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?string $navigationLabel = 'Wallets';

    protected static ?int $navigationSort = 5;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->defaultSort('balance', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->whereHas('user', function (Builder $q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: function (Builder $query, string $direction) {
                        $query->orderBy(
                            \App\Models\User::query()->select('name')->whereColumn('users.id', 'wallets.user_id'),
                            $direction,
                        );
                    }),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->toggleable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('user.phone')
                    ->label('Phone')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('balance')
                    ->money('MYR')
                    ->sortable()
                    ->weight('bold')
                    ->color(fn ($state) => (float) $state > 0 ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('lifetime_topup')
                    ->label('Lifetime top-up')
                    ->money('MYR')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('lifetime_spent')
                    ->label('Lifetime spent')
                    ->money('MYR')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last activity')
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_balance')
                    ->label('Has balance')
                    ->query(fn (Builder $query) => $query->where('balance', '>', 0))
                    ->default(),
                Tables\Filters\Filter::make('big_spender')
                    ->label('Lifetime spent ≥ RM 500')
                    ->query(fn (Builder $query) => $query->where('lifetime_spent', '>=', 500)),
            ])
            ->paginated([25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\WalletResource\RelationManagers\TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWallets::route('/'),
            'view' => Pages\ViewWallet::route('/{record}'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\WalletStatsWidget::class,
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view_any_wallet') ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_any_wallet') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('view_wallet') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
