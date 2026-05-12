<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentOrdersWidget extends BaseWidget
{
    protected static ?int $sort = 7;

    protected static ?string $heading = 'Latest orders';

    protected static ?string $pollingInterval = '15s';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->buildQuery())
            ->columns([
                Tables\Columns\TextColumn::make('order_number')->label('Order')->searchable(),
                Tables\Columns\TextColumn::make('branch.name')->label('Branch'),
                Tables\Columns\TextColumn::make('customer_snapshot.name')->label('Customer')->placeholder('Walk-in'),
                Tables\Columns\TextColumn::make('total')->money('MYR'),
                Tables\Columns\TextColumn::make('payment_status')->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (OrderStatus $state): string => match ($state) {
                        OrderStatus::Pending => 'warning',
                        OrderStatus::Preparing => 'info',
                        OrderStatus::Ready => 'success',
                        OrderStatus::Completed => 'gray',
                        OrderStatus::Cancelled, OrderStatus::Refunded => 'danger',
                    }),
                Tables\Columns\TextColumn::make('created_at')->since()->label('Placed'),
            ])
            ->recordUrl(fn (Order $r): string => route('filament.admin.resources.orders.view', $r))
            ->paginated(false);
    }

    /** @return Builder<Order> */
    protected function buildQuery(): Builder
    {
        return Order::query()->with(['branch'])->latest('created_at')->limit(8);
    }
}
