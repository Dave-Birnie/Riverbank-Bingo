<?php

namespace App\Support;

use Illuminate\Support\Arr;

/**
 * The site's two languages. English lives at the root; French under /fr with
 * French URLs (/fr/horaire, /fr/en-direct, …).
 */
class Locales
{
    public const DEFAULT = 'en';

    public const ALL = ['en', 'fr'];

    /** URL path for each public route, per language. */
    public const PATHS = [
        'schedule' => ['en' => 'schedule', 'fr' => 'horaire'],
        'watch' => ['en' => 'watch-live', 'fr' => 'en-direct'],
        'distributors' => ['en' => 'distributors', 'fr' => 'distributeurs'],
        'winners' => ['en' => 'winners', 'fr' => 'gagnants'],
        'blog' => ['en' => 'news', 'fr' => 'nouvelles'],
        'contact' => ['en' => 'contact', 'fr' => 'contact'],
        'page.about' => ['en' => 'about', 'fr' => 'a-propos'],
        'page.how-to-play' => ['en' => 'how-to-play', 'fr' => 'comment-jouer'],
        'page.rules' => ['en' => 'rules', 'fr' => 'reglements'],
        'page.privacy' => ['en' => 'privacy', 'fr' => 'confidentialite'],
        'page.become-a-distributor' => ['en' => 'become-a-distributor', 'fr' => 'devenir-distributeur'],
    ];

    public static function path(string $name, string $locale): string
    {
        return self::PATHS[$name][$locale];
    }

    /** "schedule" in English, "fr.schedule" in French. */
    public static function routeName(string $name, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return $locale === self::DEFAULT ? $name : "{$locale}.{$name}";
    }

    public static function route(string $name, mixed $parameters = [], ?string $locale = null): string
    {
        return route(self::routeName($name, $locale), $parameters);
    }

    /**
     * The current page in every language, for the language switch and the
     * hreflang links. Falls back to the home page when there's no match.
     *
     * @return array<string, string>
     */
    public static function alternates(): array
    {
        $route = request()->route();
        $name = $route?->getName();
        $base = $name ? preg_replace('/^('.implode('|', self::ALL).')\./', '', $name) : null;
        $parameters = $route ? Arr::only($route->parameters(), $route->parameterNames()) : [];

        return collect(self::ALL)->mapWithKeys(function (string $locale) use ($base, $parameters) {
            $routeName = $base ? self::routeName($base, $locale) : null;

            return [$locale => $routeName && app('router')->has($routeName)
                ? route($routeName, $parameters)
                : route(self::routeName('home', $locale))];
        })->all();
    }

    /** One field of a JSON list item (a prize, a jackpot) in the current language: name_fr, else name. */
    public static function item(array $item, string $key): mixed
    {
        $locale = app()->getLocale();

        return $locale !== self::DEFAULT && filled($item["{$key}_{$locale}"] ?? null)
            ? $item["{$key}_{$locale}"]
            : ($item[$key] ?? null);
    }

    public static function htmlLang(): string
    {
        return app()->getLocale().'-CA';
    }
}
