@extends('layouts.site', ['title' => $page?->localized('title') ?? __('Game Schedule'), 'reloadOnLiveChange' => true])

@section('content')
    @include('partials.page-hero', ['eyebrow' => __('Every second Tuesday'), 'heading' => $page?->localized('title') ?? __('Game Schedule'), 'intro' => $page?->localized('intro')])

    <div class="wrap page-body">
        @if ($page?->localized('body'))<div class="prose">{!! $page->renderedBody() !!}</div>@endif

        <div class="games">
            @forelse ($games as $game)
                @include('partials.game-row', ['showPrizes' => true])
            @empty
                <p class="empty">{{ __('The next games will be posted soon. Check back shortly!') }}</p>
            @endforelse
        </div>
    </div>
@endsection
