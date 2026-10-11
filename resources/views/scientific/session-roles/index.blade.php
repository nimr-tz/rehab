@php
    use App\Enums\ApplicationStatus;
    use App\Enums\SessionRole;
@endphp

<x-layouts.portal title="Chairs & rapporteurs">
    <x-slot:header>
        <x-page-header eyebrow="Scientific committee" title="Chairs & rapporteurs"
            :description="'Accept an application by giving it a session; the person is named on the programme and emailed.'.($deadline ? ' Applications close on '.$deadline->format('j F Y').'.' : '')" />
    </x-slot:header>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat label="Applications to decide" :value="$counts[ApplicationStatus::Pending->value] ?? 0" icon="clock" tint="bg-sun-100 text-sun-800" />
        <x-stat label="Sessions without a chair" :value="$gaps['chair']" icon="users" tint="bg-brand-50 text-brand-700" />
        <x-stat label="Sessions without a rapporteur" :value="$gaps['rapporteur']" icon="document" tint="bg-brand-50 text-brand-700" />
    </div>

    <x-card :padding="false">
        <div class="flex flex-col gap-3 border-b border-ink-100 p-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-wrap gap-2">
                @foreach (ApplicationStatus::cases() as $tab)
                    <a href="{{ route('scientific.session-roles.index', array_filter(['status' => $tab->value, 'role' => $role?->value])) }}"
                       @class(['rounded-full px-4 py-2 text-sm font-semibold transition', 'bg-brand-700 text-white' => $status === $tab, 'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-50' => $status !== $tab])>
                        {{ $tab === ApplicationStatus::Pending ? 'To decide' : $tab->label() }} <span class="ml-1 opacity-70">{{ $counts[$tab->value] ?? 0 }}</span>
                    </a>
                @endforeach
            </div>
            <div class="flex gap-2 text-sm">
                @foreach ([null => 'Both', ...collect(SessionRole::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label().'s'])->all()] as $value => $label)
                    <a href="{{ route('scientific.session-roles.index', array_filter(['status' => $status->value, 'role' => $value])) }}"
                       @class(['rounded-lg px-3 py-1.5 font-semibold', 'bg-ink-100 text-ink-900' => ($role?->value ?? '') === (string) $value, 'text-ink-500 hover:text-ink-800' => ($role?->value ?? '') !== (string) $value])>{{ $label }}</a>
                @endforeach
            </div>
        </div>

        @if ($applications->isEmpty())
            <x-empty icon="users" :title="$status === ApplicationStatus::Pending ? 'Nothing to decide' : 'None here'">
                {{ $status === ApplicationStatus::Pending ? 'New applications appear here as participants apply.' : 'Try another tab.' }}
            </x-empty>
        @else
            <ul class="divide-y divide-ink-100">
                @foreach ($applications as $application)
                    @php
                        $preferred = collect($application->topic_ids ?? [])->map(fn ($id) => $topics->get($id))->filter();
                        $column = $application->role === SessionRole::Chair ? 'chair' : 'rapporteur';
                        $options = $sessions->sortBy(fn ($s) => [$preferred->contains('id', $s->topic_id) ? 0 : 1, $s->{$column} ? 1 : 0, $s->starts_at->timestamp]);
                    @endphp
                    <li class="grid gap-4 px-6 py-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-bold text-ink-900">{{ $application->user->name }}</p>
                                <x-status tone="info">{{ $application->role->label() }}</x-status>
                            </div>
                            <p class="text-sm text-ink-500">{{ $application->user->profession }}{{ $application->user->institution ? ' · '.$application->user->institution : '' }}</p>
                            @if ($preferred->isNotEmpty())
                                <p class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach ($preferred as $topic)
                                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $topic->chipClasses() }}">{{ $topic->name }}</span>
                                    @endforeach
                                </p>
                            @endif
                            <p class="mt-2 whitespace-pre-line text-sm text-ink-700">{{ $application->experience }}</p>
                            <p class="mt-1 text-xs text-ink-400">Applied {{ $application->created_at->format('j M Y') }}</p>
                        </div>

                        <div>
                            @error('application_'.$application->id) <x-alert tone="danger" class="mb-3">{{ $message }}</x-alert> @enderror

                            @if ($application->status === ApplicationStatus::Pending)
                                <form method="POST" action="{{ route('scientific.session-roles.accept', $application) }}" class="space-y-3">
                                    @csrf
                                    <div>
                                        <label for="session-{{ $application->id }}" class="label">Session</label>
                                        <select id="session-{{ $application->id }}" name="session_id" class="field" required>
                                            <option value="">Choose a session…</option>
                                            @foreach ($options as $session)
                                                <option value="{{ $session->id }}" @disabled($session->{$column.'_user_id'})>
                                                    {{ $session->starts_at->format('D H:i') }} · {{ \Illuminate\Support\Str::limit($session->title, 48) }}{{ $session->{$column} ? ' ('.$column.': '.$session->{$column}.')' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <x-form.input name="note" :id="'note-'.$application->id" label="Note to them (optional)" placeholder="e.g. Please arrive 15 minutes early to meet the speakers." />
                                    <div class="flex flex-wrap items-center gap-3">
                                        <x-button variant="success" icon="check">Accept</x-button>
                                        <button type="submit" form="decline-{{ $application->id }}" class="text-sm font-semibold text-red-700 hover:underline">Not this time</button>
                                    </div>
                                </form>
                                <form id="decline-{{ $application->id }}" method="POST" action="{{ route('scientific.session-roles.decline', $application) }}">@csrf</form>
                            @elseif ($application->status === ApplicationStatus::Accepted)
                                <p class="text-sm text-ink-700">
                                    {{ $application->role->label() }} of <span class="font-semibold">{{ $application->session?->title }}</span>
                                    @if ($application->session)<span class="block text-xs text-ink-500">{{ $application->session->starts_at->format('l j F, H:i') }}</span>@endif
                                </p>
                                <p class="mt-1 text-xs text-ink-500">Accepted by {{ $application->decider?->name }} on {{ $application->decided_at?->format('j M Y') }}.</p>
                                <form method="POST" action="{{ route('scientific.session-roles.release', $application) }}" class="mt-3">
                                    @csrf
                                    <button class="text-sm font-semibold text-red-700 hover:underline">Take them off the session</button>
                                </form>
                            @else
                                <p class="text-sm text-ink-600">Not selected by {{ $application->decider?->name }} on {{ $application->decided_at?->format('j M Y') }}.</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="border-t border-ink-100 px-5 py-3">{{ $applications->links() }}</div>
        @endif
    </x-card>
</x-layouts.portal>
