<?php

namespace App\Models;

use App\Enums\AnnouncementType;
use App\Models\Concerns\HasFrench;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['type', 'message', 'message_fr', 'body', 'body_fr', 'link_url', 'link_label', 'link_label_fr', 'starts_at', 'ends_at', 'is_active', 'sort_order'])]
class Announcement extends Model
{
    use HasFactory, HasFrench;

    protected function casts(): array
    {
        return [
            'type' => AnnouncementType::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Switched on and inside its show-from / show-until window. Checked on every
     * request rather than by a scheduled job, so it appears and disappears on
     * the minute.
     */
    public function scopeShowing(Builder $query): void
    {
        $now = now();

        $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', $now))
            ->orderBy('sort_order')
            ->orderByDesc('starts_at');
    }

    public function isShowing(): bool
    {
        return $this->is_active
            && ($this->starts_at === null || $this->starts_at->lte(now()))
            && ($this->ends_at === null || $this->ends_at->gt(now()));
    }
}
