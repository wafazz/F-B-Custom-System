<?php

namespace App\Notifications;

use App\Models\MembershipTier;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TierUpgradedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly MembershipTier $tier)
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
        $multiplier = number_format((float) $this->tier->earn_multiplier, 2);

        return [
            'type' => 'loyalty.tier-upgraded',
            'title' => "You're now {$this->tier->name}!",
            'body' => "Your spending unlocked the {$this->tier->name} tier. Earn {$multiplier}× points on every order from now on.",
            'url' => '/loyalty',
        ];
    }
}
