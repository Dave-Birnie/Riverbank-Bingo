@extends('layouts.site', ['title' => $page?->localized('title') ?? __('News')])

@section('content')
@php use App\Support\Format; @endphp
    @include('partials.page-hero', ['eyebrow' => __('News'), 'heading' => $page?->localized('title') ?? __('News'), 'intro' => $page?->localized('intro')])

    <div class="wrap page-body">
        @if ($posts->isEmpty())
            <p class="empty">{{ __('No news yet.') }}</p>
        @else
            <div class="posts">
                @foreach ($posts as $post)
                    <a class="card post-card" href="{{ lroute('blog.show', $post) }}">
                        @if ($post->cover_image_path)
                            <img src="{{ Storage::disk('public')->url($post->cover_image_path) }}" alt="" loading="lazy">
                        @endif
                        <div class="body">
                            <div class="meta">{{ Format::date($post->published_at) }}</div>
                            <h2>{{ $post->localized('title') }}</h2>
                            @if ($post->localized('excerpt'))<p>{{ $post->localized('excerpt') }}</p>@endif
                        </div>
                    </a>
                @endforeach
            </div>
            {{ $posts->links('partials.pager') }}
        @endif
    </div>
@endsection
