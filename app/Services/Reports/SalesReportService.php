<?php

namespace App\Services\Reports;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalesReportService
{
    public function range(string $period, string $anchor): array
    {
        $date = CarbonImmutable::parse($anchor);

        return match ($period) {
            'weekly' => [$date->startOfWeek(), $date->endOfWeek()],
            'monthly' => [$date->startOfMonth(), $date->endOfMonth()],
            'yearly' => [$date->startOfYear(), $date->endOfYear()],
            default => [$date->startOfDay(), $date->endOfDay()],
        };
    }

    /** @return array<string, mixed> */
    public function summary(Carbon|CarbonImmutable $from, Carbon|CarbonImmutable $to, ?int $branchId = null): array
    {
        $base = $this->paidOrders($branchId)->whereBetween('created_at', [$from, $to]);

        $revenue = (float) (clone $base)->sum('total');
        $subtotal = (float) (clone $base)->sum('subtotal');
        $sst = (float) (clone $base)->sum('sst_amount');
        $serviceCharge = (float) (clone $base)->sum('service_charge_amount');
        $discounts = (float) (clone $base)->sum('discount_amount');
        $orders = (int) (clone $base)->count();

        $allBase = Order::query()
            ->when($branchId, fn ($q, $id) => $q->where('branch_id', $id))
            ->whereBetween('created_at', [$from, $to]);
        $totalOrders = (int) (clone $allBase)->count();
        $cancelled = (int) (clone $allBase)->where('status', OrderStatus::Cancelled->value)->count();
        $refunded = (int) (clone $allBase)->where('status', OrderStatus::Refunded->value)->count();

        return [
            'orders' => $orders,
            'total_orders' => $totalOrders,
            'cancelled' => $cancelled,
            'refunded' => $refunded,
            'subtotal' => $subtotal,
            'discounts' => $discounts,
            'sst' => $sst,
            'service_charge' => $serviceCharge,
            'revenue' => $revenue,
            'avg_ticket' => $orders > 0 ? round($revenue / $orders, 2) : 0,
        ];
    }

    /** @return list<array<string, mixed>> */
    public function byBranch(Carbon|CarbonImmutable $from, Carbon|CarbonImmutable $to, ?int $branchId = null): array
    {
        $rows = $this->paidOrdersQuery($branchId)
            ->whereBetween('orders.created_at', [$from, $to])
            ->join('branches', 'branches.id', '=', 'orders.branch_id')
            ->selectRaw('branches.id as branch_id, branches.name as branch_name, COUNT(*) as orders, SUM(orders.total) as revenue, SUM(orders.discount_amount) as discounts')
            ->groupBy('branches.id', 'branches.name')
            ->orderByDesc('revenue')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            /** @var object{branch_id: int|string, branch_name: string, orders: int|string, revenue: int|string|float, discounts: int|string|float} $row */
            $out[] = [
                'branch_id' => (int) $row->branch_id,
                'branch_name' => (string) $row->branch_name,
                'orders' => (int) $row->orders,
                'revenue' => (float) $row->revenue,
                'discounts' => (float) $row->discounts,
            ];
        }

