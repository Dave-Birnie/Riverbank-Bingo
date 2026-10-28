@extends('layouts.site', ['title' => 'Card Distributors'])

@section('content')
@php
    $pickup = $distributors->where('type', \App\Enums\DistributorType::Pickup);
    $delivery = $distributors->where('type', \App\Enums\DistributorType::Delivery);
    $areas = collect(preg_split('/\r?\n/', (string) $settings->get('delivery_areas')))->map('trim')->filter();
@endphp
    @include('partials.page-hero', ['eyebrow' => 'Find cards', 'heading' => $page?->title ?? 'Card Distributors', 'intro' => $page?->intro])

    <div class="wrap page-body">
        @include('partials.map', ['distributors' => $distributors, 'class' => 'map-tall'])

        <div class="split" style="align-items:start;margin-top:40px">
            <div>
                <h2 style="font-size:28px;margin:0 0 16px">Pickup locations</h2>
                <div class="list">
                    @forelse ($pickup as $distributor)
                        @include('partials.distributor-row', ['full' => true])
                    @empty
                        <p class="empty">Pickup locations coming soon.</p>
                    @endforelse
                </div>
            </div>
            <div>
                <h2 style="font-size:28px;margin:0 0 16px">Delivery</h2>
                @if ($areas->isNotEmpty())
                    <p class="lede" style="margin-top:0">Contactless card delivery to: {{ $areas->implode(', ') }}.</p>
                @endif
                <div class="list">
                    @foreach ($delivery as $distributor)
                        @include('partials.distributor-row', ['full' => true])
                    @endforeach
                </div>
                @if ($settings->get('business_phone'))
                    <p style="margin-top:18px"><a class="btn btn-hot" href="{{ $settings->phoneHref() }}">Call to arrange delivery</a></p>
                @endif
            </div>
        </div>

        @if ($page?->body)<div class="prose" style="margin-top:36px">{!! $page->renderedBody() !!}</div>@endif
        <p style="margin-top:28px">Want to sell cards in your community? <a href="{{ route('page', 'become-a-distributor') }}">Become a distributor</a>.</p>
    </div>
@endsection
