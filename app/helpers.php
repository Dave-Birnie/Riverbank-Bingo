<?php

use App\Support\Locales;

if (! function_exists('lroute')) {
    /** A public URL in the current language: lroute('schedule') is /schedule or /fr/horaire. */
    function lroute(string $name, mixed $parameters = [], ?string $locale = null): string
    {
        return Locales::route($name, $parameters, $locale);
    }
}
