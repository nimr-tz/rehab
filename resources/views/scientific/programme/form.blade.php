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

    @if ($session->exists && $session->isScannable())
        <x-card title="Presentations" :description="$presentations->count().' scheduled, presented in this order. Presenters see their session on their abstract.'" :padding="false">
            @if ($presentations->isEmpty())
                <p class="px-6 pb-2 pt-4 text-sm text-ink-500">No presentations in this session yet.</p>
            @else
                <ol class="divide-y divide-ink-100">
                    @foreach ($presentations as $abstract)
                        <li class="flex flex-wrap items-center gap-3 px-6 py-3">
                            <span class="w-6 text-xs font-bold text-ink-400">{{ $loop->iteration }}</span>
                            <span class="min-w-0 flex-1">
                                <a href="{{ route('scientific.abstracts.show', $abstract) }}" class="block truncate font-semibold text-ink-900 hover:text-brand-700">
                                    <span class="font-mono text-brand-700">{{ $abstract->code }}</span> · {{ $abstract->title }}
                                </a>
                                <span class="block truncate text-xs text-ink-500">
                                    {{ $abstract->presenter()?->name }}
                                    · @if ($abstract->presentationFile)<a href="{{ route('presentations.download', $abstract) }}" class="font-semibold text-emerald-700 hover:underline">Slides ({{ $abstract->presentationFile->sizeLabel() }})</a>@else<span class="text-ember-700">No slides yet</span>@endif
                                </span>
                            </span>
                            <span class="flex items-center gap-1">
                                @foreach (['up' => '↑', 'down' => '↓'] as $direction => $arrow)
                                    @continue(($direction === 'up' && $loop->parent->first) || ($direction === 'down' && $loop->parent->last))
                                    <form method="POST" action="{{ route('scientific.programme.abstracts.move', [$session, $abstract]) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="direction" value="{{ $direction }}">
                                        <button class="grid h-8 w-8 place-items-center rounded-lg text-ink-500 hover:bg-ink-100 hover:text-ink-900" aria-label="Move {{ $direction }}">{{ $arrow }}</button>
                                    </form>
                                @endforeach
                                <form method="POST" action="{{ route('scientific.programme.abstracts.destroy', [$session, $abstract]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="ml-1 text-xs font-semibold text-red-700 hover:underline">Remove</button>
                                </form>
                            </span>
                        </li>
                    @endforeach
                </ol>
            @endif

            <form method="POST" action="{{ route('scientific.programme.abstracts.store', $session) }}" class="flex flex-col gap-3 border-t border-ink-100 px-6 py-4 sm:flex-row sm:items-end">
                @csrf
                <div class="flex-1">
                    <label for="abstract_id" class="label">Add an accepted abstract</label>
                    <select id="abstract_id" name="abstract_id" class="field" @disabled($candidates->isEmpty())>
                        @forelse ($candidates as $candidate)
                            <option value="{{ $candidate->id }}">{{ $candidate->code }} · {{ \Illuminate\Support\Str::limit($candidate->title, 70) }}{{ $candidate->topic_id === $session->topic_id ? '' : ' ('.$candidate->topic->code.')' }}</option>
                        @empty
                            <option>Every accepted abstract is scheduled</option>
                        @endforelse
                    </select>
                    @error('abstract_id') <p class="mt-1.5 text-xs font-medium text-red-700">{{ $message }}</p> @enderror
                </div>
                <x-button variant="secondary" icon="plus" :disabled="$candidates->isEmpty()">Add</x-button>
            </form>
        </x-card>
    @endif

    @if ($session->exists)
        <form method="POST" action="{{ route('scientific.programme.destroy', $session) }}" x-data @submit="if (! confirm(@js('Remove '.$session->title.' from the programme?'))) $event.preventDefault()">
            @csrf
            @method('DELETE')
            <button class="text-sm font-semibold text-red-700 hover:underline">Remove this session from the programme</button>
        </form>
    @endif
</x-layouts.portal>
