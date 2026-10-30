{{-- OpenStreetMap via Leaflet: free, no API key, no billing account. --}}
@php
    $pins = $distributors->filter->hasPin()->map(fn ($d) => [
        'name' => $d->name,
        'lat' => $d->latitude,
        'lng' => $d->longitude,
        'detail' => collect([$d->address, $d->city])->filter()->implode(', '),
        'phone' => $d->phone,
    ])->values();
@endphp
@push('head')
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
@endpush
@push('scripts')
    <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
@endpush
<div class="map {{ $class ?? '' }}" data-map="{{ $pins->toJson() }}" role="region" aria-label="{{ __('Map of card distributors') }}"></div>
