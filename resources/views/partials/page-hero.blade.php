<section class="page-hero">
    <div class="wrap">
        @isset($eyebrow)<div class="eyebrow">{{ $eyebrow }}</div>@endisset
        <h1>{{ $heading }}</h1>
        @if (! empty($intro))<p class="lede">{{ $intro }}</p>@endif
    </div>
</section>
