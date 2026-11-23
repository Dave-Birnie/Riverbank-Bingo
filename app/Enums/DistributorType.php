<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DistributorType: string implements HasLabel
{
    case Pickup = 'pickup';
    case Delivery = 'delivery';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pickup => 'Pickup location',
            self::Delivery => 'Delivery',
        };
    }
}
