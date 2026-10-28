@php
    $phone = $settings->get('business_phone');
    $nextGame = $live['next_game'];
    $nav = [
        'schedule' => 'Schedule',
        'watch' => 'Watch Live',
        'distributors' => 'Find Cards',
        'winners' => 'Winners',
        'blog' => 'News',
        'about' => 'About',
        'contact' => 'Contact',
    ];
@endphp
<!DOCTYPE html>
<html lang="en"
      data-theme="{{ $theme }}"
      data-live="{{ $live['live'] ? 1 : 0 }}"
      data-status-url="{{ route('status') }}"
      data-next-game="{{ $nextGame?->starts_at->toIso8601String() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' · Riverbank Bingo' : 'Riverbank Bingo · Live-streamed bingo from Kahnawake' }}</title>
    <meta name="description" content="{{ $description ?? "Kahnawake's live-streamed bingo. Pick up your cards, tune in to the live broadcast every second Tuesday, and call in your BINGO." }}">
    @unless (app()->environment('production'))
        <meta name="robots" content="noindex, nofollow">
    @endunless
    <meta name="theme-color" content="#0B2A3A">
    <link rel="icon" href="{{ asset('img/favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('img/logo-badge-gold.png') }}">
    <link rel="preload" href="{{ asset('fonts/bevan-400-normal-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ filemtime(public_path('css/site.css')) }}">
    @stack('head')
</head>
<body @if ($reloadOnLiveChange ?? false) data-reload-on-live-change @endif>
<a class="skip" href="#main">Skip to content</a>

<div class="utility">
    <span class="pill">18+</span>
    <span>Approved adult play only</span>
    <span class="dot">·</span>
    <a href="{{ route('page', 'rules') }}">Play responsibly</a>
    @if ($phone)
        <span class="dot">·</span>
        <a href="{{ $settings->phoneHref() }}">Bingo hotline {{ $phone }}</a>
    @endif
</div>

<header class="site-header">
    <div class="wrap-wide headrow">
        <a href="{{ route('home') }}" class="brand" aria-label="Riverbank Bingo home">
            <img class="badge" src="{{ asset('img/logo-badge-gold.png') }}" alt="" width="56" height="56">
            <img class="wordmark" src="{{ asset('img/wordmark.png') }}" alt="Riverbank Bingo" height="30">
        </a>
        <nav class="site-nav" aria-label="Main">
            @foreach ($nav as $route => $label)
                @php $url = in_array($route, ['about']) ? route('page', $route) : route($route); @endphp
                <a href="{{ $url }}" @if (url()->current() === $url) aria-current="page" @endif>
                    @if ($route === 'watch')<span class="live-dot" aria-hidden="true"></span>@endif
                    {{ $label }}
                </a>
            @endforeach
        </nav>
        <a class="btn btn-hot when-offline" href="{{ route('page', 'how-to-play') }}">How to Play</a>
        <a class="btn btn-hot when-live" href="{{ route('watch') }}" data-live-cta>Watch Live</a>
    </div>
    @if ($bannerItems->isNotEmpty())
        @include('partials.banner', ['items' => $bannerItems])
    @endif
</header>

<main id="main">
    @yield('content')
</main>

<footer class="site-footer">
    <div class="wrap footer-grid">
        <div>
            <img src="{{ asset('img/logo-badge-gold.png') }}" alt="Riverbank Bingo" width="96" height="96">
            <p>Kahnawake's live-streamed bingo. Paper cards from local distributors, a live broadcast every second Tuesday, and winners verified on air.</p>
        </div>
        <div>
            <div class="footer-head">Play</div>
            <div class="footer-links">
                <a href="{{ route('schedule') }}">Game schedule</a>
                <a href="{{ route('watch') }}">Watch live</a>
                <a href="{{ route('page', 'how-to-play') }}">How to play</a>
                <a href="{{ route('distributors') }}">Find cards</a>
            </div>
        </div>
        <div>
            <div class="footer-head">Riverbank</div>
            <div class="footer-links">
                <a href="{{ route('page', 'about') }}">About us</a>
                <a href="{{ route('winners') }}">Winners</a>
                <a href="{{ route('blog') }}">News</a>
                <a href="{{ route('contact') }}">Contact us</a>
            </div>
        </div>
        <div>
            <div class="footer-head">Help</div>
            <div class="footer-links">
                @if ($phone)<a href="{{ $settings->phoneHref() }}">{{ $phone }}</a>@endif
                <a href="{{ route('page', 'rules') }}">Rules</a>
                <a href="{{ route('page', 'privacy') }}">Privacy policy</a>
                <a href="{{ route('page', 'become-a-distributor') }}">Become a distributor</a>
            </div>
        </div>
    </div>
    <div class="wrap footer-base">
        <span>© {{ now()->year }} Riverbank Bingo. Approved adult play only, 18+.</span>
        <span>Kahnawake, Quebec</span>
    </div>
</footer>

<script src="{{ asset('js/site.js') }}?v={{ filemtime(public_path('js/site.js')) }}" defer></script>
@stack('scripts')
</body>
</html>
