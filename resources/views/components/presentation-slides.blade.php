{{-- The slides for an accepted abstract: the current file, and an uploader when allowed. --}}
@props(['abstract', 'canUpload' => false])

@php
    $file = $abstract->presentationFile;
    $deadline = $abstract->edition->presentation_deadline;
    $extensions = config('presentations.extensions');
@endphp

<x-card title="Presentation slides" :description="$deadline ? 'Upload by '.$deadline->format('j F Y').'. You can replace them until then.' : 'Upload them before the summit. You can replace them at any time.'">
    @if ($file)
        <div class="flex items-center justify-between gap-3 rounded-2xl bg-ink-50 px-4 py-3">
            <span class="min-w-0">
                <span class="block truncate text-sm font-semibold text-ink-900">{{ $file->original_name }}</span>
                <span class="block text-xs text-ink-500">{{ $file->sizeLabel() }} · uploaded {{ $file->updated_at->format('j M Y, H:i') }}{{ $file->uploader && $file->uploader->id !== $abstract->user_id ? ' by '.$file->uploader->name : '' }}</span>
            </span>
            <x-button variant="ghost" size="sm" :href="route('presentations.download', $abstract)" icon="download">Download</x-button>
        </div>
    @elseif (! $canUpload)
        <p class="text-sm text-ink-600">No slides were uploaded.</p>
    @endif

    @if ($canUpload)
        <div class="{{ $file ? 'mt-4' : '' }}" x-data="fileUploader({ url: @js(route('presentations.upload', $abstract)), chunkSize: {{ config('presentations.chunk_kb') * 1024 }}, maxBytes: {{ config('presentations.max_mb') * 1048576 }}, extensions: @js($extensions) })">
            <label x-show="! busy" class="flex cursor-pointer flex-col items-center gap-1 rounded-2xl border border-dashed border-ink-300 bg-canvas px-4 py-5 text-center text-sm hover:border-brand-400">
                <x-icon name="upload" class="h-6 w-6 text-brand-600" />
                <span class="font-semibold text-brand-700">{{ $file ? 'Replace the slides' : 'Upload your slides' }}</span>
                <span class="text-xs text-ink-500">{{ strtoupper(implode(', ', $extensions)) }}, up to {{ config('presentations.max_mb') }} MB</span>
                <input type="file" class="sr-only" accept="{{ collect($extensions)->map(fn ($e) => '.'.$e)->implode(',') }}" @change="pick($event)">
            </label>
            <div x-show="busy" x-cloak class="rounded-2xl bg-brand-50 px-4 py-4 text-sm" role="status" aria-live="polite">
                <p class="font-semibold text-ink-900">Uploading <span x-text="file?.name"></span> · <span x-text="progress + '%'"></span></p>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-white"><div class="h-full rounded-full bg-brand-600 transition-all" :style="`width: ${progress}%`"></div></div>
            </div>
            <p x-show="error" x-cloak x-text="error" class="mt-3 rounded-xl bg-red-50 px-3 py-2 text-sm font-medium text-red-800" role="alert"></p>
        </div>
    @elseif ($abstract->user_id === auth()->id() && $deadline?->isPast())
        <p class="mt-3 text-xs text-ink-500">The upload deadline has passed. To change the slides, contact the scientific committee.</p>
    @endif
</x-card>
