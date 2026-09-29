@extends('layouts.app')

@section('title', 'Student Verification Hub')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 dark:from-gray-900 dark:via-slate-900 dark:to-gray-900">
    <div class="relative max-w-[1600px] mx-auto px-6 py-10">

        {{-- Executive Header System --}}
        <div class="relative overflow-hidden mb-12">
            <div class="absolute inset-0 opacity-40 pointer-events-none">
                <div class="absolute top-0 right-1/4 w-96 h-96 bg-blue-200 dark:bg-blue-500/20 rounded-full blur-3xl animate-pulse"></div>
                <div class="absolute -bottom-24 left-1/4 w-80 h-80 bg-emerald-200 dark:bg-emerald-500/10 rounded-full blur-3xl"></div>
            </div>

            <div class="relative flex flex-col lg:flex-row lg:items-center justify-between gap-8 z-10">
                <div class="flex items-center gap-6">
                    <div class="p-5 bg-slate-900 dark:bg-white rounded-[2rem] shadow-2xl shadow-slate-200/50 dark:shadow-none group">
                        <svg class="w-10 h-10 text-white dark:text-slate-900 group-hover:rotate-12 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight">Student Verification</h1>
                        <div class="flex items-center gap-3 mt-2">
                            <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 text-[10px] font-black uppercase rounded-md border border-blue-500/20">Quality Assurance</span>
                            <p class="text-slate-500 dark:text-slate-400 text-sm font-medium tracking-wide">Reviewing and certifying student credentials for reduced rates.</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-4">
                    <div class="relative group">
                        <input type="text" id="studentSearch" placeholder="Search by name or email..."
                               class="w-full md:w-80 px-6 py-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all font-bold text-slate-900 dark:text-white dark:placeholder-slate-500 pl-14">
                        <div class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tiered Metrics Console --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <div class="p-6 bg-white/70 dark:bg-slate-800/50 backdrop-blur-md rounded-[2.5rem] border border-white dark:border-slate-700/50 shadow-xl shadow-slate-200/50 dark:shadow-none group transition-all hover:scale-[1.02]">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-2 bg-amber-100 dark:bg-amber-500/20 rounded-xl">
                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-[10px] font-black text-amber-600 dark:text-amber-400 uppercase tracking-widest">Queue Status</span>
                </div>
                <p class="text-3xl font-black text-slate-900 dark:text-white leading-none">{{ number_format($stats['pending']) }}</p>
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mt-2 tracking-widest">Pending Review</p>
            </div>

            <div class="p-6 bg-white/70 dark:bg-slate-800/50 backdrop-blur-md rounded-[2.5rem] border border-white dark:border-slate-700/50 shadow-xl shadow-slate-200/50 dark:shadow-none group transition-all hover:scale-[1.02]">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-2 bg-emerald-100 dark:bg-emerald-500/20 rounded-xl">
                        <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">Efficiency</span>
                </div>
                <p class="text-3xl font-black text-slate-900 dark:text-white leading-none">{{ number_format($stats['verified']) }}</p>
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mt-2 tracking-widest">Verified Students</p>
            </div>

            <div class="p-6 bg-white/70 dark:bg-slate-800/50 backdrop-blur-md rounded-[2.5rem] border border-white dark:border-slate-700/50 shadow-xl shadow-slate-200/50 dark:shadow-none group transition-all hover:scale-[1.02]">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-2 bg-rose-100 dark:bg-rose-500/20 rounded-xl">
                        <svg class="w-6 h-6 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-[10px] font-black text-rose-600 dark:text-rose-400 uppercase tracking-widest">Rejections</span>
                </div>
                <p class="text-3xl font-black text-slate-900 dark:text-white leading-none">{{ number_format($stats['rejected']) }}</p>
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mt-2 tracking-widest">Rejected Claims</p>
            </div>

            <div class="p-6 bg-slate-900 rounded-[2.5rem] border border-slate-800 shadow-2xl transition-all hover:scale-[1.02]">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-2 bg-slate-800 rounded-xl">
                        <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-2.239"/></svg>
                    </div>
                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Total Volume</span>
                </div>
                <p class="text-3xl font-black text-white leading-none">{{ number_format($stats['total']) }}</p>
                <p class="text-xs font-bold text-slate-600 uppercase mt-2 tracking-widest">Cumulative Students</p>
            </div>
        </div>

        {{-- Tactical Filter Console --}}
        <div class="bg-white/50 dark:bg-slate-800/50 backdrop-blur-xl rounded-[2.5rem] border border-white dark:border-slate-700 shadow-xl mb-12 p-2">
            <div class="flex flex-wrap items-center gap-2 p-4">
                <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-4 mr-4">Selection Tier</span>

                @foreach([
                    'pending' => ['label' => 'Pending Review', 'icon' => '⏳'],
                    'verified' => ['label' => 'Verified Students', 'icon' => '✅'],
                    'rejected' => ['label' => 'Rejected Claims', 'icon' => '❌'],
                    'all' => ['label' => 'All Registry', 'icon' => '📚']
                ] as $key => $filter)
                    <a href="{{ route('admin.students.index', ['status' => $key]) }}"
                        class="px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all
                        {{ ($status === $key)
                            ? 'bg-slate-900 dark:bg-white text-white dark:text-slate-900 shadow-xl shadow-slate-900/10'
                            : 'bg-white/50 dark:bg-slate-900/50 text-slate-500 hover:bg-white dark:hover:bg-slate-700 border border-slate-100 dark:border-slate-800' }}">
                        <span class="mr-2">{{ $filter['icon'] }}</span>
                        {{ $filter['label'] }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Verification Ledger --}}
        <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-2xl rounded-[3rem] border border-white dark:border-slate-700 shadow-2xl overflow-hidden">
            <div class="px-10 py-8 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-black text-slate-900 dark:text-white leading-none">Verification Ledger</h3>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-2">Authenticated Student Registry</p>
                </div>
                <div>
                    <span class="px-4 py-1.5 bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400 text-[10px] font-black uppercase tracking-widest rounded-full border border-slate-200 dark:border-slate-700">
                        Total Items: {{ $students->total() }}
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-50/50 dark:bg-slate-900/50">
                            <th class="px-10 py-6 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Student Profile</th>
                            <th class="px-10 py-6 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Category & Status</th>
                            <th class="px-10 py-6 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Credential ID</th>
                            <th class="px-10 py-6 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700 text-slate-700 dark:text-slate-300">
                        @forelse ($students as $student)
                            <tr class="group hover:bg-blue-50/30 dark:hover:bg-blue-900/10 transition-all duration-300">
                                <td class="px-10 py-8">
                                    <div class="flex items-center gap-5">
                                        <div class="h-14 w-14 rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-700 dark:to-slate-600 text-slate-600 dark:text-slate-300 flex items-center justify-center font-black text-xl shadow-inner border border-white dark:border-slate-700">
                                            {{ strtoupper(substr($student->first_name, 0, 1) . substr($student->last_name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="text-base font-black text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                                {{ $student->first_name }} {{ $student->last_name }}
                                            </p>
                                            <p class="text-xs font-bold text-slate-400 tracking-wide mt-0.5">{{ $student->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-10 py-8">
                                    <div class="flex flex-col gap-2">
                                        <span class="inline-flex items-center px-4 py-1 rounded-xl text-[10px] font-black uppercase tracking-widest border bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-200/50 w-fit">
                                            {{ str_replace('_', ' ', $student->registration_category) }}
                                        </span>
                                        @if($student->student_verification_status === 'verified')
                                            <span class="inline-flex items-center px-4 py-1 rounded-xl text-[10px] font-black uppercase tracking-widest border bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-200/50 w-fit">
                                                Verified
                                            </span>
                                        @elseif($student->student_verification_status === 'rejected')
                                            <span class="inline-flex items-center px-4 py-1 rounded-xl text-[10px] font-black uppercase tracking-widest border bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-200/50 w-fit">
                                                Rejected
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-4 py-1 rounded-xl text-[10px] font-black uppercase tracking-widest border bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-200/50 w-fit">
                                                Pending Review
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-10 py-8">
                                    @if ($student->student_document)
                                        <a href="{{ asset('storage/' . $student->student_document) }}" target="_blank"
                                           class="inline-flex items-center gap-3 px-6 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs font-black uppercase tracking-widest text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 hover:scale-105 transition-all shadow-sm group/btn">
                                            <svg class="w-5 h-5 text-blue-500 group-hover/btn:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            View Student ID
                                        </a>
                                    @else
                                        <span class="text-xs font-bold text-slate-400 italic">No document uploaded</span>
                                    @endif
                                </td>
                                <td class="px-10 py-8 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        @if($student->student_verification_status === 'pending')
                                            <button onclick="openVerifyModal({{ $student->id }}, {{ json_encode($student->full_name) }})"
                                                    class="p-4 bg-emerald-500 hover:bg-emerald-600 text-white rounded-2xl shadow-lg shadow-emerald-200 transition-all hover:-translate-y-1">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            </button>
                                            <button onclick="openRejectModal({{ $student->id }}, {{ json_encode($student->full_name) }})"
                                                    class="p-4 bg-rose-500 hover:bg-rose-600 text-white rounded-2xl shadow-lg shadow-rose-200 transition-all hover:-translate-y-1">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        @else
                                            <div class="text-right">
                                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Reviewed At</p>
                                                <p class="text-xs font-bold text-slate-600">{{ $student->student_verified_at?->format('M d, Y H:i') }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-10 py-20 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-20 h-20 bg-slate-50 dark:bg-slate-900 rounded-3xl flex items-center justify-center mb-4 text-4xl">📭</div>
                                        <h3 class="text-xl font-black text-slate-900 dark:text-white">No Verification Protocol Found</h3>
                                        <p class="text-slate-400 font-bold text-xs mt-2 uppercase tracking-widest">Registry is clean for this classification tier.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($students->hasPages())
                <div class="px-10 py-8 border-t border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50">
                    {{ $students->appends(['status' => $status])->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Verification Modals --}}
<div id="verifyModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md hidden z-[100] p-6 items-center justify-center">
    <div class="bg-white dark:bg-slate-800 rounded-[3rem] p-10 w-full max-w-lg shadow-2xl border border-white dark:border-slate-700 animate-in zoom-in duration-300">
        <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight mb-2">Certify Student Credentials</h3>
        <p class="text-xs font-bold text-slate-400 mb-8 tracking-wide">Enter optional notes for the verification audit trail for <span id="vStudentName" class="text-emerald-500 font-black"></span>.</p>

        <form id="verifyForm" method="POST">
            @csrf
            <div class="mb-8">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Audit Notes</label>
                <textarea name="notes" rows="4" class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none text-sm font-bold resize-none" placeholder="Verified against institutional database..."></textarea>
            </div>
            <div class="flex gap-4">
                <button type="button" onclick="closeModal('verifyModal')" class="flex-1 py-4 text-xs font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-all">Abort</button>
                <button type="submit" class="flex-1 py-4 bg-emerald-500 text-white text-xs font-black uppercase tracking-widest rounded-2xl shadow-xl shadow-emerald-200 hover:-translate-y-1 transition-all">Certify Protocol</button>
            </div>
        </form>
    </div>
</div>

<div id="rejectModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md hidden z-[100] p-6 items-center justify-center">
    <div class="bg-white dark:bg-slate-800 rounded-[3rem] p-10 w-full max-w-lg shadow-2xl border border-white dark:border-slate-700 animate-in zoom-in duration-300">
        <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight mb-2">Reject Claim</h3>
        <p class="text-xs font-bold text-slate-400 mb-8 tracking-wide">Provide a mandatory reason for rejecting student status for <span id="rStudentName" class="text-rose-500 font-black"></span>.</p>

        <form id="rejectForm" method="POST">
            @csrf
            <div class="mb-8">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Rejection Reason (Required)</label>
                <textarea name="notes" rows="4" required class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 outline-none text-sm font-bold resize-none" placeholder="Document expired or invalid..."></textarea>
            </div>
            <div class="flex gap-4">
                <button type="button" onclick="closeModal('rejectModal')" class="flex-1 py-4 text-xs font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-all">Abort</button>
                <button type="submit" class="flex-1 py-4 bg-rose-500 text-white text-xs font-black uppercase tracking-widest rounded-2xl shadow-xl shadow-rose-200 hover:-translate-y-1 transition-all">Execute Rejection</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openVerifyModal(id, name) {
        document.getElementById('vStudentName').textContent = name;
        document.getElementById('verifyForm').action = `/admin/students/${id}/verify`;
        document.getElementById('verifyModal').classList.remove('hidden');
        document.getElementById('verifyModal').classList.add('flex');
    }

    function openRejectModal(id, name) {
        document.getElementById('rStudentName').textContent = name;
        document.getElementById('rejectForm').action = `/admin/students/${id}/reject`;
        document.getElementById('rejectModal').classList.remove('hidden');
        document.getElementById('rejectModal').classList.add('flex');
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
        document.getElementById(id).classList.remove('flex');
    }

    // Live Search
    let searchTimeout;
    document.getElementById('studentSearch').addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const query = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr:not(.empty-row)');

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                if(text.includes(query)) {
                    row.classList.remove('hidden');
                } else {
                    row.classList.add('hidden');
                }
            });
        }, 300);
    });
</script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;900&display=swap');
    :root { font-family: 'Outfit', sans-serif; }
</style>
@endsection
