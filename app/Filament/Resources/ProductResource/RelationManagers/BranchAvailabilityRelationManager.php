<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\Branch;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BranchAvailabilityRelationManager extends RelationManager
{
    protected static string $relationship = 'branches';

    protected static ?string $title = 'Branch Availability & Pricing';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Toggle::make('is_available')
                ->default(true)
                ->helperText('When off, this branch will not show this product on the menu.'),
            Forms\Components\TextInput::make('price_override')
                ->numeric()
                ->prefix('RM')
                ->step(0.01)
                ->helperText('Leave empty to use base price.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('code')->badge()->color('primary'),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('city')->toggleable(),
                Tables\Columns\IconColumn::make('pivot.is_available')->boolean()->label('Available'),
                Tables\Columns\TextColumn::make('pivot.price_override')
                    ->label('Price override')
                    ->money('MYR')
                    ->placeholder('—'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action) => [
                        $action->getRecordSelect(),
                        Forms\Components\Toggle::make('is_available')->default(true),
                        Forms\Components\TextInput::make('price_override')
                            ->numeric()
                            ->prefix('RM')
                            ->step(0.01),
                    ]),
                Tables\Actions\Action::make('attachAll')
                    ->label('Attach to all branches')
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->action(function () {
                        $existing = $this->ownerProduct()->branches()->pluck('branches.id')->all();
                        $missing = Branch::whereNotIn('id', $existing)->pluck('id');
                        foreach ($missing as $branchId) {
                            $this->ownerProduct()->branches()->attach($branchId, ['is_available' => true]);
                            $this->ownerProduct()->stocks()->updateOrCreate(
                                ['branch_id' => $branchId],
                                ['quantity' => 0, 'low_threshold' => 5, 'is_available' => true, 'track_quantity' => false],
                            );
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
            ]);
    }

    protected function ownerProduct(): Product
    {
        /** @var Product $product */
        $product = $this->getOwnerRecord();

        return $product;
    }
}
