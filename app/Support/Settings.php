<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Site-wide settings the owner edits once and the whole site reads: contact
 * details, the stream, progressive jackpots. Loaded once per request.
 */
class Settings
{
    public const DEFAULTS = [
        'business_phone' => '450-632-4646',
        'business_email' => null,
        'mailing_address' => null,
        'facebook_url' => null,
        'youtube_channel_url' => null,
        'youtube_channel_id' => null,
        'stream_url' => null,
        'offline_message' => 'The next game starts soon. Get your cards ready!',
        'delivery_areas' => null,
        'progressives' => [],
        'live_override' => null,
        'assistant_enabled' => false,
    ];

    /** @var array<string, mixed>|null */
    private ?array $values = null;

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->values ??= array_merge(
            self::DEFAULTS,
            array_filter(Setting::query()->pluck('value', 'key')->all(), fn ($value) => $value !== null),
        );
    }

    public function set(string $key, mixed $value): void
    {
        $this->setMany([$key => $value]);
    }

    /** @param  array<string, mixed>  $values */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->values = null;
    }

    /** Digits only, for tel: links. */
    public function phoneHref(): string
    {
        return 'tel:'.preg_replace('/\D+/', '', (string) $this->get('business_phone'));
    }
}
