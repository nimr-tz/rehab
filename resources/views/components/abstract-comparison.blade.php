@props(['abstract'])

{{-- The text before the revision and after it, side by side, section by section. --}}
@php
    $rows = ['title' => 'Title'] + \App\Models\AbstractSubmission::SECTIONS + ['keywords' => 'Keywords'];
@endphp

@if ($abstract->original_version)
    <div {{ $attributes->class('space-y-5') }}>
        <div class="hidden grid-cols-2 gap-5 text-[11px] font-bold uppercase tracking-[0.16em] text-ink-500 md:grid">
            <span>Before the revision</span>
            <span>Revised</span>
        </div>
        @foreach ($rows as $field => $label)
            @php
                $before = (string) $abstract->originalText($field);
                $after = (string) $abstract->{$field};
                $changed = trim($before) !== trim($after);
            @endphp
            @continue(! $changed && in_array($field, ['title', 'keywords'], true))
            <section>
                <h3 class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.18em] text-brand-700">
                    {{ $label }}
                    @unless ($changed)<span class="rounded-full bg-ink-50 px-2 py-0.5 text-[10px] font-semibold normal-case tracking-normal text-ink-500">Unchanged</span>@endunless
                </h3>
                <div class="mt-2 grid gap-3 md:grid-cols-2 md:gap-5">
                    <div class="rounded-xl bg-canvas px-4 py-3 text-sm leading-relaxed text-ink-600">
                        <span class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-ink-400 md:hidden">Before</span>
                        <p class="whitespace-pre-line">{{ $before ?: '—' }}</p>
                    </div>
                    <div @class(['rounded-xl px-4 py-3 text-sm leading-relaxed', 'bg-amber-50 text-ink-900 ring-1 ring-amber-200' => $changed, 'bg-canvas text-ink-600' => ! $changed])>
                        <span class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-ink-400 md:hidden">Revised</span>
                        <p class="whitespace-pre-line">{{ $after ?: '—' }}</p>
                    </div>
                </div>
            </section>
        @endforeach
    </div>
@endif
