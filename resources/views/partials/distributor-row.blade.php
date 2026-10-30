@php
    $initials = collect(preg_split('/\s+/', trim($distributor->name)))->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
    $where = collect([
        $distributor->type === \App\Enums\DistributorType::Delivery ? __('Delivery') : null,
        $distributor->address,
        $distributor->city,
    ])->filter()->implode(' · ');
@endphp
<div class="card dist">
    <div class="dist-initials" aria-hidden="true">{{ $initials }}</div>
    <div class="dist-body">
        <div class="dist-name">{{ $distributor->name }}</div>
        @if ($where)<div class="dist-detail">{{ $where }}</div>@endif
        @if (($full ?? false) && $distributor->localized('hours'))<div class="dist-detail">{{ $distributor->localized('hours') }}</div>@endif
        @if (($full ?? false) && $distributor->localized('notes'))<div class="dist-detail">{{ $distributor->localized('notes') }}</div>@endif
    </div>
    @if ($distributor->phone)
        <a class="call" href="tel:{{ preg_replace('/\D+/', '', $distributor->phone) }}" aria-label="{{ __('Call :name', ['name' => $distributor->name]) }}">{{ __('Call') }}</a>
    @endif
</div>
