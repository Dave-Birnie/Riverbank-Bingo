@extends('layouts.site', ['title' => $page?->localized('title') ?? __('Watch Live'), 'reloadOnLiveChange' => true])

@section('content')
@php
    use App\Support\Format;
    use App\Support\YouTube;

    $embed = YouTube::embedUrl($settings->get('stream_url'), $settings->get('youtube_channel_id'));
    $channel = $settings->get('youtube_channel_url');
    $phone = $settings->get('business_phone');
    $next = $live['next_game'];
@endphp
    @include('partials.page-hero', ['eyebrow' => $live['live'] ? __('Live now') : __('Watch Live'), 'heading' => $page?->localized('title') ?? __('Watch Live'), 'intro' => $page?->localized('intro')])

    <div class="wrap page-body">
        @if ($live['live'] && $embed)
            <div class="player">
                <iframe src="{{ $embed }}" title="{{ __('Riverbank Bingo live broadcast') }}" allow="autoplay; encrypted-media; picture-in-picture; fullscreen" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
            </div>
            @if ($channel)<p style="margin-top:12px">{{ __('Stream not loading?') }} <a href="{{ $channel }}" target="_blank" rel="noopener">{{ __('Watch on YouTube') }}</a>.</p>@endif
        @elseif ($live['live'])
            <div class="offline">
                <h2>{{ __("We're live on YouTube") }}</h2>
                <p>{{ __('The game is in session right now.') }}</p>
                @if ($channel)<p><a class="btn btn-hot" href="{{ $channel }}" target="_blank" rel="noopener">{{ __('Watch on YouTube') }}</a></p>@endif
            </div>
        @else
            <div class="offline">
                <h2>{{ __("We're not live right now") }}</h2>
                <p>{{ $settings->localized('offline_message') }}</p>
                @if ($next)
                    <p style="font-weight:600">{{ __('Next game: :day at :time', ['day' => Format::day($next->starts_at), 'time' => Format::time($next->starts_at)]) }}</p>
                    @include('partials.countdown')
                @endif
                <p style="margin-top:22px"><a class="btn btn-hot" href="{{ lroute('schedule') }}">{{ __('See the schedule') }}</a></p>
            </div>
        @endif

        @if ($phone)
            <div class="card hotline">
                <div>
                    <div class="eyebrow">{{ __('Have BINGO?') }}</div>
                    <b>{{ __('Call :phone', ['phone' => $phone]) }}</b>
                </div>
                <a class="btn btn-hot" href="{{ $settings->phoneHref() }}">{{ __('Call the hotline') }}</a>
            </div>
        @endif

        @if ($page?->localized('body'))<div class="prose" style="margin-top:36px">{!! $page->renderedBody() !!}</div>@endif
    </div>
@endsection
