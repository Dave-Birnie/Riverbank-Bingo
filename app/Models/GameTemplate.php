<?php

namespace App\Models;

use App\Models\Concerns\HasFrench;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable(['name', 'title', 'title_fr', 'weekday', 'interval_weeks', 'start_time', 'duration_minutes', 'sales_close_minutes_before', 'prizes', 'notes', 'notes_fr'])]
class GameTemplate extends Model
{
    use HasFactory, HasFrench;

    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'interval_weeks' => 'integer',
            'duration_minutes' => 'integer',
            'sales_close_minutes_before' => 'integer',
            'prizes' => 'array',
        ];
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    /**
     * The first date on or after $from that falls on this template's weekday.
     */
    public function firstDateFrom(CarbonImmutable $from): CarbonImmutable
    {
        $from = $from->startOfDay();

        return $from->dayOfWeek === $this->weekday ? $from : $from->next($this->weekday);
    }

    /**
     * Where a new run of games should start: one interval after the last game
     * this template made, or the next matching weekday if it hasn't made any.
     */
    public function suggestedFirstDate(): CarbonImmutable
    {
        $last = $this->games()->latest('starts_at')->value('starts_at');

        if ($last && CarbonImmutable::parse($last)->isFuture()) {
            return CarbonImmutable::parse($last)->startOfDay()->addWeeks($this->interval_weeks);
        }

        return $this->firstDateFrom(CarbonImmutable::tomorrow());
    }

    /**
     * Create $count games on this template's cadence, starting at $firstDate
     * (moved forward to the template's weekday if needed). Dates that already
     * have a game are skipped, so running it twice doesn't double-book.
     *
     * @return Collection<int, Game> the games created
     */
    public function generateGames(CarbonImmutable $firstDate, int $count): Collection
    {
        $date = $this->firstDateFrom($firstDate);
        [$hour, $minute] = array_map('intval', explode(':', $this->start_time));
        $created = collect();

        for ($i = 0; $i < $count; $i++, $date = $date->addWeeks($this->interval_weeks)) {
            if (Game::query()->whereDate('starts_at', $date->toDateString())->exists()) {
                continue;
            }

            $startsAt = $date->setTime($hour, $minute);

            $created->push($this->games()->create([
                'title' => $this->title,
                'title_fr' => $this->title_fr,
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->addMinutes($this->duration_minutes),
                'card_sales_close_at' => $this->sales_close_minutes_before !== null
                    ? $startsAt->subMinutes($this->sales_close_minutes_before)
                    : null,
                'prizes' => $this->prizes,
                'notes' => $this->notes,
                'notes_fr' => $this->notes_fr,
            ]));
        }

        return $created;
    }
}
