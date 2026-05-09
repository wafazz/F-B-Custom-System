<?php

namespace App\Http\Requests;

use App\Enums\OrderType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'order_type' => ['required', Rule::enum(OrderType::class)],
            'dine_in_table' => ['nullable', 'string', 'max:20', 'required_if:order_type,dine_in'],
            'pickup_at' => ['nullable', 'date', 'after_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'lines.*.modifier_option_ids' => ['array'],
            'lines.*.modifier_option_ids.*' => ['integer', 'exists:modifier_options,id'],
            'lines.*.notes' => ['nullable', 'string', 'max:200'],
            'voucher_code' => ['nullable', 'string', 'max:40'],
            'loyalty_redeem_points' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'payment_method' => ['nullable', \Illuminate\Validation\Rule::in(['gateway', 'wallet'])],
        ];
    }
}
