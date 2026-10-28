@extends('layouts.site', ['title' => $page->title, 'description' => $page->meta_description ?: $page->intro])

@section('content')
    @include('partials.page-hero', ['heading' => $page->title, 'intro' => $page->intro])

    <div class="wrap page-body">
        <div class="prose">{!! $page->renderedBody() !!}</div>

        @if ($page->key === 'become-a-distributor')
            <p style="margin-top:28px"><a class="btn btn-hot" href="{{ route('contact', ['topic' => 'distributor']) }}">Apply to become a distributor</a></p>
        @endif
    </div>
@endsection
