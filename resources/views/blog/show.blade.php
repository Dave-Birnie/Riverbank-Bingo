@extends('layouts.site', ['title' => $post->title, 'description' => $post->excerpt])

@section('content')
    @include('partials.page-hero', [
        'eyebrow' => $post->published_at && ! $post->isScheduled() ? $post->published_at->isoFormat('MMMM D, YYYY') : 'Preview — not published yet',
        'heading' => $post->title,
        'intro' => $post->excerpt,
    ])

    <div class="wrap page-body">
        @if ($post->cover_image_path)
            <img src="{{ Storage::disk('public')->url($post->cover_image_path) }}" alt="" style="border-radius:20px;margin-bottom:28px;max-height:460px;width:100%;object-fit:cover">
        @endif
        <div class="prose">{!! app(\App\Support\ContentTokens::class)->render($post->body) !!}</div>
        <p style="margin-top:36px"><a href="{{ route('blog') }}">← All news</a></p>
    </div>
@endsection
