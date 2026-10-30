<?php

namespace App\Models;

use App\Enums\GameStatus;
use App\Models\Concerns\HasFrench;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['game_template_id', 'title', 'title_fr', 'starts_at', 'ends_at', 'card_sales_close_at', 'status', 'cancellation_note', 'cancellation_note_fr', 'prizes', 'notes', 'notes_fr'])]
class Game extends Model
{
    use HasFactory, HasFrench;

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'card_sales_close_at' => 'datetime',
            'status' => GameStatus::class,
            'prizes' => 'array',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(GameTemplate::class, 'game_template_id');
    }

    public function winners(): HasMany
    {
        return $this->hasMany(Winner::class);
    }

    /** Games that will (or did) actually run — not cancelled. */
    public function scopeScheduled(Builder $query): void
    {
        $query->where('status', GameStatus::Scheduled);
    }

    /** Games that haven't finished yet, cancelled ones included. */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('ends_at', '>', now())->orderBy('starts_at');
    }

    public function isCancelled(): bool
    {
        return $this->status === GameStatus::Cancelled;
    }

    /** Whether now falls inside the scheduled window (ignores manual overrides). */
    public function isInWindow(): bool
    {
        return ! $this->isCancelled()
            && $this->starts_at->lte(now())
            && $this->ends_at->gt(now());
    }

    public function cancel(?string $note = null, ?string $noteFr = null): void
    {
        $this->update(['status' => GameStatus::Cancelled, 'cancellation_note' => $note, 'cancellation_note_fr' => $noteFr]);
    }

    public function reinstate(): void
    {
        $this->update(['status' => GameStatus::Scheduled, 'cancellation_note' => null, 'cancellation_note_fr' => null]);
    }
}
