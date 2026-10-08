<x-layouts.portal :title="$session->exists ? 'Edit session' : 'Add a session'">
    <x-slot:header>
        <x-page-header eyebrow="Programme & CPD" :title="$session->exists ? $session->title : 'Add a session'" :back="route('scientific.programme.index')" />
    </x-slot:header>

    <form method="POST" action="{{ $session->exists ? route('scientific.programme.update', $session) : route('scientific.programme.store') }}"
          x-data="{ kind: @js(old('kind', $session->kind)) }" class="grid gap-6 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
        @csrf
        @if ($session->exists) @method('PUT') @endif

        <x-card title="Session">
            <div class="space-y-5">
                <x-form.input name="title" label="Title" :value="$session->title" required autofocus />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.select name="kind" label="Type" :options="$kinds" :value="$session->kind" x-model="kind" required />
                    <x-form.select name="topic_id" label="Topic (optional)" placeholder="—" :options="$topics" :value="$session->topic_id" />
                    <x-form.input name="starts_at" type="datetime-local" label="Starts" :value="$session->starts_at?->format('Y-m-d\TH:i')" required />
                    <x-form.input name="ends_at" type="datetime-local" label="Ends" :value="$session->ends_at?->format('Y-m-d\TH:i')" required />
                    <x-form.input name="hall" label="Hall (optional)" :value="$session->hall" />
                    <x-form.input name="chair" label="Chair (optional)" :value="$session->chair" />
                </div>
                <x-form.input name="description" label="Short description (optional)" :value="$session->description" />
            </div>
        </x-card>

        <div class="space-y-6">
            <x-card title="CPD points" description="What attending this session earns. Badges are scanned while the session runs.">
                <div x-show="kind !== 'break'">
                    <x-form.input name="cpd_points" type="number" step="0.25" min="0" max="99" label="Points" :value="$session->cpd_points !== null ? rtrim(rtrim((string) $session->cpd_points, '0'), '.') : null" hint="Leave empty if not decided yet. Use 0 for a session that earns nothing." />
                </div>
                <p x-show="kind === 'break'" x-cloak class="text-sm text-ink-600">Breaks are not scanned and earn no points.</p>
            </x-card>

            <x-button size="lg" icon="check" class="w-full">{{ $session->exists ? 'Save the session' : 'Add the session' }}</x-button>
        </div>
    </form>

    @if ($session->exists)
        <form method="POST" action="{{ route('scientific.programme.destroy', $session) }}" x-data @submit="if (! confirm(@js('Remove '.$session->title.' from the programme?'))) $event.preventDefault()">
            @csrf
            @method('DELETE')
            <button class="text-sm font-semibold text-red-700 hover:underline">Remove this session from the programme</button>
        </form>
    @endif
</x-layouts.portal>
