@extends('layouts.site')

@section('content')
@php
    use App\Support\Format;
    use App\Support\Locales;

    $next = $live['next_game'];
    $phone = $settings->get('business_phone');
    $progressives = collect($settings->get('progressives', []))->filter(fn ($p) => ! empty($p['name']));
    $deliveryAreas = collect(preg_split('/\r?\n/', (string) $settings->localized('delivery_areas')))->map('trim')->filter();
@endphp

<div class="hero">
    {{-- Normal hero: countdown and the animated flashboard. --}}
    <section class="wrap-wide hero-grid when-offline" aria-labelledby="hero-title">
        <div>
            <div class="hero-flag"><span class="dot" aria-hidden="true"></span>
                {{ $next ? __('Next game').' · '.Format::day($next->starts_at) : __('Play at home') }}
            </div>
            <h1 id="hero-title">{{ $page?->localized('title') ?? __('Bingo night, at your table.') }}</h1>
            @if ($page?->localized('intro'))<p class="lede">{{ $page->localized('intro') }}</p>@endif

            @if ($next)
                @include('partials.countdown')
                <p class="next-when">{{ __(':day at :time, live on YouTube', ['day' => Format::day($next->starts_at), 'time' => Format::time($next->starts_at)]) }}</p>
            @endif

            <div class="hero-actions">
                <a class="btn btn-hot" href="{{ lroute('page.how-to-play') }}">{{ __('How to Play') }}</a>
                <a class="btn btn-ghost-light" href="{{ lroute('distributors') }}">{{ __('Find Cards') }}</a>
            </div>
        </div>

        <div class="flashboard" data-flashboard data-called-label="{{ __(':count called') }}" aria-hidden="true">
            <div class="cage">
                <div class="cage-globe" data-cage></div>
                <div class="cage-ring"></div>
                <div class="cage-logo"><img src="{{ asset($theme === 'classic' ? 'img/logo-badge-colour.png' : 'img/logo-badge-gold.png') }}" alt=""></div>
                <div class="ball ball-current ball-N"><small>N</small><b>42</b></div>
            </div>
            <div class="board-panel">
                <div class="label"><span>{{ __('Flashboard') }}</span><span data-call-count></span></div>
                <div class="board"></div>
            </div>
        </div>
    </section>

    {{-- Live hero: replaces the flashboard while a game is in session. --}}
    <section class="wrap hero-live when-live" aria-label="{{ __('Game in session') }}">
        <div class="live-flag"><span class="live-dot" aria-hidden="true"></span> {{ __('Live now') }}</div>
        <h1>{{ __('Our game is currently in session') }}</h1>
        <p class="lede">{{ __("Grab your cards and join the live broadcast. Have BINGO? Call the hotline right away and we'll verify your card on air.") }}</p>
        <div class="hero-actions">
            <a class="btn btn-hot" href="{{ lroute('watch') }}">{{ __('Watch Live') }}</a>
            @if ($phone)<a class="btn btn-ghost-light" href="{{ $settings->phoneHref() }}">{{ __('Call :phone', ['phone' => $phone]) }}</a>@endif
        </div>
    </section>

    <div class="ticker" aria-hidden="true">
        <div class="ticker-track">
            @foreach ([1, 2] as $copy)
                @foreach ([__('Live on YouTube every second Tuesday'), __('Paper cards from local distributors'), __('Winners verified live on air'), __('Payouts by e-Transfer'), __('Proudly Kahnawake'), __('18+ approved adult play')] as $line)
                    <span>{{ $line }}</span>
                @endforeach
            @endforeach
        </div>
    </div>
</div>

@if ($progressives->isNotEmpty())
    <section class="wrap section" aria-labelledby="jackpots-title">
        <div class="section-head">
            <div class="eyebrow">{{ __('Growing every game') }}</div>
            <h2 id="jackpots-title">{{ __('Progressive jackpots') }}</h2>
        </div>
        <div class="jackpots">
            @foreach ($progressives as $progressive)
                <div class="card jackpot">
                    <small>{{ Locales::item($progressive, 'name') }}</small>
                    <b>{{ Format::money($progressive['amount'] ?? 0) }}</b>
                </div>
            @endforeach
        </div>
    </section>
