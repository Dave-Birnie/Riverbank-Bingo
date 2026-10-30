@php
    use App\Support\Format;
    use App\Support\Locales;

    $isLiveGame = $live['live'] && $live['game']?->is($game);
    $cancelNote = $game->localized('cancellation_note');
@endphp
<article class="card game @if ($game->isCancelled()) is-cancelled @endif">
    <div class="game-date">
        <small>{{ Format::month($game->starts_at) }}</small>
        <b>{{ $game->starts_at->day }}</b>
    </div>
    <div>
        <div class="game-title">{{ $game->localized('title') }}</div>
        <div class="game-detail">
            {{ Format::weekday($game->starts_at) }} · {{ Format::time($game->starts_at) }}
            @if ($game->card_sales_close_at && ! $game->isCancelled())
                · {{ __('cards close :time', ['time' => Format::time($game->card_sales_close_at, zone: false)]) }}
            @endif
        </div>
        @if ($game->isCancelled())
            <div class="cancel-note">{{ $cancelNote ? __('Cancelled: :note', ['note' => $cancelNote]) : __('Cancelled') }}</div>
        @elseif (($showPrizes ?? false) && ! empty($game->prizes))
            <ul class="prizes">
                @foreach ($game->prizes as $prize)
                    @php $pattern = Locales::item($prize, 'pattern'); @endphp
                    <li>{{ Locales::item($prize, 'name') ?? __('Prize') }}@if (! empty($prize['amount'])) <b>{{ Format::money($prize['amount']) }}</b>@endif
                        @if ($pattern) · {{ $pattern }}@endif</li>
                @endforeach
            </ul>
        @endif
        @if (($showPrizes ?? false) && ! $game->isCancelled() && $game->localized('notes'))
            <div class="game-detail" style="margin-top:8px">{{ $game->localized('notes') }}</div>
        @endif
    </div>
    @if ($game->isCancelled())
        <span class="tag tag-cancelled">{{ __('Cancelled') }}</span>
    @elseif ($isLiveGame)
        <a class="tag tag-live" href="{{ lroute('watch') }}"><span class="live-dot"></span>{{ __('Live now') }}</a>
    @elseif ($loop->first ?? false)
        <span class="tag tag-hot">{{ __('Next game') }}</span>
    @else
        <span class="tag">{{ __('Scheduled') }}</span>
    @endif
</article>
