<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['topic', 'name', 'email', 'phone', 'message', 'read_at'])]
class ContactMessage extends Model
{
    public const TOPICS = [
        'general' => 'General question',
        'cards' => 'Cards and delivery',
        'winnings' => 'Winnings and payouts',
        'distributor' => 'Becoming a distributor',
    ];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function topicLabel(): string
    {
        return self::TOPICS[$this->topic] ?? $this->topic;
    }
}
