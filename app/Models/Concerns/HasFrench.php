<?php

namespace App\Models\Concerns;

/**
 * Text fields with a French twin column ("title" and "title_fr"). Pages
 * read localized('title'); a blank French field falls back to the English.
 */
trait HasFrench
{
    public function localized(string $field): mixed
    {
        $locale = app()->getLocale();

        if ($locale !== 'en' && filled($value = $this->getAttribute("{$field}_{$locale}"))) {
            return $value;
        }

        return $this->getAttribute($field);
    }
}
