@php use App\Support\Format; @endphp
<article class="card winner">
    <div class="winner-photo">
        @if ($winner->photo_path)
            <img src="{{ Storage::disk('public')->url($winner->photo_path) }}" alt="{{ $winner->display_name }}" loading="lazy">
        @else
            <span aria-hidden="true">★</span>
        @endif
    </div>
    <div class="winner-body">
        <div class="winner-amount">
            {{ $winner->amount ? Format::money($winner->amount) : '' }} {{ $winner->localized('prize_label') }}
        </div>
        @if ($winner->localized('story'))<p class="winner-story">{{ $winner->localized('story') }}</p>@endif
        <div class="winner-who">{{ $winner->display_name }} · {{ Format::date($winner->won_on) }}</div>
    </div>
</article>
