@extends('layouts.site', ['title' => 'Contact Us'])

@section('content')
@php
    $phone = $settings->get('business_phone');
    $email = $settings->get('business_email');
@endphp
    @include('partials.page-hero', ['eyebrow' => 'Contact', 'heading' => $page?->title ?? 'Contact Us', 'intro' => $page?->intro])

    <div class="wrap page-body">
        <div class="split" style="align-items:start">
            <div>
                @if (session('sent'))
                    <p class="flash" role="status">Thanks! Your message is on its way. We'll get back to you soon.</p>
                @endif

                <form class="form" method="post" action="{{ route('contact.send') }}" novalidate>
                    @csrf
                    <div class="hp" aria-hidden="true">
                        <label for="website">Leave this empty</label>
                        <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                    </div>
                    <div class="field">
                        <label for="topic">What's it about?</label>
                        <select id="topic" name="topic" style="width:100%;font:inherit;padding:13px 16px;border-radius:12px;border:1px solid var(--line);background:var(--surface);color:var(--ink)">
                            @foreach (\App\Models\ContactMessage::TOPICS as $value => $label)
                                <option value="{{ $value }}" @selected(old('topic', request('topic')) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="name">Your name</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name">
                        @error('name')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email">
                        @error('email')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="field">
                        <label for="phone">Phone (optional)</label>
                        <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel">
                        @error('phone')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="field">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" required>{{ old('message') }}</textarea>
                        @error('message')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div><button class="btn btn-hot" type="submit">Send message</button></div>
                    <p style="font-size:14px;color:var(--muted-2);margin:0">We only use your details to reply to you. See our <a href="{{ route('page', 'privacy') }}">privacy policy</a>.</p>
                </form>
            </div>
            <div>
                @if ($phone)
                    <div class="card hotline" style="margin-top:0">
                        <div><div class="eyebrow">Call us</div><b>{{ $phone }}</b></div>
                        <a class="btn btn-hot" href="{{ $settings->phoneHref() }}">Call</a>
                    </div>
                @endif
                @if ($email)
                    <div class="card hotline">
                        <div><div class="eyebrow">Email</div><a href="mailto:{{ $email }}" style="font-weight:700;font-size:18px">{{ $email }}</a></div>
                    </div>
                @endif
                @if ($page?->body)<div class="prose" style="margin-top:24px">{!! $page->renderedBody() !!}</div>@endif
            </div>
        </div>
    </div>
@endsection
