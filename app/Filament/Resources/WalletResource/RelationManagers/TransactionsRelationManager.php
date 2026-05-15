<?php

namespace App\Filament\Resources\WalletResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $title = 'Transactions';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'topup' => 'success',
                        'spend' => 'warning',
                        'refund' => 'info',
                        'adjustment' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('amount')
                    ->money('MYR')
                    ->color(fn ($state) => (float) $state >= 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('balance_after')
                    ->money('MYR')
                    ->label('Balance after'),
                Tables\Columns\TextColumn::make('description')->wrap(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options([
                    'topup' => 'Top-up',
                    'spend' => 'Spend',
                    'refund' => 'Refund',
                    'adjustment' => 'Adjustment',
                ]),
            ])
            ->paginated([25, 50, 100]);
    }
}
