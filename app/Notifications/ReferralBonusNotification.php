<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReferralBonusNotification extends Notification
{
    use Queueable;

    /**
     * @param  'referrer'|'referee'  $role
     */
    public function __construct(
        public readonly string $role,
        public readonly string $otherPartyName,
        public readonly int $points,
    ) {
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        if ($this->role === 'referrer') {
            $title = "+{$this->points} referral bonus!";
            $body = "{$this->otherPartyName} placed their first order. Bonus points credited to your account.";
        } else {
            $title = "Welcome bonus: +{$this->points} pts";
            $body = "Thanks for joining through {$this->otherPartyName}. Bonus points credited to your account.";
        }

        return [
            'type' => 'referral.bonus',
            'title' => $title,
            'body' => $body,
            'url' => '/loyalty',
            'role' => $this->role,
            'points' => $this->points,
        ];
    }
}