        return $out;
    }

    /** @return list<array<string, mixed>> */
    public function topProducts(Carbon|CarbonImmutable $from, Carbon|CarbonImmutable $to, ?int $branchId = null, int $limit = 10): array
    {
        $rows = $this->paidOrdersQuery($branchId)
            ->whereBetween('orders.created_at', [$from, $to])
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->selectRaw('order_items.product_name, SUM(order_items.quantity) as qty, SUM(order_items.line_total) as revenue')
            ->groupBy('order_items.product_name')
            ->orderByDesc('qty')
            ->limit($limit)
            ->get();

        $out = [];
        foreach ($rows as $row) {
            /** @var object{product_name: string, qty: int|string, revenue: int|string|float} $row */
            $out[] = [
                'product_name' => (string) $row->product_name,
                'quantity' => (int) $row->qty,
                'revenue' => (float) $row->revenue,
            ];
        }

        return $out;
    }

    /** @return list<array<string, mixed>> */
    public function timeSeries(Carbon|CarbonImmutable $from, Carbon|CarbonImmutable $to, ?int $branchId = null): array
    {
        $rows = $this->paidOrdersQuery($branchId)
            ->whereBetween('orders.created_at', [$from, $to])
            ->selectRaw('DATE(orders.created_at) as d, COUNT(*) as orders, SUM(orders.total) as revenue')
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->keyBy('d');

        $out = [];
        $cursor = CarbonImmutable::parse($from->toDateString());
        $end = CarbonImmutable::parse($to->toDateString());
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            /** @var object{orders: int|string, revenue: int|string|float}|null $row */
            $row = $rows->get($key);
            $out[] = [
                'date' => $key,
                'orders' => $row ? (int) $row->orders : 0,
                'revenue' => $row ? (float) $row->revenue : 0,
            ];
            $cursor = $cursor->addDay();
        }

        return $out;
    }

    /**
     * @return list<array{
     *     id: int,
     *     number: string,
     *     branch: string,
     *     status: string,
     *     payment_status: string,
     *     payment_method: string|null,
     *     order_type: string,
     *     customer: string|null,
     *     subtotal: float,
     *     discount: float,
     *     sst: float,
     *     service_charge: float,
     *     total: float,
     *     created_at: string,
     *     items: list<array{name: string, quantity: int, unit_price: float, line_total: float, modifiers: string}>,
     * }>
     */
    public function orders(Carbon|CarbonImmutable $from, Carbon|CarbonImmutable $to, ?int $branchId = null, ?int $limit = null): array
    {
        $query = Order::query()
            ->whereBetween('created_at', [$from, $to])
            ->when($branchId, fn ($q, $id) => $q->where('branch_id', $id))
            ->with([
                'branch:id,name',
                'items' => fn ($q) => $q->select(['id', 'order_id', 'product_name', 'quantity', 'unit_price', 'line_total']),
                'items.modifiers:id,order_item_id,option_name',
            ])
            ->orderBy('created_at');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $rows = $query->get();

        $out = [];
        foreach ($rows as $order) {
            $items = [];
            foreach ($order->items as $item) {
                $modifiers = $item->modifiers
                    ->pluck('option_name')
                    ->filter()
                    ->join(', ');
                $items[] = [
                    'name' => (string) $item->product_name,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->line_total,
                    'modifiers' => (string) $modifiers,
                ];
            }

            /** @var array<string, mixed>|null $snapshot */
            $snapshot = $order->customer_snapshot;

            $out[] = [
                'id' => (int) $order->id,
                'number' => (string) $order->number,
                'branch' => $order->branch ? (string) $order->branch->name : '—',
                'status' => $order->status->value,
                'payment_status' => $order->payment_status->value,
                'payment_method' => $order->payment_method,
                'order_type' => $order->order_type->value,
                'customer' => isset($snapshot['name']) ? (string) $snapshot['name'] : null,
                'subtotal' => (float) $order->subtotal,
                'discount' => (float) $order->discount_amount,
                'sst' => (float) $order->sst_amount,
                'service_charge' => (float) ($order->service_charge_amount ?? 0),
                'total' => (float) $order->total,
                'created_at' => $order->created_at?->toDateTimeString() ?? '',
                'items' => $items,
            ];
        }

        return $out;
    }

    /** @return Builder<Order> */
    protected function paidOrders(?int $branchId = null): Builder
    {
        return Order::query()
            ->where('payment_status', PaymentStatus::Paid->value)
            ->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Refunded->value])
            ->when($branchId, fn ($q, $id) => $q->where('branch_id', $id));
    }

    /** Raw query builder for aggregate selects that bypass Eloquent property typing. */
    protected function paidOrdersQuery(?int $branchId = null): QueryBuilder
    {
        return DB::table('orders')
            ->where('orders.payment_status', PaymentStatus::Paid->value)
            ->whereNotIn('orders.status', [OrderStatus::Cancelled->value, OrderStatus::Refunded->value])
            ->when($branchId, fn ($q, $id) => $q->where('orders.branch_id', $id));
    }
}
