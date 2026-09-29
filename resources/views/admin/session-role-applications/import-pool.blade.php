@extends('layouts.app')

@section('title', 'Import Chair and Rapporteur Pool')

@section('content')
<div class="min-h-screen bg-slate-50 dark:bg-gray-950">
    <div class="max-w-[1600px] mx-auto px-6 py-10">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6 mb-8">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.28em] text-indigo-600 dark:text-indigo-300 mb-3">Live Account Linking</p>
                <h1 class="text-3xl md:text-4xl font-black tracking-tight text-slate-900 dark:text-white">Saved Chair and Rapporteur Pool</h1>
                <p class="mt-3 max-w-3xl text-sm text-slate-500 dark:text-slate-400">
                    Upload the committee Excel once to save the pool. After that, verify live account links and approve session assignments from the saved records without starting over.
                </p>
            </div>
            <a href="{{ route('admin.session-role-applications.index') }}"
               class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-xs font-black uppercase tracking-widest text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950">
                Back to Applications
            </a>
        </div>

        @if(session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.session-role-applications.import-pool.preview') }}" enctype="multipart/form-data"
              class="mb-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            @csrf
            <div class="grid gap-4 lg:grid-cols-[1fr_240px_180px] lg:items-end">
                <div>
                    <label class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-400">Excel File</label>
                    <input type="file" name="pool_file" accept=".xlsx,.xls,.csv" required
                           class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 file:mr-4 file:rounded-xl file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-xs file:font-black file:uppercase file:tracking-widest file:text-indigo-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:file:bg-indigo-900/30 dark:file:text-indigo-200">
                </div>
                <div>
                    <label class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-400">Default Role</label>
                    <select name="default_role" class="w-full rounded-2xl border-slate-200 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                        <option value="rapporteur" {{ $selectedRole === 'rapporteur' ? 'selected' : '' }}>Rapporteur</option>
                        <option value="chair" {{ $selectedRole === 'chair' ? 'selected' : '' }}>Chair</option>
                    </select>
                </div>
                <button class="rounded-2xl bg-indigo-600 px-5 py-3 text-xs font-black uppercase tracking-widest text-white transition hover:bg-indigo-700">
                    Save / Refresh Pool
                </button>
            </div>
        </form>

        @if($people->isNotEmpty())
            <div class="mb-5 grid gap-4 md:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-2xl font-black text-slate-900 dark:text-white">{{ $people->count() }}</p>
                    <p class="mt-1 text-[10px] font-black uppercase tracking-widest text-slate-500">People Imported</p>
                </div>
                <div class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm dark:border-emerald-900/50 dark:bg-slate-900">
                    <p class="text-2xl font-black text-emerald-600">{{ $people->filter(fn($p) => $p['candidates']->count() === 1)->count() }}</p>
                    <p class="mt-1 text-[10px] font-black uppercase tracking-widest text-slate-500">Single Match</p>
                </div>
                <div class="rounded-2xl border border-amber-200 bg-white p-5 shadow-sm dark:border-amber-900/50 dark:bg-slate-900">
                    <p class="text-2xl font-black text-amber-600">{{ $people->filter(fn($p) => $p['candidates']->count() > 1)->count() }}</p>
                    <p class="mt-1 text-[10px] font-black uppercase tracking-widest text-slate-500">Needs Choice</p>
                </div>
                <div class="rounded-2xl border border-red-200 bg-white p-5 shadow-sm dark:border-red-900/50 dark:bg-slate-900">
                    <p class="text-2xl font-black text-red-600">{{ $people->filter(fn($p) => $p['candidates']->isEmpty())->count() }}</p>
                    <p class="mt-1 text-[10px] font-black uppercase tracking-widest text-slate-500">No Match</p>
                </div>
            </div>

            @if(!empty($assignmentWarnings))
                <div class="mb-5 rounded-3xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
                    <p class="mb-3 text-xs font-black uppercase tracking-widest">Assignment Warnings</p>
                    <ul class="space-y-2">
                        @foreach($assignmentWarnings as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(!empty($assignments))
                @php
                    $assignmentsBySession = collect($assignments)->groupBy('session_id');
                    $editableIndex = 0;
                @endphp
                <div class="mb-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <form method="POST" action="{{ route('admin.session-role-applications.import-pool.approve') }}">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-300">Generated Assignments</p>
                                <h2 class="mt-2 text-2xl font-black text-slate-900 dark:text-white">{{ count($assignmentsBySession) }} sessions, {{ count($assignments) }} role links</h2>
                                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                                    Each session is shown once. Change the linked chair or rapporteur here before approving.
                                </p>
                            </div>
                            @csrf
                            <button class="rounded-2xl bg-emerald-600 px-6 py-3 text-xs font-black uppercase tracking-widest text-white transition hover:bg-emerald-700">
                                Approve & Save Assignments
                            </button>
                        </div>

                        <div class="mt-6 overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                                <thead>
                                    <tr class="text-left text-[10px] font-black uppercase tracking-widest text-slate-400">
                                        <th class="px-3 py-3">Date</th>
                                        <th class="px-3 py-3">Time</th>
                                        <th class="px-3 py-3">Subtheme</th>
                                        <th class="px-3 py-3">Session</th>
                                        <th class="px-3 py-3">Role</th>
                                        <th class="px-3 py-3">Linked Account</th>
                                        <th class="px-3 py-3">Match</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @foreach($assignmentsBySession as $sessionAssignments)
                                        @php
                                            $firstAssignment = $sessionAssignments->first();
                                            $rowspan = $sessionAssignments->count();
                                        @endphp
                                        @foreach($sessionAssignments->sortBy('role') as $assignment)
                                            @php
                                                $options = $assignmentOptions[$assignment['role']][$assignment['subtheme_key']] ?? [];
                                            @endphp
                                            <tr class="text-slate-700 dark:text-slate-200">
                                                @if($loop->first)
                                                    <td rowspan="{{ $rowspan }}" class="border-b border-slate-100 px-3 py-4 align-top whitespace-nowrap dark:border-slate-800">{{ $firstAssignment['date'] }}</td>
                                                    <td rowspan="{{ $rowspan }}" class="border-b border-slate-100 px-3 py-4 align-top whitespace-nowrap dark:border-slate-800">{{ $firstAssignment['time'] }}</td>
                                                    <td rowspan="{{ $rowspan }}" class="border-b border-slate-100 px-3 py-4 align-top whitespace-nowrap font-bold dark:border-slate-800">{{ $firstAssignment['subtheme_key'] }}</td>
                                                    <td rowspan="{{ $rowspan }}" class="border-b border-slate-100 px-3 py-4 align-top min-w-[380px] dark:border-slate-800">{{ $firstAssignment['session_name'] }}</td>
                                                @endif
                                                <td class="px-3 py-3 whitespace-nowrap">{{ ucfirst($assignment['role']) }}</td>
                                                <td class="px-3 py-3 min-w-[280px]">
                                                    <input type="hidden" name="assignments[{{ $editableIndex }}][session_id]" value="{{ $assignment['session_id'] }}">
                                                    <input type="hidden" name="assignments[{{ $editableIndex }}][role]" value="{{ $assignment['role'] }}">
                                                    <input type="hidden" name="assignments[{{ $editableIndex }}][date]" value="{{ $assignment['date'] }}">
                                                    <select name="assignments[{{ $editableIndex }}][user_id]" required class="w-full rounded-2xl border-slate-200 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                                        @foreach($options as $option)
                                                            <option value="{{ $option['user_id'] }}" {{ (int) $option['user_id'] === (int) $assignment['user_id'] ? 'selected' : '' }}>
                                                                {{ $option['user_name'] }} - {{ $option['user_email'] }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="px-3 py-3 whitespace-nowrap">{{ $assignment['confidence'] }}</td>
                                            </tr>
                                            @php($editableIndex++)
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('admin.session-role-applications.import-pool.auto-assign') }}" class="mt-4">
                        @csrf
                        <button class="rounded-2xl bg-slate-900 px-6 py-3 text-xs font-black uppercase tracking-widest text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950">
                            Regenerate & Apply Current Program
                        </button>
                    </form>
                </div>
            @endif

            <div class="mb-4">
                <p class="text-[10px] font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-300">Verify Excel Account Links</p>
                <h2 class="mt-2 text-2xl font-black text-slate-900 dark:text-white">Commit matched accounts from the Excel pool</h2>
                <p class="mt-2 max-w-3xl text-sm text-slate-500 dark:text-slate-400">
                    Use this lower section only to confirm which live account belongs to each Excel person. Session assignment changes are handled in the generated assignment table above.
                </p>
            </div>

            <div class="space-y-4">
                @foreach($people as $person)
                    @php($candidates = $person['candidates'])
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="grid gap-6 xl:grid-cols-[340px_1fr]">
                            <div>
                                <div class="mb-3 flex flex-wrap gap-2">
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $person['source_sheet'] }}</span>
                                    @if($person['group'])
                                        <span class="rounded-full bg-indigo-50 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-200">{{ $person['group'] }}</span>
                                    @endif
                                </div>
                                <h2 class="text-lg font-black text-slate-900 dark:text-white">{{ $person['name'] }}</h2>
                                <div class="mt-2 space-y-1 text-sm text-slate-500 dark:text-slate-400">
                                    @if($person['institution'])<p>{{ $person['institution'] }}</p>@endif
                                    @if($person['region'])<p>{{ $person['region'] }}</p>@endif
                                    @if($person['status'])<p>{{ $person['status'] }}</p>@endif
                                </div>
                            </div>

                            @if($candidates->isEmpty())
                                <div class="rounded-2xl border border-dashed border-red-200 bg-red-50 p-5 text-sm font-semibold text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300">
                                    No matching user account was found. Create or confirm the person has registered, then upload again or search manually from user management.
                                </div>
                            @else
                                <div class="space-y-3">
                                    @foreach($candidates as $candidate)
                                        <form method="POST" action="{{ route('admin.session-role-applications.import-pool.verify') }}" data-verify-link-form
                                              class="grid gap-3 rounded-2xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950 lg:grid-cols-[1fr_180px_140px] lg:items-center">
                                            @csrf
                                            @if(!empty($person['imported_person_id']))
                                                <input type="hidden" name="imported_person_id" value="{{ $person['imported_person_id'] }}">
                                            @endif
                                            <input type="hidden" name="user_id" value="{{ $candidate['id'] }}">
                                            <input type="hidden" name="subtheme" value="{{ $person['group'] }}">
                                            <input type="hidden" name="source_name" value="{{ $person['name'] }}">
                                            <input type="hidden" name="institution" value="{{ $person['institution'] }}">
                                            <div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="font-black text-slate-900 dark:text-white">{{ $candidate['name'] }}</p>
                                                    <span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-widest {{ $candidate['confidence'] === 'Exact' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' }}">
                                                        {{ $candidate['confidence'] }}
                                                    </span>
                                                </div>
                                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $candidate['email'] }}</p>
                                                @if($candidate['affiliation'])
                                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $candidate['affiliation'] }}</p>
                                                @endif
                                            </div>

                                            <select name="role" class="rounded-2xl border-slate-200 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                                                <option value="rapporteur" {{ $person['default_role'] === 'rapporteur' ? 'selected' : '' }}>Rapporteur</option>
                                                <option value="chair" {{ $person['default_role'] === 'chair' ? 'selected' : '' }}>Chair</option>
                                            </select>

                                            <button data-verify-link-button class="rounded-2xl px-4 py-3 text-xs font-black uppercase tracking-widest text-white transition {{ !empty($person['verified_at']) && (int) $person['matched_user_id'] === (int) $candidate['id'] ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-slate-900 hover:bg-slate-800 dark:bg-white dark:text-slate-950' }}">
                                                {{ !empty($person['verified_at']) && (int) $person['matched_user_id'] === (int) $candidate['id'] ? 'Verified' : 'Verify Link' }}
                                            </button>
                                        </form>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-3xl border-2 border-dashed border-slate-200 bg-white p-12 text-center dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-lg font-black text-slate-900 dark:text-white">Upload the Excel to save the pool</p>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Once saved, the imported people and account links remain here for later verification and assignment.</p>
            </div>
        @endif
    </div>
</div>

<script>
document.querySelectorAll('[data-verify-link-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const button = form.querySelector('[data-verify-link-button]');
        const originalText = button.textContent.trim();

        button.disabled = true;
        button.textContent = 'Verifying...';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('Verification failed');
            }

            button.textContent = 'Verified';
            button.classList.remove('bg-slate-900', 'hover:bg-slate-800', 'dark:bg-white', 'dark:text-slate-950');
            button.classList.add('bg-emerald-600', 'hover:bg-emerald-700', 'text-white');
            form.classList.remove('border-slate-100', 'dark:border-slate-800');
            form.classList.add('border-emerald-200', 'dark:border-emerald-900');
        } catch (error) {
            button.disabled = false;
            button.textContent = originalText;
            alert('Could not verify this account link. Please try again.');
        }
    });
});
</script>
@endsection
