@php
    $initials = collect(preg_split('/\s+/', trim($distributor->name)))->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
    $where = collect([
        $distributor->type === \App\Enums\DistributorType::Delivery ? 'Delivery' : null,
        $distributor->address,
        $distributor->city,
    ])->filter()->implode(' · ');
@endphp
<div class="card dist">
    <div class="dist-initials" aria-hidden="true">{{ $initials }}</div>
    <div class="dist-body">
        <div class="dist-name">{{ $distributor->name }}</div>
        @if ($where)<div class="dist-detail">{{ $where }}</div>@endif
        @if (($full ?? false) && $distributor->hours)<div class="dist-detail">{{ $distributor->hours }}</div>@endif
        @if (($full ?? false) && $distributor->notes)<div class="dist-detail">{{ $distributor->notes }}</div>@endif
    </div>
    @if ($distributor->phone)
        <a class="call" href="tel:{{ preg_replace('/\D+/', '', $distributor->phone) }}" aria-label="Call {{ $distributor->name }}">Call</a>
    @endif
</div>
