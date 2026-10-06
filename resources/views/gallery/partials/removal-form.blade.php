{{-- Opened from the flag button in the lightbox (resources/js/gallery.js). --}}
@php $failed = $errors->hasAny(['name', 'email', 'reason']); @endphp

<div x-data="{ open: @js($failed), url: @js(old('removal_url', '')), thumb: @js(old('removal_thumb', '')) }"
     x-on:photo-removal.window="open = true; url = $event.detail.url; thumb = $event.detail.thumb; $nextTick(() => $refs.name.focus())"
     x-on:keydown.escape.window="open = false"
     x-show="open" x-cloak
     class="fixed inset-0 z-[100] grid place-items-center overflow-y-auto bg-ink-950/60 p-4">
    <div x-on:click.outside="open = false" role="dialog" aria-modal="true" aria-labelledby="removal-title"
         class="w-full max-w-md rounded-card bg-white p-6 shadow-lift sm:p-7">
        <div class="flex items-start gap-4">
            <img x-show="thumb" :src="thumb" alt="" class="h-16 w-24 shrink-0 rounded-xl object-cover">
            <div>
                <h2 id="removal-title" class="text-lg font-bold text-ink-900">Ask for this photo to be removed</h2>
                <p class="mt-1 text-sm text-ink-500">The organisers review every request and email you what they decide.</p>
            </div>
        </div>

        <form method="POST" :action="url" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="removal_url" :value="url">
            <input type="hidden" name="removal_thumb" :value="thumb">
            <x-form.input name="name" label="Your name" autocomplete="name" required maxlength="120" x-ref="name" />
            <x-form.input name="email" type="email" label="Email" autocomplete="email" required maxlength="190" />
            <x-form.textarea name="reason" label="Reason (optional)" rows="3" maxlength="1000"
                hint="For example: you are in the photo and did not agree to it being shared." />
            <div class="flex justify-end gap-2 pt-1">
                <x-button type="button" variant="secondary" x-on:click="open = false">Cancel</x-button>
                <x-button icon="flag">Send request</x-button>
            </div>
        </form>
    </div>
</div>
