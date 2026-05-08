<?php

namespace App\Enums;

enum OrderType: string
{
    case Pickup = 'pickup';
    case DineIn = 'dine_in';

    public function label(): string
    {
        return match ($this) {
            self::Pickup => 'Pickup',
            self::DineIn => 'Dine-in',
        };
    }
}
