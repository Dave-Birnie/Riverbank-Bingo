<?php

namespace App\Models;

use App\Support\ContentTokens;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'title', 'intro', 'body', 'meta_description'])]
class Page extends Model
{
    public static function for(string $key): ?self
    {
        return static::query()->where('key', $key)->first();
    }

    /** Body HTML, sanitized, with [phone] / [email] filled in from Settings. */
    public function renderedBody(): string
    {
        return app(ContentTokens::class)->render($this->body);
    }
}
