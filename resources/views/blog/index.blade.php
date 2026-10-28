@extends('layouts.site', ['title' => 'News'])

@section('content')
    @include('partials.page-hero', ['eyebrow' => 'News', 'heading' => $page?->title ?? 'News', 'intro' => $page?->intro])

    <div class="wrap page-body">
        @if ($posts->isEmpty())
            <p class="empty">No posts yet.</p>
        @else
            <div class="posts">
                @foreach ($posts as $post)
                    <a class="card post-card" href="{{ route('blog.show', $post) }}">
                        @if ($post->cover_image_path)
                            <img src="{{ Storage::disk('public')->url($post->cover_image_path) }}" alt="" loading="lazy">
                        @endif
                        <div class="body">
                            <div class="meta">{{ $post->published_at->isoFormat('MMMM D, YYYY') }}</div>
                            <h2>{{ $post->title }}</h2>
                            @if ($post->excerpt)<p>{{ $post->excerpt }}</p>@endif
                        </div>
                    </a>
                @endforeach
            </div>
            <div style="margin-top:32px">{{ $posts->links() }}</div>
        @endif
    </div>
@endsection
