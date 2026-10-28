{{-- Scrolling announcement strip. The list is doubled so the marquee loops seamlessly. --}}
@php $loop_items = $items->count() < 3 ? $items->concat($items)->concat($items) : $items; @endphp
<div class="banner" role="region" aria-label="Announcements">
    <ul class="sr-only">
        @foreach ($items as $item)
            <li>{{ $item['text'] }}</li>
        @endforeach
    </ul>
    <div class="banner-track" aria-hidden="true" style="--marquee-duration: {{ max(24, $loop_items->count() * 10) }}s">
        @foreach ([1, 2] as $copy)
            @foreach ($loop_items as $item)
                <span class="banner-item @if ($item['alert']) is-alert @endif">
                    {{ $item['alert'] ? '⚠' : '★' }} {{ $item['text'] }}
                    @if ($item['url'])<a href="{{ $item['url'] }}" tabindex="-1">{{ $item['label'] }}</a>@endif
                </span>
            @endforeach
        @endforeach
    </div>
</div>
