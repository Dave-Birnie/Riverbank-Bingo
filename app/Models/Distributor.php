<?php

namespace App\Models;

use App\Enums\DistributorType;
use App\Models\Concerns\HasFrench;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'type', 'address', 'city', 'region', 'postal_code', 'phone', 'hours', 'hours_fr', 'notes', 'notes_fr', 'latitude', 'longitude', 'is_active', 'sort_order'])]
class Distributor extends Model
{
    use HasFactory, HasFrench;

    protected function casts(): array
    {
        return [
            'type' => DistributorType::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    public function hasPin(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }
}
