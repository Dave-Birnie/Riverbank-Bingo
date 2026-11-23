@php
    $live = $state['live'];
    $game = $state['game'];
    $next = $state['next_game'];
    $until = $state['override_until'];
@endphp
<x-filament-widgets::widget>
    <x-filament::section>
        <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1.25rem">
            <div style="display:flex;align-items:center;gap:1rem">
                <span style="width:18px;height:18px;border-radius:50%;flex:none;background:{{ $live ? '#E5383B' : '#9CA3AF' }};box-shadow:{{ $live ? '0 0 0 6px rgba(229,56,59,.2)' : 'none' }}"></span>
                <div>
                    <div style="font-size:1.5rem;font-weight:700;line-height:1.2">
                        {{ $live ? 'The site is LIVE' : 'Not live' }}
                    </div>
                    <div style="color:#6B7280;margin-top:.25rem">
                        @if ($live && $game)
                            {{ $game->title }} · started {{ $game->starts_at->format('g:i A') }}.
                        @endif
                        @if ($state['source'] === 'manual' && $until)
                            {{ $live ? 'Turned on' : 'Turned off' }} by hand until {{ $until->isToday() ? $until->format('g:i A') : $until->isoFormat('ddd MMM D, h:mm A') }}, then the schedule takes over.
                        @elseif ($state['source'] === 'schedule')
                            Following the schedule; ends at {{ $game?->ends_at->format('g:i A') }}.
                        @elseif ($next)
                            Next game: {{ $next->starts_at->isoFormat('dddd, MMMM D') }} at {{ $next->starts_at->format('g:i A') }}. The site goes live by itself then.
                        @else
                            No games scheduled. Add some under Games.
                        @endif
                    </div>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap">
                @if ($state['source'] === 'manual')
                    {{ $this->resumeAction }}
                @endif
                @if ($live)
                    {{ $this->endLiveAction }}
                @else
                    {{ $this->goLiveAction }}
                @endif
            </div>
        </div>
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-widgets::widget>
