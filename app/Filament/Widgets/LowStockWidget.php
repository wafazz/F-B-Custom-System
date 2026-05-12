<?php

namespace App\Filament\Widgets;

use App\Models\BranchStock;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LowStockWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    protected static ?string $heading = 'Low stock alerts';

    protected static ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->buildQuery())
            ->columns([
                Tables\Columns\TextColumn::make('branch.name')->label('Branch')->sortable(),
                Tables\Columns\TextColumn::make('product.name')->label('Product')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('quantity')->label('On hand')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('low_threshold')->label('Threshold')->numeric(),
                Tables\Columns\TextColumn::make('last_restocked_at')->label('Last restock')->since()->placeholder('never'),
            ])
            ->emptyStateHeading('All stock levels healthy')
            ->emptyStateDescription('No branch is at or below its low-stock threshold right now.')
            ->emptyStateIcon('heroicon-o-check-badge')
            ->defaultSort('quantity', 'asc')
            ->paginated(false);
    }

    /** @return Builder<BranchStock> */
    protected function buildQuery(): Builder
    {
        return BranchStock::query()
            ->with(['branch', 'product'])
            ->lowStock()
            ->limit(20);
    }
}
