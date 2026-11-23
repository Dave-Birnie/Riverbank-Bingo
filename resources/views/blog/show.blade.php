@extends('layouts.site', ['title' => $post->localized('title'), 'description' => $post->localized('excerpt')])

@section('content')
@php
    use App\Support\ContentTokens;
    use App\Support\Format;
@endphp
    @include('partials.page-hero', [
        'eyebrow' => $post->published_at && ! $post->isScheduled() ? Format::date($post->published_at) : __('Preview — not published yet'),
        'heading' => $post->localized('title'),
        'intro' => $post->localized('excerpt'),
    ])

    <div class="wrap page-body">
        @if ($post->cover_image_path)
            <img src="{{ Storage::disk('public')->url($post->cover_image_path) }}" alt="" style="border-radius:var(--radius);margin-bottom:28px;max-height:460px;width:100%;object-fit:cover">
        @endif
        <div class="prose">{!! app(ContentTokens::class)->render($post->localized('body')) !!}</div>
        <p style="margin-top:36px"><a href="{{ lroute('blog') }}">← {{ __('All news') }}</a></p>
    </div>
@endsection
