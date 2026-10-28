@extends('layouts.site', ['title' => 'Watch Live', 'reloadOnLiveChange' => true])

@section('content')
@php
    $embed = \App\Support\YouTube::embedUrl($settings->get('stream_url'), $settings->get('youtube_channel_id'));
    $channel = $settings->get('youtube_channel_url');
    $phone = $settings->get('business_phone');
    $next = $live['next_game'];
@endphp
    @include('partials.page-hero', ['eyebrow' => $live['live'] ? 'Live now' : 'Watch Live', 'heading' => $page?->title ?? 'Watch Live', 'intro' => $page?->intro])

    <div class="wrap page-body">
        @if ($live['live'] && $embed)
            <div class="player">
                <iframe src="{{ $embed }}" title="Riverbank Bingo live broadcast" allow="autoplay; encrypted-media; picture-in-picture; fullscreen" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
            </div>
            @if ($channel)<p style="margin-top:12px">Stream not loading? <a href="{{ $channel }}" target="_blank" rel="noopener">Watch on YouTube</a>.</p>@endif
        @elseif ($live['live'])
            <div class="offline">
                <h2>We're live on YouTube</h2>
                <p>The game is in session right now.</p>
                @if ($channel)<p><a class="btn btn-hot" href="{{ $channel }}" target="_blank" rel="noopener">Watch on YouTube</a></p>@endif
            </div>
        @else
            <div class="offline">
                <h2>We're not live right now</h2>
                <p>{{ $settings->get('offline_message') }}</p>
                @if ($next)
                    <p style="font-weight:700">Next game: {{ $next->starts_at->isoFormat('dddd, MMMM D') }} at {{ $next->starts_at->format('g:i A') }} ET</p>
                    @include('partials.countdown')
                @endif
                <p style="margin-top:22px"><a class="btn btn-gold" href="{{ route('schedule') }}">See the schedule</a></p>
            </div>
        @endif

        @if ($phone)
            <div class="card hotline">
                <div>
                    <div class="eyebrow">Have BINGO?</div>
                    <b>Call {{ $phone }}</b>
                </div>
                <a class="btn btn-hot" href="{{ $settings->phoneHref() }}">Call the hotline</a>
            </div>
        @endif

        @if ($page?->body)<div class="prose" style="margin-top:36px">{!! $page->renderedBody() !!}</div>@endif
    </div>
@endsection
