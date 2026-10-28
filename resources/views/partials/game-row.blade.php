@php
    $isLiveGame = $live['live'] && $live['game']?->is($game);
@endphp
<article class="card game @if ($game->isCancelled()) is-cancelled @endif">
    <div class="game-date">
        <small>{{ $game->starts_at->isoFormat('MMM') }}</small>
        <b>{{ $game->starts_at->day }}</b>
    </div>
    <div>
        <div class="game-title">{{ $game->title }}</div>
        <div class="game-detail">
            {{ $game->starts_at->isoFormat('dddd') }} · {{ $game->starts_at->format('g:i A') }} ET
            @if ($game->card_sales_close_at && ! $game->isCancelled())
                · cards close {{ $game->card_sales_close_at->format('g:i A') }}
            @endif
        </div>
        @if ($game->isCancelled())
            <div class="cancel-note">Cancelled{{ $game->cancellation_note ? ': '.$game->cancellation_note : '' }}</div>
        @elseif (($showPrizes ?? false) && ! empty($game->prizes))
            <ul class="prizes">
                @foreach ($game->prizes as $prize)
                    <li>{{ $prize['name'] ?? 'Prize' }}@if (! empty($prize['amount'])) <b>${{ number_format((float) $prize['amount']) }}</b>@endif
                        @if (! empty($prize['pattern']))<span class="sr-only">,</span> · {{ $prize['pattern'] }}@endif</li>
                @endforeach
            </ul>
        @endif
    </div>
    @if ($game->isCancelled())
        <span class="tag tag-cancelled">Cancelled</span>
    @elseif ($isLiveGame)
        <a class="tag tag-live" href="{{ route('watch') }}"><span class="live-dot"></span>Live now</a>
    @elseif ($loop->first ?? false)
        <span class="tag tag-hot">Next game</span>
    @else
        <span class="tag">Scheduled</span>
    @endif
</article>
