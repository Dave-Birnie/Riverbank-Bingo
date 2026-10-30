<?php

namespace App\Models;

use App\Models\Concerns\HasFrench;
use App\Support\ContentTokens;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'title', 'title_fr', 'intro', 'intro_fr', 'body', 'body_fr', 'meta_description', 'meta_description_fr'])]
class Page extends Model
{
    use HasFrench;

    public static function for(string $key): ?self
    {
        return static::query()->where('key', $key)->first();
    }

    /** Body HTML in the current language, sanitized, with [phone] / [email] filled in. */
    public function renderedBody(): string
    {
        return app(ContentTokens::class)->render($this->localized('body'));
    }
}
