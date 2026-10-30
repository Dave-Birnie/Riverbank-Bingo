@php
    use App\Support\Locales;

    $phone = $settings->get('business_phone');
    $nextGame = $live['next_game'];
    $locale = app()->getLocale();
    $otherLocale = $locale === 'fr' ? 'en' : 'fr';
    $nav = [
        'schedule' => __('Schedule'),
        'watch' => __('Watch Live'),
        'distributors' => __('Find Cards'),
        'winners' => __('Winners'),
        'blog' => __('News'),
        'page.about' => __('About'),
        'contact' => __('Contact'),
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ Locales::htmlLang() }}"
      data-theme="{{ $theme }}"
      data-live="{{ $live['live'] ? 1 : 0 }}"
      data-status-url="{{ route('status') }}"
      data-next-game="{{ $nextGame?->starts_at->toIso8601String() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' · Riverbank Bingo' : 'Riverbank Bingo · '.__('Live-streamed bingo from Kahnawake') }}</title>
    <meta name="description" content="{{ $description ?? __("Kahnawake's live-streamed bingo. Pick up your cards, tune in to the live broadcast every second Tuesday, and call in your BINGO.") }}">
    @unless (app()->environment('production'))
        <meta name="robots" content="noindex, nofollow">
    @endunless
    @foreach ($alternates as $altLocale => $url)
        <link rel="alternate" hreflang="{{ $altLocale }}-CA" href="{{ $url }}">
    @endforeach
    <meta name="theme-color" content="{{ $theme === 'classic' ? '#0B2A3A' : '#08080A' }}">
    <link rel="icon" href="{{ asset('img/favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('img/logo-badge-gold.png') }}">
    <link rel="preload" href="{{ asset($theme === 'classic' ? 'fonts/bevan-400-normal-latin.woff2' : 'fonts/playfair-display-700-normal-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ filemtime(public_path('css/site.css')) }}">
    @stack('head')
</head>
<body @if ($reloadOnLiveChange ?? false) data-reload-on-live-change @endif>
<a class="skip" href="#main">{{ __('Skip to content') }}</a>

<div class="utility">
    <span class="pill">18+</span>
    <span class="hide-sm">{{ __('Approved adult play only') }}</span>
    <span class="dot hide-sm">·</span>
    <a href="{{ lroute('page.rules') }}">{{ __('Play responsibly') }}</a>
    @if ($phone)
        <span class="dot">·</span>
        <a href="{{ $settings->phoneHref() }}">{{ __('Bingo hotline') }} {{ $phone }}</a>
    @endif
    <span class="dot">·</span>
    <a class="lang" href="{{ $alternates[$otherLocale] }}" hreflang="{{ $otherLocale }}" lang="{{ $otherLocale }}">{{ $otherLocale === 'fr' ? 'Français' : 'English' }}</a>
</div>

<header class="site-header">
    <div class="wrap-wide headrow">
        <a href="{{ lroute('home') }}" class="brand" aria-label="{{ __('Riverbank Bingo home') }}">
            <img class="badge" src="{{ asset('img/logo-badge-gold.png') }}" alt="" width="56" height="56">
            <img class="wordmark" src="{{ asset('img/wordmark.png') }}" alt="Riverbank Bingo" height="30">
        </a>
        <nav class="site-nav" aria-label="{{ __('Main') }}">
            @foreach ($nav as $route => $label)
                @php $url = lroute($route); @endphp
                <a href="{{ $url }}" @if (url()->current() === $url) aria-current="page" @endif>
                    @if ($route === 'watch')<span class="live-dot" aria-hidden="true"></span>@endif
                    {{ $label }}
                </a>
            @endforeach
        </nav>
        <a class="btn btn-hot when-offline" href="{{ lroute('page.how-to-play') }}">{{ __('How to Play') }}</a>
        <a class="btn btn-hot when-live" href="{{ lroute('watch') }}">{{ __('Watch Live') }}</a>
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
            <p>{{ __("Kahnawake's live-streamed bingo. Paper cards from local distributors, a live broadcast every second Tuesday, and winners verified on air.") }}</p>
        </div>
        <div>
            <div class="footer-head">{{ __('Play') }}</div>
            <div class="footer-links">
                <a href="{{ lroute('schedule') }}">{{ __('Game schedule') }}</a>
                <a href="{{ lroute('watch') }}">{{ __('Watch live') }}</a>
                <a href="{{ lroute('page.how-to-play') }}">{{ __('How to play') }}</a>
                <a href="{{ lroute('distributors') }}">{{ __('Find cards') }}</a>
            </div>
        </div>
        <div>
            <div class="footer-head">Riverbank</div>
            <div class="footer-links">
                <a href="{{ lroute('page.about') }}">{{ __('About us') }}</a>
                <a href="{{ lroute('winners') }}">{{ __('Winners') }}</a>
                <a href="{{ lroute('blog') }}">{{ __('News') }}</a>
                <a href="{{ lroute('contact') }}">{{ __('Contact us') }}</a>
            </div>
        </div>
        <div>
            <div class="footer-head">{{ __('Help') }}</div>
            <div class="footer-links">
                @if ($phone)<a href="{{ $settings->phoneHref() }}">{{ $phone }}</a>@endif
                <a href="{{ lroute('page.rules') }}">{{ __('Rules') }}</a>
                <a href="{{ lroute('page.privacy') }}">{{ __('Privacy policy') }}</a>
                <a href="{{ lroute('page.become-a-distributor') }}">{{ __('Become a distributor') }}</a>
            </div>
        </div>
    </div>
    <div class="wrap footer-base">
        <span>© {{ now()->year }} Riverbank Bingo. {{ __('Approved adult play only, 18+.') }}</span>
        <span>Kahnawake, {{ __('Quebec') }}</span>
    </div>
</footer>

<script src="{{ asset('js/site.js') }}?v={{ filemtime(public_path('js/site.js')) }}" defer></script>
@stack('scripts')
</body>
</html>
