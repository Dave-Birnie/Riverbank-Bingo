@extends('layouts.site', ['title' => $page->localized('title'), 'description' => $page->localized('meta_description') ?: $page->localized('intro')])

@section('content')
    @include('partials.page-hero', ['heading' => $page->localized('title'), 'intro' => $page->localized('intro')])

    <div class="wrap page-body">
        <div class="prose">{!! $page->renderedBody() !!}</div>

        @if ($page->key === 'become-a-distributor')
            <p style="margin-top:28px"><a class="btn btn-hot" href="{{ lroute('contact', ['topic' => 'distributor']) }}">{{ __('Apply to become a distributor') }}</a></p>
        @endif
    </div>
@endsection
