<?php

namespace App\Models;

use App\Enums\PostStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['title', 'slug', 'excerpt', 'body', 'cover_image_path', 'status', 'published_at'])]
class Post extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Post $post) {
            $post->slug = $post->slug ?: Str::slug($post->title);

            // Publishing without a date means "now".
            if ($post->status === PostStatus::Published && $post->published_at === null) {
                $post->published_at = now();
            }
        });
    }

    /** Published and past its publish date; future dates are scheduled posts. */
    public function scopeLive(Builder $query): void
    {
        $query->where('status', PostStatus::Published)
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at');
    }

    public function isScheduled(): bool
    {
        return $this->status === PostStatus::Published && $this->published_at?->isFuture();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
