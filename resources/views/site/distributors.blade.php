@extends('layouts.site', ['title' => $page?->localized('title') ?? __('Card Distributors')])

@section('content')
@php
    use App\Enums\DistributorType;

    $pickup = $distributors->where('type', DistributorType::Pickup);
    $delivery = $distributors->where('type', DistributorType::Delivery);
    $areas = collect(preg_split('/\r?\n/', (string) $settings->localized('delivery_areas')))->map('trim')->filter();
@endphp
    @include('partials.page-hero', ['eyebrow' => __('Find cards'), 'heading' => $page?->localized('title') ?? __('Card Distributors'), 'intro' => $page?->localized('intro')])

    <div class="wrap page-body">
        @include('partials.map', ['distributors' => $distributors, 'class' => 'map-tall'])

        <div class="split split-top" style="margin-top:40px">
            <div>
                <h2 style="font-size:28px;margin:0 0 16px">{{ __('Pickup locations') }}</h2>
                <div class="list">
                    @forelse ($pickup as $distributor)
                        @include('partials.distributor-row', ['full' => true])
                    @empty
                        <p class="empty">{{ __('Pickup locations coming soon.') }}</p>
                    @endforelse
                </div>
            </div>
            <div>
                <h2 style="font-size:28px;margin:0 0 16px">{{ __('Delivery') }}</h2>
                @if ($areas->isNotEmpty())
                    <p class="lede" style="margin-top:0">{{ __('Contactless card delivery to: :areas.', ['areas' => $areas->implode(', ')]) }}</p>
                @endif
                <div class="list">
                    @foreach ($delivery as $distributor)
                        @include('partials.distributor-row', ['full' => true])
                    @endforeach
                </div>
                @if ($settings->get('business_phone'))
                    <p style="margin-top:18px"><a class="btn btn-hot" href="{{ $settings->phoneHref() }}">{{ __('Call to arrange delivery') }}</a></p>
                @endif
            </div>
        </div>

        @if ($page?->localized('body'))<div class="prose" style="margin-top:36px">{!! $page->renderedBody() !!}</div>@endif
        <p style="margin-top:28px">{{ __('Want to sell cards in your community?') }} <a href="{{ lroute('page.become-a-distributor') }}">{{ __('Become a distributor') }}</a>.</p>
    </div>
@endsection
