<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\OrderItem;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TopProductsWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected static ?string $heading = 'Top products (last 30 days)';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->buildQuery())
            ->columns([
                Tables\Columns\TextColumn::make('product_name')->label('Product'),
                Tables\Columns\TextColumn::make('product_sku')->label('SKU')->badge(),
                Tables\Columns\TextColumn::make('units_sold')->label('Units')->numeric(),
                Tables\Columns\TextColumn::make('revenue')->label('Revenue')->money('MYR'),
            ])
            ->paginated(false);
    }

    /** Aggregated rows have no `id` — key by product_id (deleted products fall back to SKU). */
    public function getTableRecordKey(Model $record): string
    {
        $productId = $record->getAttribute('product_id');
        if ($productId !== null) {
            return (string) $productId;
        }

        return 'sku-'.((string) $record->getAttribute('product_sku'));
    }

    /** @return Builder<OrderItem> */
    protected function buildQuery(): Builder
    {
        return OrderItem::query()
            ->selectRaw('MIN(id) AS id, product_id, product_name, product_sku, SUM(quantity) AS units_sold, SUM(line_total) AS revenue')
            ->whereHas('order', fn ($q) => $q
                ->where('created_at', '>=', now()->subDays(30))
                ->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Refunded->value]))
            ->groupBy('product_id', 'product_name', 'product_sku')
            ->orderByDesc('units_sold')
            ->limit(10);
    }
}
