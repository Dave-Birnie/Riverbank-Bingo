<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable(['display_name', 'won_on', 'game_id', 'prize_label', 'amount', 'photo_path', 'story', 'consent_confirmed', 'is_published'])]
class Winner extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'won_on' => 'date',
            'amount' => 'decimal:2',
            'consent_confirmed' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // A winner's name and photo only go public with their consent on file.
        static::saving(function (Winner $winner) {
            if ($winner->is_published && ! $winner->consent_confirmed) {
                throw ValidationException::withMessages([
                    'consent_confirmed' => 'Confirm the winner agreed to be featured before publishing.',
                ]);
            }
        });
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true)->orderByDesc('won_on');
    }
}
