@extends('layouts.site', ['title' => $page?->localized('title') ?? __('Contact Us')])

@section('content')
@php
    use App\Models\ContactMessage;

    $phone = $settings->get('business_phone');
    $email = $settings->get('business_email');
@endphp
    @include('partials.page-hero', ['eyebrow' => __('Contact'), 'heading' => $page?->localized('title') ?? __('Contact Us'), 'intro' => $page?->localized('intro')])

    <div class="wrap page-body">
        <div class="split split-top">
            <div>
                @if (session('sent'))
                    <p class="flash" role="status">{{ __("Thanks! Your message is on its way. We'll get back to you soon.") }}</p>
                @endif

                <form class="form" method="post" action="{{ lroute('contact.send') }}" novalidate>
                    @csrf
                    <div class="hp" aria-hidden="true">
                        <label for="website">{{ __('Leave this empty') }}</label>
                        <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                    </div>
                    <div class="field">
                        <label for="topic">{{ __("What's it about?") }}</label>
                        <select id="topic" name="topic">
                            @foreach (ContactMessage::TOPICS as $value => $label)
                                <option value="{{ $value }}" @selected(old('topic', request('topic')) === $value)>{{ __($label) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="name">{{ __('Your name') }}</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name">
                        @error('name')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="field">
                        <label for="email">{{ __('Email') }}</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email">
                        @error('email')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="field">
                        <label for="phone">{{ __('Phone (optional)') }}</label>
                        <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel">
                        @error('phone')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="field">
                        <label for="message">{{ __('Message') }}</label>
                        <textarea id="message" name="message" required>{{ old('message') }}</textarea>
                        @error('message')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div><button class="btn btn-hot" type="submit">{{ __('Send message') }}</button></div>
                    <p class="small-print">{{ __('We only use your details to reply to you.') }} <a href="{{ lroute('page.privacy') }}">{{ __('Privacy policy') }}</a></p>
                </form>
            </div>
            <div>
                @if ($phone)
                    <div class="card hotline" style="margin-top:0">
                        <div><div class="eyebrow">{{ __('Call us') }}</div><b>{{ $phone }}</b></div>
                        <a class="btn btn-hot" href="{{ $settings->phoneHref() }}">{{ __('Call') }}</a>
                    </div>
                @endif
                @if ($email)
                    <div class="card hotline">
                        <div><div class="eyebrow">{{ __('Email') }}</div><a href="mailto:{{ $email }}" style="font-weight:600;font-size:18px">{{ $email }}</a></div>
                    </div>
                @endif
                @if ($page?->localized('body'))<div class="prose" style="margin-top:24px">{!! $page->renderedBody() !!}</div>@endif
            </div>
        </div>
    </div>
@endsection
