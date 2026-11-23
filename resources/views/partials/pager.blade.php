@if ($paginator->hasPages())
    <nav class="pager" aria-label="{{ __('Pages') }}">
        @if ($paginator->onFirstPage())<span></span>@else<a class="btn btn-ghost" href="{{ $paginator->previousPageUrl() }}" rel="prev">← {{ __('Newer') }}</a>@endif
        @if ($paginator->hasMorePages())<a class="btn btn-ghost" href="{{ $paginator->nextPageUrl() }}" rel="next">{{ __('Older') }} →</a>@endif
    </nav>
@endif
