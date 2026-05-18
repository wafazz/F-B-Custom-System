<?php

namespace App\Notifications;

use App\Models\WalletTopup;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WalletTopupPaidNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly WalletTopup $topup)
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
        $amount = number_format((float) $this->topup->amount, 2);

        return [
            'type' => 'wallet.topup-paid',
            'title' => 'Wallet top-up successful',
            'body' => "RM{$amount} added to your wallet. Ready to spend on your next order.",
            'url' => '/wallet',
            'topup_id' => $this->topup->id,
            'amount' => (float) $this->topup->amount,
        ];
    }
}
