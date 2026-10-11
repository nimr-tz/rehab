@php
    use App\Enums\ApplicationStatus;
    use App\Enums\SessionRole;
    $deadline = $edition?->session_role_deadline;
@endphp

<x-layouts.portal title="Chair or rapporteur">
    <x-slot:header>
        <x-page-header eyebrow="Programme" title="Chair or rapporteur"
            description="Help run the summit's sessions. The scientific committee chooses chairs and rapporteurs from the people who apply, and tells everyone either way." />
    </x-slot:header>

    @unless ($edition)
        <x-card><x-empty icon="calendar" title="The summit has not been set up yet">Come back once the summit's details are announced.</x-empty></x-card>
    @else
        <div class="grid gap-6 lg:grid-cols-2">
            @foreach ($roles as $role)
                @php $application = $applications->get($role->value); @endphp
                <x-card :title="$role->label()" :description="$role->duty()">
                    @if (! $application)
                        @if ($open)
                            <form method="POST" action="{{ route('session-roles.store') }}" class="space-y-4">
                                @csrf
                                <input type="hidden" name="role" value="{{ $role->value }}">
                                @if ($topics->isNotEmpty())
                                    <fieldset>
                                        <legend class="label">Topics you know well (optional)</legend>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($topics as $topic)
                                                <label class="flex cursor-pointer items-center gap-2 rounded-full border border-ink-200 px-3 py-1.5 text-sm has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50">
                                                    <input type="checkbox" name="topics[]" value="{{ $topic->id }}" @checked(old('role') === $role->value && in_array($topic->id, old('topics', []))) class="h-4 w-4 accent-brand-700">
                                                    {{ $topic->name }}
                                                </label>
                                            @endforeach
                                        </div>
                                    </fieldset>
                                @endif
                                <x-form.textarea name="experience" :id="'experience-'.$role->value" label="Your experience" rows="4" :value="old('role') === $role->value ? old('experience') : null"
                                    placeholder="{{ $role === SessionRole::Chair ? 'e.g. I have chaired sessions at the national physiotherapy conference since 2022.' : 'e.g. I wrote session reports for the 2025 summit and edit our department newsletter.' }}" />
                                <x-button icon="check">Apply to be a {{ strtolower($role->label()) }}</x-button>
                            </form>
                            @if ($deadline)
                                <p class="mt-3 text-xs text-ink-500">Applications close on {{ $deadline->format('j F Y') }}.</p>
                            @endif
                        @else
                            <x-empty icon="clock" title="Applications are closed" class="!py-6">
                                {{ $deadline ? 'They closed on '.$deadline->format('j F Y').'.' : 'They are not open at the moment.' }}
                            </x-empty>
                        @endif
                    @else
                        <div class="flex flex-wrap items-center gap-2">
                            <x-status :tone="$application->status->tone()">{{ $application->status->label() }}</x-status>
                            <span class="text-xs text-ink-500">Applied {{ $application->created_at->format('j M Y') }}</span>
                        </div>

                        @if ($application->status === ApplicationStatus::Pending)
                            <p class="mt-3 text-sm text-ink-600">The committee will email you once it has decided.</p>
                            <form method="POST" action="{{ route('session-roles.destroy', $application) }}" class="mt-4">
                                @csrf
                                @method('DELETE')
                                <button class="text-sm font-semibold text-red-700 hover:underline">Withdraw the application</button>
                            </form>
                        @elseif ($application->status === ApplicationStatus::Declined)
                            <p class="mt-3 text-sm text-ink-600">The committee had more offers than sessions this time. Thank you for offering.</p>
                        @elseif ($session = $application->session)
                            <div class="mt-4 rounded-2xl bg-brand-50 p-4">
                                <p class="font-bold text-ink-900">{{ $session->title }}</p>
                                <p class="text-sm text-ink-600">{{ $session->starts_at->format('l j F, H:i') }}–{{ $session->ends_at->format('H:i') }}{{ $session->hall ? ' · '.$session->hall : '' }}</p>
                            </div>
                            @if ($application->decision_note)
                                <p class="mt-3 text-sm text-ink-600">Note from the committee: {{ $application->decision_note }}</p>
                            @endif

                            @if ($session->abstracts->isNotEmpty())
                                <p class="mt-5 text-sm font-semibold text-ink-900">Presentations, in order</p>
                                <ol class="mt-2 divide-y divide-ink-100 rounded-2xl border border-ink-100 text-sm">
                                    @foreach ($session->abstracts as $abstract)
                                        <li class="flex items-center justify-between gap-3 px-4 py-2.5">
                                            <span class="min-w-0">
                                                <span class="block truncate font-semibold text-ink-900"><span class="font-mono text-brand-700">{{ $abstract->code }}</span> · {{ $abstract->title }}</span>
                                                <span class="block text-xs text-ink-500">{{ $abstract->presenter()?->name }}</span>
                                            </span>
                                            @if ($role === SessionRole::Chair && $abstract->presentationFile)
                                                <x-button variant="ghost" size="sm" :href="route('presentations.download', $abstract)" icon="download">Slides</x-button>
                                            @endif
                                        </li>
                                    @endforeach
                                </ol>
                            @endif

                            @if ($role === SessionRole::Rapporteur)
                                <div class="mt-6 border-t border-ink-100 pt-5">
                                    @if ($session->acceptsReport())
                                        <form method="POST" action="{{ route('session-roles.report', $session) }}" class="space-y-3">
                                            @csrf
                                            @method('PUT')
                                            <x-form.textarea name="report" label="Your report" rows="8" :value="old('report', $session->rapporteur_report)"
                                                hint="The main points, questions and recommendations of the session. It is published with the programme." />
                                            <x-button icon="check">{{ $session->rapporteur_report ? 'Update the report' : 'Publish the report' }}</x-button>
                                        </form>
                                        @if ($session->rapporteur_report_at)
                                            <p class="mt-2 text-xs text-ink-500">Last saved {{ $session->rapporteur_report_at->format('j M Y, H:i') }}.</p>
                                        @endif
                                    @else
                                        <p class="text-sm text-ink-600">You can write your report here once the session has started.</p>
                                    @endif
                                </div>
                            @endif
                        @endif
                    @endif
                </x-card>
            @endforeach
        </div>
    @endunless
</x-layouts.portal>
