@extends('layouts.site')

@section('content')
@php
    $next = $live['next_game'];
    $phone = $settings->get('business_phone');
    $progressives = collect($settings->get('progressives', []))->filter(fn ($p) => ! empty($p['name']));
@endphp

<div class="hero">
    {{-- Normal hero: countdown and the animated flashboard. --}}
    <section class="wrap-wide hero-grid when-offline" aria-labelledby="hero-title">
        <div>
            <div class="hero-flag"><span class="dot" aria-hidden="true"></span>
                {{ $next ? 'Next game · '.$next->starts_at->isoFormat('dddd, MMMM D') : 'Play at home' }}
            </div>
            <h1 id="hero-title">{{ $page?->title ?? 'Bingo night, at your table.' }}</h1>
            @if ($page?->intro)<p class="lede">{{ $page->intro }}</p>@endif

            @if ($next)
                @include('partials.countdown')
                <p class="next-when">{{ $next->starts_at->isoFormat('dddd, MMMM D') }} at {{ $next->starts_at->format('g:i A') }} ET, live on YouTube</p>
            @endif

            <div class="hero-actions">
                <a class="btn btn-hot" href="{{ route('page', 'how-to-play') }}">How to Play</a>
                <a class="btn btn-ghost-light" href="{{ route('distributors') }}">Find Cards</a>
            </div>
        </div>

        <div class="flashboard" data-flashboard aria-hidden="true">
            <div class="cage">
                <div class="cage-globe" data-cage></div>
                <div class="cage-ring"></div>
                <div class="cage-logo"><img src="{{ asset('img/logo-badge-colour.png') }}" alt=""></div>
                <div class="ball ball-current ball-N"><small>N</small><b>42</b></div>
            </div>
            <div class="board-panel">
                <div class="label"><span>Flashboard</span><span data-call-count></span></div>
                <div class="board"></div>
            </div>
        </div>
    </section>

    {{-- Live hero: replaces the flashboard while a game is in session. --}}
    <section class="wrap hero-live when-live" aria-label="Game in session">
        <div class="live-flag"><span class="live-dot" aria-hidden="true"></span> Live now</div>
        <h1>Our game is currently in session</h1>
        <p class="lede">Grab your cards and join the live broadcast. Have BINGO? Call the hotline right away and we'll verify your card on air.</p>
        <div class="hero-actions">
            <a class="btn btn-hot" href="{{ route('watch') }}">Watch Live</a>
            @if ($phone)<a class="btn btn-ghost-light" href="{{ $settings->phoneHref() }}">Call {{ $phone }}</a>@endif
        </div>
    </section>

    <div class="ticker" aria-hidden="true">
        <div class="ticker-track">
            @foreach ([1, 2] as $copy)
                @foreach (['Live on YouTube every second Tuesday', 'Paper cards from local distributors', 'Winners verified live on air', 'Payouts by e-Transfer', 'Proudly Kahnawake', '18+ approved adult play'] as $line)
                    <span>{{ $line }}</span>
                @endforeach
            @endforeach
        </div>
    </div>
</div>

@if ($progressives->isNotEmpty())
    <section class="wrap section" aria-labelledby="jackpots-title">
        <div class="section-head">
            <div class="eyebrow">Growing every game</div>
            <h2 id="jackpots-title">Progressive jackpots</h2>
        </div>
        <div class="jackpots">
            @foreach ($progressives as $progressive)
                <div class="card jackpot">
                    <small>{{ $progressive['name'] }}</small>
                    <b>${{ number_format((float) ($progressive['amount'] ?? 0)) }}</b>
                </div>
            @endforeach
        </div>
    </section>
@endif

<section class="wrap section" aria-labelledby="how-title">
    <div class="section-head">
        <div class="eyebrow">How to play at home</div>
        <h2 id="how-title">Three steps, then play from your own table.</h2>
        <p class="lede">No account to create, no app to install. Pick up cards, tune in to the live broadcast, and call us the moment you have BINGO.</p>
    </div>
    <div class="steps">
        <div class="card step">
            <div class="step-num">1</div>
            <h3>Get your cards</h3>
            <p>Pick up paper cards from a community distributor, or ask about contactless delivery. Card sales close before the game starts.</p>
            <a href="{{ route('distributors') }}">Find cards →</a>
        </div>
        <div class="card step">
            <div class="step-num">2</div>
            <h3>Tune in live</h3>
            <p>Watch the broadcast at game time. Every number is called on screen with colour-coded cues that match your cards.</p>
            <a href="{{ route('watch') }}">Watch live →</a>
        </div>
        <div class="card step">
            <div class="step-num">3</div>
            <h3>Have BINGO? Call us</h3>
            <p>Call the hotline right away. We check your card against the calls and confirm your win live on air.</p>
            @if ($phone)<a href="{{ $settings->phoneHref() }}">{{ $phone }} →</a>@endif
        </div>
    </div>
</section>

<section class="wrap section" aria-labelledby="cards-title">
    <div class="split">
        <div>
            <div class="eyebrow" style="color:#DF3F2E">Find cards</div>
            <h2 id="cards-title" style="font-size:clamp(30px,3.6vw,44px);margin:14px 0 0">Real paper cards, from people down the road.</h2>
            <p class="lede">Grab your cards from a trusted community distributor. Further out? We deliver to Île-Perrot, Coteau-du-Lac and parts of New York.</p>
            @if ($next?->card_sales_close_at)
                <div class="notice" style="margin-top:24px"><span class="glow" aria-hidden="true"></span>
                    Cards available until {{ $next->card_sales_close_at->format('g:i A') }} ET on game day
                </div>
            @endif
            <div class="hero-actions" style="margin-top:24px">
                @if ($phone)<a class="btn btn-hot" href="{{ $settings->phoneHref() }}">Call to arrange cards</a>@endif
                <a class="btn btn-ghost" href="{{ route('distributors') }}">See all distributors</a>
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
            <div class="eyebrow" style="color:#1E78AE">Upcoming games</div>
            <h2 id="games-title">Pick a night.</h2>
        </div>
        <a href="{{ route('schedule') }}" style="font-weight:700">Full schedule and prizes →</a>
    </div>
    <div class="games">
        @forelse ($games as $game)
            @include('partials.game-row')
        @empty
            <p class="empty">The next games will be posted soon.</p>
        @endforelse
    </div>
</section>

@if ($winners->isNotEmpty())
    <section class="wrap section" aria-labelledby="winners-title">
        <div class="section-head">
            <div class="eyebrow" style="color:#7048B0">Winners</div>
            <h2 id="winners-title">Real cards, real calls, real neighbours.</h2>
        </div>
        <div class="winners">
            @foreach ($winners as $winner)
                @include('partials.winner-card')
            @endforeach
        </div>
    </section>
@endif
@endsection
