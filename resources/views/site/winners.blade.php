@extends('layouts.site', ['title' => $page?->localized('title') ?? __('Winners')])

@section('content')
    @include('partials.page-hero', ['eyebrow' => __('Winners'), 'heading' => $page?->localized('title') ?? __('Winners'), 'intro' => $page?->localized('intro')])

    <div class="wrap page-body">
        @if ($page?->localized('body'))<div class="prose">{!! $page->renderedBody() !!}</div>@endif

        @if ($winners->isEmpty())
            <p class="empty">{{ __('Our latest winners will appear here.') }}</p>
        @else
            <div class="winners" style="margin-top:0">
                @foreach ($winners as $winner)
                    @include('partials.winner-card')
                @endforeach
            </div>
            {{ $winners->links('partials.pager') }}
        @endif
    </div>
@endsection
