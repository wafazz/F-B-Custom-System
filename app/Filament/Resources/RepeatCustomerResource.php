<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Filament\Resources\RepeatCustomerResource\Pages;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class RepeatCustomerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Repeat Customers';

    protected static ?string $modelLabel = 'repeat customer';

    protected static ?string $recordTitleAttribute = 'name';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('repeat_orders_count', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('repeat_orders_count')
                    ->label('Times repeat')
                    ->badge()
                    ->color('success')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state.'×'),
                Tables\Columns\TextColumn::make('repeated_items_count')
                    ->label('Repeated items')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('top_repeated_items')
                    ->label('Top repeated items')
                    ->badge()
                    ->separator(',')
                    ->color('primary')
                    ->state(fn (User $r) => static::topRepeatedItems($r->getKey(), 5))
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_spent')
                    ->label('Total spent')
                    ->money('MYR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('last_order_at')
                    ->label('Last order')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('min_repeat')
                    ->label('Minimum visits')
                    ->options([
                        2 => '2+ times',
                        3 => '3+ times',
                        5 => '5+ times',
                        10 => '10+ times',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (filled($data['value'])) {
                            $query->having('repeat_orders_count', '>=', (int) $data['value']);
                        }

                        return $query;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('items')
                    ->label('Repeated items')
                    ->icon('heroicon-o-list-bullet')
                    ->color('warning')
                    ->modalHeading(fn (User $r) => "Repeated items — {$r->name}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn (User $r) => new HtmlString(static::itemsTable($r->getKey()))),
            ])
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRepeatCustomers::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $completed = OrderStatus::Completed->value;

        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->whereHas('orders', fn (Builder $q) => $q->where('status', $completed), '>=', 2)
            ->withCount(['orders as repeat_orders_count' => fn (Builder $q) => $q->where('status', $completed)])
            ->addSelect([
                'last_order_at' => DB::table('orders')
                    ->selectRaw('MAX(completed_at)')
                    ->whereColumn('orders.user_id', 'users.id')
                    ->where('status', $completed),
                'total_spent' => DB::table('orders')
                    ->selectRaw('COALESCE(SUM(total), 0)')
                    ->whereColumn('orders.user_id', 'users.id')
                    ->where('status', $completed),
            ])
            ->selectRaw(
                '(SELECT COUNT(*) FROM (
                    SELECT oi.product_name
                    FROM order_items oi
                    JOIN orders o ON o.id = oi.order_id
                    WHERE o.user_id = users.id AND o.status = ?
                    GROUP BY oi.product_name
                    HAVING COUNT(DISTINCT o.id) >= 2
                 ) AS r) AS repeated_items_count',
                [$completed]
            );
    }

    /** @return list<string> */
    protected static function topRepeatedItems(int $userId, int $limit): array
    {
        return DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->where('o.user_id', $userId)
            ->where('o.status', OrderStatus::Completed->value)
            ->groupBy('oi.product_name')
            ->havingRaw('COUNT(DISTINCT o.id) >= 2')
            ->orderByRaw('COUNT(DISTINCT o.id) DESC')
            ->limit($limit)
            ->pluck('oi.product_name')
            ->all();
    }

    protected static function itemsTable(int $userId): string
    {
        $rows = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->where('o.user_id', $userId)
            ->where('o.status', OrderStatus::Completed->value)
            ->groupBy('oi.product_name')
            ->havingRaw('COUNT(DISTINCT o.id) >= 2')
            ->orderByRaw('COUNT(DISTINCT o.id) DESC, SUM(oi.quantity) DESC')
            ->get([
                'oi.product_name',
                DB::raw('COUNT(DISTINCT o.id) as orders_count'),
                DB::raw('SUM(oi.quantity) as total_qty'),
            ]);

        if ($rows->isEmpty()) {
            return '<div style="padding:1rem;color:#6b7280;">This customer has not reordered any single item across multiple visits yet.</div>';
        }

        $body = '';
        foreach ($rows as $row) {
            $name = e($row->product_name);
            $body .= "<tr style=\"border-top:1px solid #e5e7eb;\">
                <td style=\"padding:.5rem .75rem;\">{$name}</td>
                <td style=\"padding:.5rem .75rem;text-align:center;font-weight:600;\">{$row->orders_count}×</td>
                <td style=\"padding:.5rem .75rem;text-align:center;\">{$row->total_qty}</td>
            </tr>";
        }

        return "<div style=\"overflow-x:auto;\">
            <table style=\"width:100%;border-collapse:collapse;font-size:.875rem;\">
                <thead><tr style=\"text-align:left;color:#6b7280;\">
                    <th style=\"padding:.5rem .75rem;\">Item</th>
                    <th style=\"padding:.5rem .75rem;text-align:center;\">Ordered in (orders)</th>
                    <th style=\"padding:.5rem .75rem;text-align:center;\">Total qty</th>
                </tr></thead>
                <tbody>{$body}</tbody>
            </table>
        </div>";
    }
}