@endif

<section class="wrap section" aria-labelledby="how-title">
    <div class="section-head">
        <div class="eyebrow">{{ __('How to play at home') }}</div>
        <h2 id="how-title">{{ __('Three steps, then play from your own table.') }}</h2>
        <p class="lede">{{ __('No account to create, no app to install. Pick up cards, tune in to the live broadcast, and call us the moment you have BINGO.') }}</p>
    </div>
    <div class="steps">
        <div class="card step">
            <div class="step-num" aria-hidden="true"></div>
            <h3>{{ __('Get your cards') }}</h3>
            <p>{{ __('Pick up paper cards from a community distributor, or ask about contactless delivery. Card sales close before the game starts.') }}</p>
            <a href="{{ lroute('distributors') }}">{{ __('Find cards') }} →</a>
        </div>
        <div class="card step">
            <div class="step-num" aria-hidden="true"></div>
            <h3>{{ __('Tune in live') }}</h3>
            <p>{{ __('Watch the broadcast at game time. Every number is called on screen with colour-coded cues that match your cards.') }}</p>
            <a href="{{ lroute('watch') }}">{{ __('Watch live') }} →</a>
        </div>
        <div class="card step">
            <div class="step-num" aria-hidden="true"></div>
            <h3>{{ __('Have BINGO? Call us') }}</h3>
            <p>{{ __('Call the hotline right away. We check your card against the calls and confirm your win live on air.') }}</p>
            @if ($phone)<a href="{{ $settings->phoneHref() }}">{{ $phone }} →</a>@endif
        </div>
    </div>
</section>

<section class="wrap section" aria-labelledby="cards-title">
    <div class="split">
        <div>
            <div class="eyebrow">{{ __('Find cards') }}</div>
            <h2 id="cards-title" class="section-title">{{ __('Real paper cards, from people down the road.') }}</h2>
            <p class="lede">
                {{ __('Grab your cards from a trusted community distributor.') }}
                @if ($deliveryAreas->isNotEmpty()){{ __('Further out? We deliver to :areas.', ['areas' => $deliveryAreas->implode(', ')]) }}@endif
            </p>
            @if ($next?->card_sales_close_at)
                <div class="notice" style="margin-top:24px"><span class="glow" aria-hidden="true"></span>
                    {{ __('Cards available until :time on game day', ['time' => Format::time($next->card_sales_close_at)]) }}
                </div>
            @endif
            <div class="hero-actions" style="margin-top:24px">
                @if ($phone)<a class="btn btn-hot" href="{{ $settings->phoneHref() }}">{{ __('Call to arrange cards') }}</a>@endif
                <a class="btn btn-ghost" href="{{ lroute('distributors') }}">{{ __('See all distributors') }}</a>
            </div>
        </div>
        <div>
            @include('partials.map', ['distributors' => $distributors])
            <div class="list" style="margin-top:14px">
                @foreach ($distributors->take(3) as $distributor)
                    @include('partials.distributor-row')
                @endforeach
            </div>
        </div>
    </div>
</section>

<section class="wrap section" aria-labelledby="games-title">
    <div class="section-row">
        <div>
            <div class="eyebrow">{{ __('Upcoming games') }}</div>
            <h2 id="games-title">{{ __('Pick a night.') }}</h2>
        </div>
        <a href="{{ lroute('schedule') }}" style="font-weight:600">{{ __('Full schedule and prizes') }} →</a>
    </div>
    <div class="games">
        @forelse ($games as $game)
            @include('partials.game-row')
        @empty
            <p class="empty">{{ __('The next games will be posted soon.') }}</p>
        @endforelse
    </div>
</section>

@if ($winners->isNotEmpty())
    <section class="wrap section" aria-labelledby="winners-title">
        <div class="section-head">
            <div class="eyebrow">{{ __('Winners') }}</div>
            <h2 id="winners-title">{{ __('Real cards, real calls, real neighbours.') }}</h2>
        </div>
        <div class="winners">
            @foreach ($winners as $winner)
                @include('partials.winner-card')
            @endforeach
        </div>
    </section>
@endif
@endsection
