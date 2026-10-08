@php
    use App\Models\AbstractSubmission;

    $sections = [
        'background' => ['Background', 'Why does this work matter? What problem does it address?'],
        'methods' => ['Methods', 'Design, setting, participants and how you collected and analysed data.'],
        'results' => ['Results', 'Your main findings, with numbers where you have them.'],
        'conclusions' => ['Conclusions', 'What it means for rehabilitation practice or policy.'],
    ];
    $text = collect(array_keys($sections))->mapWithKeys(fn ($field) => [$field => old($field, $abstract->{$field})])->all();
@endphp

<x-layouts.portal title="Revise your abstract">
    <x-slot:header>
        <x-page-header :eyebrow="$abstract->topic->name" title="Revise your abstract" :back="route('abstracts.show', $abstract)"
            :description="'Send the revised version by '.$abstract->revision_due_on->format('l j F Y').'. It goes back to the reviewers who asked for changes, and the scientific committee then makes the final decision.'" />
    </x-slot:header>

    @if ($errors->any())
        <x-alert tone="danger">Please check the highlighted fields.</x-alert>
    @elseif ($abstract->isRevisionOverdue())
        <x-alert tone="danger">The revised version was due on {{ $abstract->revision_due_on->format('j F') }}. You can still send it while the committee has not decided.</x-alert>
    @endif

    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_380px]">
        <form method="POST" action="{{ route('abstracts.revision.update', $abstract) }}" class="space-y-6"
              x-data="{
                  text: @js($text),
                  get words() { return Object.values(this.text).join(' ').trim().split(/\s+/).filter(Boolean).length },
              }">
            @csrf
            @method('PUT')

            <x-card title="About the abstract" description="Only the title, text and keywords can change. The topic and authors stay as reviewed.">
                <div class="space-y-5">
                    <x-form.input name="title" label="Title" :value="$abstract->title" maxlength="200" required />
                    <x-form.input name="keywords" label="Keywords (optional)" :value="$abstract->keywords" />
                </div>
            </x-card>

            <x-card title="Abstract">
                <x-slot:actions>
                    <span class="rounded-full px-3 py-1 text-sm font-semibold"
                          :class="words > {{ AbstractSubmission::WORD_LIMIT }} ? 'bg-red-50 text-red-700' : 'bg-brand-50 text-brand-800'">
                        <span x-text="words"></span> / {{ AbstractSubmission::WORD_LIMIT }} words
                    </span>
                </x-slot:actions>
                <div class="space-y-5">
                    @foreach ($sections as $field => [$label, $hint])
                        <x-form.textarea :name="$field" :label="$label" :hint="$hint" rows="5" x-model="text.{{ $field }}" required />
                    @endforeach
                </div>
            </x-card>

            <x-card title="Your response to the reviewers" description="What you changed for each comment, and anything you chose not to change and why. The reviewers see this beside the two versions.">
                <x-form.textarea name="revision_response" label="Response" rows="6" required
                    placeholder="For example: We added how participants were recruited to the methods, and confidence intervals to the results." />
            </x-card>

            <div class="flex flex-col-reverse gap-3 rounded-card border border-ink-100 bg-white p-5 shadow-soft sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-ink-500">You can revise once. After you send it, the abstract cannot be changed.</p>
                <x-button icon="check">Send revised version</x-button>
            </div>
        </form>

        <div class="space-y-6 lg:sticky lg:top-28">
            @if ($abstract->revision_note)
                <x-card title="From the scientific committee">
                    <p class="whitespace-pre-line text-sm leading-relaxed text-ink-700">{{ $abstract->revision_note }}</p>
                </x-card>
            @endif
            <x-card title="What the reviewers said" description="Reviewers are anonymous.">
                <div class="space-y-4">
                    @forelse ($feedback as $review)
                        <div class="rounded-2xl border border-ink-100 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-ink-500">Reviewer {{ $loop->iteration }}</p>
                            <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-ink-700">{{ $review->comments_for_author }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-ink-500">No written comments.</p>
                    @endforelse
                </div>
            </x-card>
        </div>
    </div>
</x-layouts.portal>
