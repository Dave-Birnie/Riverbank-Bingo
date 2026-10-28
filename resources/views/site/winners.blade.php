@extends('layouts.site', ['title' => 'Winners'])

@section('content')
    @include('partials.page-hero', ['eyebrow' => 'Winners', 'heading' => $page?->title ?? 'Winners', 'intro' => $page?->intro])

    <div class="wrap page-body">
        @if ($page?->body)<div class="prose">{!! $page->renderedBody() !!}</div>@endif

        @if ($winners->isEmpty())
            <p class="empty">Our latest winners will appear here.</p>
        @else
            <div class="winners" style="margin-top:0">
                @foreach ($winners as $winner)
                    @include('partials.winner-card')
                @endforeach
            </div>
            <div style="margin-top:32px">{{ $winners->links() }}</div>
        @endif
    </div>
@endsection
