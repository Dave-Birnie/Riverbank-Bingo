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
            {{ $winner->amount ? '$'.number_format((float) $winner->amount) : '' }} {{ $winner->prize_label }}
        </div>
        @if ($winner->story)<p class="winner-story">{{ $winner->story }}</p>@endif
        <div class="winner-who">{{ $winner->display_name }} · {{ $winner->won_on->isoFormat('MMMM D, YYYY') }}</div>
    </div>
</article>
