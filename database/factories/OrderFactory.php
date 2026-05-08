<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'number' => 'SC-'.now()->format('ymd').'-'.strtoupper(Str::random(4)),
            'branch_id' => Branch::factory(),
            'user_id' => null,
            'order_type' => OrderType::Pickup,
            'status' => OrderStatus::Pending,
            'subtotal' => 12.00,
            'sst_amount' => 0.72,
            'discount_amount' => 0,
            'total' => 12.72,
            'payment_status' => PaymentStatus::Unpaid,
        ];
    }
}
