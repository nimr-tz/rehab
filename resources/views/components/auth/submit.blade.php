<button type="submit"
        {{ $attributes->merge(['class' => 'group flex h-12 w-full items-center justify-center gap-2 rounded-control bg-gradient-to-r from-brand-700 to-brand-500 text-[15px] font-bold text-white shadow-soft transition hover:from-brand-800 hover:to-brand-600 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-500/30 disabled:opacity-60']) }}>
    {{ $slot }}
    <x-icon name="arrow-right" class="h-5 w-5 transition group-hover:translate-x-0.5" />
</button>
