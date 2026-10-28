@extends('layouts.site', ['title' => 'Game Schedule', 'reloadOnLiveChange' => true])

@section('content')
    @include('partials.page-hero', ['eyebrow' => 'Every second Tuesday', 'heading' => $page?->title ?? 'Game Schedule', 'intro' => $page?->intro])

    <div class="wrap page-body">
        @if ($page?->body)<div class="prose">{!! $page->renderedBody() !!}</div>@endif

        <div class="games">
            @forelse ($games as $game)
                @include('partials.game-row', ['showPrizes' => true])
            @empty
                <p class="empty">The next games will be posted soon. Check back shortly!</p>
            @endforelse
        </div>
    </div>
@endsection
