<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Events\BranchStockChanged;
use App\Models\BranchStock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class StockRelationManager extends RelationManager
{
    protected static string $relationship = 'stocks';

    protected static ?string $title = 'Stock per Branch';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('branch_id')
                ->relationship('branch', 'name')
                ->required()
                ->searchable(),
            Forms\Components\Toggle::make('track_quantity')
                ->helperText('Off = always available; On = decrements per order.'),
            Forms\Components\TextInput::make('quantity')
                ->numeric()
                ->default(0),
            Forms\Components\TextInput::make('low_threshold')
                ->numeric()
                ->default(5),
            Forms\Components\Toggle::make('is_available')
                ->default(true),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('branch.code')->badge(),
                Tables\Columns\TextColumn::make('branch.name')->searchable(),
                Tables\Columns\IconColumn::make('track_quantity')->boolean()->label('Track'),
                Tables\Columns\TextColumn::make('quantity')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('low_threshold')->numeric()->toggleable(),
                Tables\Columns\IconColumn::make('is_available')->boolean()->label('Avail.'),
                Tables\Columns\TextColumn::make('last_restocked_at')->dateTime()->since()->toggleable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('toggleAvailable')
                    ->label(fn (BranchStock $r) => $r->is_available ? 'Mark Out-of-Stock' : 'Mark Available')
                    ->icon(fn (BranchStock $r) => $r->is_available ? 'heroicon-o-no-symbol' : 'heroicon-o-check-circle')
                    ->color(fn (BranchStock $r) => $r->is_available ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function (BranchStock $r) {
                        $r->update(['is_available' => ! $r->is_available]);
                        event(new BranchStockChanged(
                            $r->branch_id,
                            $r->product_id,
                            $r->is_available && (! $r->track_quantity || $r->quantity > 0),
                            $r->quantity,
                        ));
                    }),
                Tables\Actions\Action::make('adjust')
                    ->label('Adjust qty')
                    ->icon('heroicon-o-pencil-square')
                    ->form([
                        Forms\Components\Select::make('type')
                            ->options([
                                'restock' => 'Restock',
                                'sale' => 'Sale',
                                'wastage' => 'Wastage',
                                'adjustment' => 'Adjustment',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('delta')
                            ->numeric()
                            ->required()
                            ->helperText('Positive to add, negative to deduct.'),
                        Forms\Components\TextInput::make('reason'),
                    ])
                    ->action(function (BranchStock $r, array $data) {
                        $r->applyMovement(
                            $data['type'],
                            (int) $data['delta'],
                            $data['reason'] ?? null,
                            null,
                            auth()->id(),
                        );
                        Notification::make()
                            ->title("Stock updated → {$r->fresh()->quantity}")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
