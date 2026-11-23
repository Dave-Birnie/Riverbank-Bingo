<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Lets the owner write [phone] or [email] inside page copy, so changing the
 * number in Settings updates every page that mentions it.
 */
class ContentTokens
{
    public function __construct(private Settings $settings) {}

    /** Owner-written HTML, cleaned of anything unsafe, with tokens filled in. */
    public function render(?string $html): string
    {
        return $this->replace(Str::sanitizeHtml((string) $html));
    }

    public function replace(string $html): string
    {
        $phone = e((string) $this->settings->get('business_phone'));
        $email = e((string) $this->settings->get('business_email'));

        return strtr($html, [
            '[phone]' => $phone !== '' ? '<a href="'.e($this->settings->phoneHref()).'">'.$phone.'</a>' : '',
            '[email]' => $email !== '' ? '<a href="mailto:'.$email.'">'.$email.'</a>' : '',
        ]);
    }
}
