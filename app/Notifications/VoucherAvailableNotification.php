<?php

namespace App\Notifications;

use App\Models\Voucher;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class VoucherAvailableNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Voucher $voucher)
    {
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $discount = $this->voucher->discount_type === 'percentage'
            ? number_format((float) $this->voucher->discount_value, 0).'% off'
            : 'RM'.number_format((float) $this->voucher->discount_value, 2).' off';

        return [
            'type' => 'voucher.available',
            'title' => 'New voucher: '.$this->voucher->name,
            'body' => "{$discount} — tap to claim.",
            'url' => '/vouchers',
            'voucher_id' => $this->voucher->id,
            'voucher_code' => $this->voucher->code,
        ];
    }
}
