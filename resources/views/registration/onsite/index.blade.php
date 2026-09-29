@extends('layouts.app')

@section('title', 'Walk-In Visitors')

@section('content')
<div class="min-h-screen bg-[#fcfcfd] dark:bg-[#0a0a0b] font-sans pb-24">

    {{-- Header --}}
    <div class="relative min-h-[240px] flex items-center overflow-hidden bg-gradient-to-br from-violet-700 via-purple-700 to-indigo-800 rounded-b-[2rem] md:rounded-b-[4rem] shadow-[0_20px_50px_rgba(124,58,237,0.2)] mb-10">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[60%] bg-violet-500/20 rounded-full blur-[120px] animate-pulse pointer-events-none"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[60%] bg-indigo-700/20 rounded-full blur-[120px] pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-8 w-full relative z-10">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <a href="{{ route('registration.dashboard') }}" class="text-white/60 hover:text-white text-xs font-bold uppercase tracking-widest transition-colors">← Registration</a>
                        <span class="text-white/30">/</span>
                        <span class="text-white/80 text-xs font-bold uppercase tracking-widest">Walk-In Visitors</span>
                    </div>
                    <h1 class="text-3xl md:text-4xl font-black text-white leading-tight" style="font-family:'Outfit',sans-serif;">
                        Walk-In <span class="text-violet-200/70">Visitors.</span>
                    </h1>
                    <p class="text-violet-200/70 text-sm font-medium mt-1">Invitees and on-site guests — badge printed without registration.</p>
                </div>

                <button onclick="document.getElementById('add-modal').classList.remove('hidden'); document.getElementById('add-modal').classList.add('flex'); document.getElementById('add-name').focus();"
                        class="flex items-center gap-2 px-6 py-3.5 bg-white text-violet-700 font-black text-[10px] uppercase tracking-widest rounded-2xl shadow-lg hover:scale-105 active:scale-95 transition-all self-start lg:self-auto">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add New Visitor
                </button>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-8">

        {{-- Search --}}
        <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] border border-slate-100 dark:border-gray-700 shadow-[0_20px_50px_rgba(0,0,0,0.03)] p-5 mb-8">
            <form method="GET" action="{{ route('registration.onsite.index') }}" class="flex gap-4">
                <div class="flex-1 relative">
                    <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search visitor name or institution…"
                           class="w-full pl-12 pr-5 py-3.5 bg-slate-50 dark:bg-gray-950 border-none rounded-2xl text-sm font-bold focus:ring-2 focus:ring-violet-500/40 dark:text-white shadow-inner">
                </div>
                <button type="submit" class="px-8 py-3.5 bg-violet-600 text-white font-black text-[10px] uppercase tracking-[0.2em] rounded-2xl hover:bg-violet-700 shadow-lg transition-all">Search</button>
                @if($search)
                <a href="{{ route('registration.onsite.index') }}" class="px-5 py-3.5 bg-slate-100 dark:bg-gray-700 text-slate-500 font-black text-[10px] uppercase tracking-widest rounded-2xl hover:bg-slate-200 transition-all">Clear</a>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-[3rem] shadow-[0_30px_60px_rgba(0,0,0,0.03)] border border-slate-100 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-50 dark:divide-gray-700">
                    <thead class="bg-slate-50/60 dark:bg-gray-900/60">
                        <tr>
                            <th class="px-8 py-5 text-left text-[9px] font-black text-slate-400 uppercase tracking-[0.15em]">Visitor</th>
                            <th class="px-8 py-5 text-left text-[9px] font-black text-slate-400 uppercase tracking-[0.15em]">Institution</th>
                            <th class="px-8 py-5 text-left text-[9px] font-black text-slate-400 uppercase tracking-[0.15em]">Category</th>
                            <th class="px-8 py-5 text-left text-[9px] font-black text-slate-400 uppercase tracking-[0.15em]">Badge</th>
                            <th class="px-8 py-5 text-left text-[9px] font-black text-slate-400 uppercase tracking-[0.15em]">Added</th>
                            <th class="px-8 py-5 text-right text-[9px] font-black text-slate-400 uppercase tracking-[0.15em]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-gray-700">
                        @forelse($visitors as $visitor)
                        @php
                            $categoryColors = [
                                'invitee' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                'vip'     => 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400',
                                'guest'   => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                                'media'   => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
                                'staff'   => 'bg-slate-100 text-slate-600 dark:bg-gray-700 dark:text-gray-300',
                                'speaker' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
                            ];
                            $cc = $categoryColors[$visitor->badge_category] ?? $categoryColors['invitee'];
                        @endphp
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-gray-700/30 transition-all group" id="visitor-row-{{ $visitor->id }}">
                            {{-- Name cell --}}
                            <td class="px-8 py-5">
                                <div class="flex items-center gap-4">
                                    <div class="visitor-avatar w-10 h-10 rounded-2xl bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center text-white font-black text-sm shadow-md flex-shrink-0">
                                        {{ $visitor->initials }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        {{-- Display state --}}
                                        <div class="visitor-name-display flex items-center gap-2">
                                            <p class="visitor-name-text text-sm font-black text-slate-900 dark:text-white">{{ $visitor->effective_name }}</p>
                                            <button onclick="startEditVisitor({{ $visitor->id }})"
                                                    class="opacity-0 group-hover:opacity-100 transition-opacity p-1 rounded-lg text-slate-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/20"
                                                    title="Edit print name">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            </button>
                                        </div>
                                        {{-- Original name hint when overridden --}}
                                        @if($visitor->print_name && $visitor->print_name !== $visitor->name)
                                            <p class="text-[9px] text-slate-400 mt-0.5">Original: {{ $visitor->name }}</p>
                                        @endif
                                        {{-- Edit state --}}
                                        <div class="visitor-name-edit hidden mt-1">
                                            <input type="text" class="visitor-name-input w-full px-3 py-1.5 text-sm font-bold border-2 border-violet-400 rounded-xl focus:outline-none focus:border-violet-600 bg-white dark:bg-slate-800 dark:text-white"
                                                   value="{{ $visitor->effective_name }}" data-original="{{ $visitor->effective_name }}">
                                            <div class="flex items-center gap-2 mt-1.5">
                                                <button onclick="saveVisitorName({{ $visitor->id }}, '{{ route('registration.onsite.update', $visitor) }}')"
                                                        class="px-3 py-1 bg-violet-600 text-white text-[9px] font-black uppercase tracking-widest rounded-lg hover:bg-violet-700 transition-all flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    Save
                                                </button>
                                                <button onclick="cancelEditVisitor({{ $visitor->id }})"
                                                        class="px-3 py-1 bg-slate-100 dark:bg-gray-700 text-slate-500 dark:text-white text-[9px] font-black uppercase tracking-widest rounded-lg hover:bg-slate-200 transition-all">
                                                    Cancel
                                                </button>
                                            </div>
                                        </div>
                                        @if($visitor->notes)
                                            <p class="text-[10px] text-slate-400 truncate max-w-[160px] mt-0.5">{{ $visitor->notes }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            {{-- Institution cell --}}
                            <td class="px-8 py-5">
                                <div class="visitor-inst-display flex items-center gap-2">
                                    <p class="visitor-inst-text text-sm font-bold text-slate-600 dark:text-slate-300">{{ $visitor->institution ?: '—' }}</p>
                                    <button onclick="startEditInstitution({{ $visitor->id }})"
                                            class="opacity-0 group-hover:opacity-100 transition-opacity p-1 rounded-lg text-slate-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/20"
                                            title="Edit institution">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>
                                </div>
                                <div class="visitor-inst-edit hidden mt-1">
                                    <input type="text" class="visitor-inst-input w-full px-3 py-1.5 text-sm font-bold border-2 border-violet-400 rounded-xl focus:outline-none focus:border-violet-600 bg-white dark:bg-slate-800 dark:text-white"
                                           value="{{ $visitor->institution }}" data-original="{{ $visitor->institution }}">
                                    <div class="flex items-center gap-2 mt-1.5">
                                        <button onclick="saveVisitorInstitution({{ $visitor->id }}, '{{ route('registration.onsite.update', $visitor) }}')"
                                                class="px-3 py-1 bg-violet-600 text-white text-[9px] font-black uppercase tracking-widest rounded-lg hover:bg-violet-700 transition-all flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Save
                                        </button>
                                        <button onclick="cancelEditInstitution({{ $visitor->id }})"
                                                class="px-3 py-1 bg-slate-100 dark:bg-gray-700 text-slate-500 dark:text-white text-[9px] font-black uppercase tracking-widest rounded-lg hover:bg-slate-200 transition-all">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-5">
                                <span class="px-3 py-1.5 {{ $cc }} rounded-xl text-[9px] font-black uppercase tracking-widest">
                                    {{ $visitor->category_label }}
                                </span>
                            </td>
                            <td class="px-8 py-5">
                                @if($visitor->badge_printed)
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></div>
                                        <span class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Printed</span>
                                    </div>
                                    <p class="text-[9px] text-slate-400 mt-0.5">{{ $visitor->badge_printed_at->format('d M, g:i A') }}</p>
                                @else
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-1.5 h-1.5 bg-amber-400 rounded-full animate-pulse"></div>
                                        <span class="text-[10px] font-black text-amber-600 uppercase tracking-widest">Not Printed</span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-8 py-5">
                                <p class="text-xs text-slate-500 font-medium">{{ $visitor->created_at->format('d M Y') }}</p>
                                <p class="text-[9px] text-slate-400">{{ $visitor->createdBy?->full_name ?? 'Staff' }}</p>
                            </td>
                            <td class="px-8 py-5 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button onclick="copyClaimLink(this, '{{ route('certificate.claim', ['code' => $visitor->qr_token]) }}')"
                                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 text-white font-black text-[9px] uppercase tracking-widest rounded-xl shadow hover:bg-emerald-700 transition-all hover:shadow-emerald-600/20">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 010 5.656l-3 3a4 4 0 01-5.656-5.656l1.5-1.5m8.5-2.5l1.5-1.5a4 4 0 10-5.656-5.656l-3 3a4 4 0 000 5.656"/></svg>
                                        <span>Copy Claim Link</span>
                                    </button>
                                    <a href="{{ route('registration.onsite.print-badge', $visitor) }}" target="_blank"
                                       class="inline-flex items-center gap-2 px-5 py-2.5 bg-violet-600 text-white font-black text-[9px] uppercase tracking-widest rounded-xl shadow hover:bg-violet-700 transition-all hover:shadow-violet-600/20">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2z"/></svg>
                                        {{ $visitor->badge_printed ? 'Reprint' : 'Print Badge' }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-10 py-24 text-center">
                                <div class="w-16 h-16 bg-violet-50 dark:bg-violet-900/10 rounded-[2rem] flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-violet-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                </div>
                                <h4 class="text-lg font-black text-slate-900 dark:text-white">No walk-in visitors yet.</h4>
                                <p class="text-sm text-slate-400 mt-1 mb-5">Add a visitor above to generate and print their badge instantly.</p>
                                <button onclick="document.getElementById('add-modal').classList.remove('hidden'); document.getElementById('add-modal').classList.add('flex');"
                                        class="px-6 py-3 bg-violet-600 text-white font-black text-[10px] uppercase tracking-widest rounded-xl shadow-lg hover:bg-violet-700 transition-all">
                                    Add First Visitor
                                </button>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($visitors->hasPages())
            <div class="px-8 py-6 bg-slate-50/60 dark:bg-gray-900/50 border-t border-slate-50 dark:border-gray-700">
                {{ $visitors->withQueryString()->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Add Visitor Modal --}}
<div id="add-modal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-xl hidden items-center justify-center z-[100] p-4">
    <div class="bg-white dark:bg-gray-900 rounded-[3rem] w-full max-w-lg shadow-2xl overflow-hidden">

        {{-- Phase 1: Entry form --}}
        <div id="modal-phase-entry">
            <div class="p-8 border-b border-slate-100 dark:border-gray-800 flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white">Add Walk-In Visitor</h2>
                    <p class="text-sm text-slate-400 mt-0.5">Badge is printed immediately after saving.</p>
                </div>
                <button onclick="closeAddModal()" class="p-2 rounded-xl text-slate-400 hover:bg-slate-100 dark:hover:bg-gray-800 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form id="add-visitor-form" class="p-8 space-y-5">
                @csrf
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Full Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="add-name" required placeholder="e.g. Prof. Jane Smith"
                           class="w-full px-4 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-gray-600 rounded-2xl text-sm font-bold dark:text-white focus:ring-2 focus:ring-violet-500/30 focus:border-violet-500 transition-all">
                    <div id="add-flags" class="hidden mt-2 space-y-1 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-700/50">
                        <p class="text-[9px] font-black text-amber-600 uppercase tracking-widest">Data Quality Warning</p>
                        <div id="add-flag-list"></div>
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Institution</label>
                    <input type="text" name="institution" id="add-institution" placeholder="Organization or affiliation"
                           class="w-full px-4 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-gray-600 rounded-2xl text-sm font-bold dark:text-white focus:ring-2 focus:ring-violet-500/30 focus:border-violet-500 transition-all">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Badge Category</label>
                        <select name="badge_category"
                                class="w-full px-4 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-gray-600 rounded-2xl text-sm font-bold dark:text-white focus:ring-2 focus:ring-violet-500/30 focus:border-violet-500 transition-all">
                            @foreach($categories as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Notes (optional)</label>
                        <input type="text" name="notes" placeholder="e.g. Ministry guest"
                               class="w-full px-4 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-gray-600 rounded-2xl text-sm font-bold dark:text-white focus:ring-2 focus:ring-violet-500/30 focus:border-violet-500 transition-all">
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeAddModal()" class="flex-1 py-4 bg-slate-100 dark:bg-gray-700 text-slate-600 dark:text-white font-black text-[10px] uppercase tracking-widest rounded-2xl hover:bg-slate-200 transition-all">Cancel</button>
                    <button type="submit" id="add-submit" class="flex-1 py-4 bg-violet-600 text-white font-black text-[10px] uppercase tracking-widest rounded-2xl shadow-lg hover:bg-violet-700 active:scale-95 transition-all flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Save & Print Badge
                    </button>
                </div>
            </form>
        </div>

        {{-- Phase 2: Review + name edit (always shown after creation) --}}
        <div id="modal-phase-flags" class="hidden">
            <div class="p-8 border-b border-slate-100 dark:border-gray-800 flex items-center gap-4">
                <div id="review-icon-wrap" class="w-10 h-10 rounded-2xl flex items-center justify-center flex-shrink-0">
                    {{-- icon injected by JS --}}
                </div>
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white">Review Before Printing</h2>
                    <p id="review-subtitle" class="text-sm mt-0.5 font-semibold"></p>
                </div>
            </div>

            <div class="p-8 space-y-5">
                {{-- Flag list (hidden when no flags) --}}
                <div id="flag-review-list" class="hidden space-y-2 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-2xl border border-amber-200 dark:border-amber-700/50"></div>

                {{-- Editable name --}}
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Print Name</label>
                    <input type="text" id="flag-edit-name"
                           class="w-full px-4 py-3.5 bg-white dark:bg-slate-800 border-2 border-violet-400 rounded-2xl text-sm font-bold dark:text-white focus:ring-2 focus:ring-violet-500/30 focus:border-violet-600 transition-all"
                           placeholder="Name as it should appear on badge">
                    <p class="text-[9px] text-slate-400 mt-1">This overrides the submitted name only on the badge. The original is preserved for admin records.</p>
                </div>

                {{-- Editable institution --}}
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Institution</label>
                    <input type="text" id="flag-edit-institution"
                           class="w-full px-4 py-3.5 bg-white dark:bg-slate-800 border-2 border-violet-400 rounded-2xl text-sm font-bold dark:text-white focus:ring-2 focus:ring-violet-500/30 focus:border-violet-600 transition-all"
                           placeholder="Organization or affiliation">
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" id="flag-print-anyway"
                            class="flex-1 py-4 bg-slate-100 dark:bg-gray-700 text-slate-500 dark:text-white font-black text-[10px] uppercase tracking-widest rounded-2xl hover:bg-slate-200 transition-all">
                        Print As-Is
                    </button>
                    <button type="button" id="flag-save-print"
                            class="flex-1 py-4 bg-violet-600 text-white font-black text-[10px] uppercase tracking-widest rounded-2xl shadow-lg hover:bg-violet-700 active:scale-95 transition-all flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Save &amp; Print
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

function copyClaimLink(btn, url) {
    navigator.clipboard.writeText(url).then(() => {
        const label = btn.querySelector('span');
        const original = label.textContent;
        label.textContent = 'Copied!';
        setTimeout(() => { label.textContent = original; }, 2000);
    }).catch(() => {
        window.prompt('Copy this claim link:', url);
    });
}

function closeAddModal() {
    document.getElementById('add-modal').classList.add('hidden');
    document.getElementById('add-modal').classList.remove('flex');
    // Reset to entry phase
    document.getElementById('modal-phase-entry').classList.remove('hidden');
    document.getElementById('modal-phase-flags').classList.add('hidden');
    document.getElementById('add-visitor-form').reset();
    document.getElementById('add-flags').classList.add('hidden');
    const btn = document.getElementById('add-submit');
    btn.disabled = false;
    btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg> Save & Print Badge';
}

// Live quality check on name field
const addName = document.getElementById('add-name');
const addInst = document.getElementById('add-institution');
const addFlags = document.getElementById('add-flags');
const addFlagList = document.getElementById('add-flag-list');

function liveCheck() {
    const n = addName.value.trim();
    const inst = addInst.value.trim();
    const flags = [];
    if (n.includes('@') || /\b[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}\b/i.test(n)) flags.push('Name contains an email address.');
    if (n.length > 3 && n === n.toUpperCase() && /^[A-Z\s.\-]+$/.test(n)) flags.push('Name appears to be all caps.');
    if (inst.includes('@')) flags.push('Institution contains an email address.');
    if (/https?:\/\//i.test(n) || /https?:\/\//i.test(inst)) flags.push('URL detected.');
    if (flags.length && n) {
        addFlagList.innerHTML = flags.map(f => `<p class="text-[10px] text-amber-700 dark:text-amber-400">• ${f}</p>`).join('');
        addFlags.classList.remove('hidden');
    } else {
        addFlags.classList.add('hidden');
    }
}
addName.addEventListener('input', liveCheck);
addInst.addEventListener('input', liveCheck);

// Shared state for flag phase
let _pendingVisitorId = null;
let _pendingPrintUrl  = null;

function doPrint(printUrl) {
    window.open(printUrl, '_blank');
    closeAddModal();
    setTimeout(() => location.reload(), 500);
}

function showReviewPhase(visitorId, printUrl, flags, name, institution) {
    _pendingVisitorId = visitorId;
    _pendingPrintUrl  = printUrl;

    const flagList   = document.getElementById('flag-review-list');
    const iconWrap   = document.getElementById('review-icon-wrap');
    const subtitle   = document.getElementById('review-subtitle');

    if (flags && flags.length) {
        flagList.classList.remove('hidden');
        flagList.innerHTML =
            '<p class="text-[9px] font-black text-amber-600 uppercase tracking-widest mb-2">Issues found</p>' +
            flags.map(f => `<p class="text-[11px] text-amber-700 dark:text-amber-400 font-semibold">• ${f}</p>`).join('');
        iconWrap.className = 'w-10 h-10 rounded-2xl bg-amber-100 flex items-center justify-center flex-shrink-0';
        iconWrap.innerHTML = '<svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>';
        subtitle.className = 'text-sm mt-0.5 font-semibold text-amber-600';
        subtitle.textContent = 'Data quality issues detected — correct the name if needed.';
    } else {
        flagList.classList.add('hidden');
        iconWrap.className = 'w-10 h-10 rounded-2xl bg-emerald-100 flex items-center justify-center flex-shrink-0';
        iconWrap.innerHTML = '<svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
        subtitle.className = 'text-sm mt-0.5 font-semibold text-emerald-600';
        subtitle.textContent = 'Looks good — edit the name if needed before printing.';
    }

    document.getElementById('flag-edit-name').value = name;
    document.getElementById('flag-edit-institution').value = institution;

    document.getElementById('modal-phase-entry').classList.add('hidden');
    document.getElementById('modal-phase-flags').classList.remove('hidden');
    document.getElementById('flag-edit-name').focus();
}

document.getElementById('add-visitor-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('add-submit');
    btn.disabled = true;
    btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Processing…';

    const fd = new FormData(this);
    fetch('{{ route("registration.onsite.create") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: fd,
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) return;
        showReviewPhase(data.visitor_id, data.print_url, data.flags || [],
            document.getElementById('add-name').value,
            document.getElementById('add-institution').value);
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = 'Save & Print Badge';
    });
});

// Review phase: print without changes
document.getElementById('flag-print-anyway').addEventListener('click', function() {
    doPrint(_pendingPrintUrl);
});

// Review phase: save print_name override then print
document.getElementById('flag-save-print').addEventListener('click', function() {
    const btn = this;
    const printName = document.getElementById('flag-edit-name').value.trim();
    const institution = document.getElementById('flag-edit-institution').value.trim();

    // If nothing changed just print
    if (!printName) { doPrint(_pendingPrintUrl); return; }

    btn.disabled = true;
    btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Saving…';

    const body = new URLSearchParams({
        _method: 'PATCH',
        print_name: printName,
        institution: institution,
    });

    fetch(`/registration/onsite/${_pendingVisitorId}`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
    })
    .then(r => r.json())
    .then(data => { doPrint(data.print_url || _pendingPrintUrl); })
    .catch(() => { doPrint(_pendingPrintUrl); });
});

// ── Inline row editing ────────────────────────────────────────────────────────

function row(id) { return document.getElementById('visitor-row-' + id); }

function startEditVisitor(id) {
    const r = row(id);
    r.querySelector('.visitor-name-display').classList.add('hidden');
    r.querySelector('.visitor-name-edit').classList.remove('hidden');
    r.querySelector('.visitor-name-input').focus();
}
function cancelEditVisitor(id) {
    const r = row(id);
    const inp = r.querySelector('.visitor-name-input');
    inp.value = inp.dataset.original;
    r.querySelector('.visitor-name-edit').classList.add('hidden');
    r.querySelector('.visitor-name-display').classList.remove('hidden');
}
function saveVisitorName(id, url) {
    const r = row(id);
    const inp = r.querySelector('.visitor-name-input');
    const printName = inp.value.trim();
    if (!printName) return;

    patchVisitor(id, url, { print_name: printName }, (data) => {
        r.querySelector('.visitor-name-text').textContent = data.effective_name || printName;
        inp.dataset.original = data.effective_name || printName;

        // Update avatar initials from server response
        if (data.initials) r.querySelector('.visitor-avatar').textContent = data.initials;

        cancelEditVisitor(id);
    });
}

function startEditInstitution(id) {
    const r = row(id);
    r.querySelector('.visitor-inst-display').classList.add('hidden');
    r.querySelector('.visitor-inst-edit').classList.remove('hidden');
    r.querySelector('.visitor-inst-input').focus();
}
function cancelEditInstitution(id) {
    const r = row(id);
    const inp = r.querySelector('.visitor-inst-input');
    inp.value = inp.dataset.original;
    r.querySelector('.visitor-inst-edit').classList.add('hidden');
    r.querySelector('.visitor-inst-display').classList.remove('hidden');
}
function saveVisitorInstitution(id, url) {
    const r = row(id);
    const inp = r.querySelector('.visitor-inst-input');
    const inst = inp.value.trim();

    patchVisitor(id, url, { institution: inst }, (data) => {
        r.querySelector('.visitor-inst-text').textContent = inst || '—';
        inp.dataset.original = inst;
        cancelEditInstitution(id);
    });
}

function patchVisitor(id, url, fields, onSuccess) {
    const body = new URLSearchParams({ _method: 'PATCH', ...fields });
    fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
    })
    .then(r => r.json())
    .then(data => { if (data.success) onSuccess(data); })
    .catch(() => {});
}

// Save on Enter, cancel on Escape in edit inputs
document.addEventListener('keydown', function(e) {
    if (!e.target.classList.contains('visitor-name-input') && !e.target.classList.contains('visitor-inst-input')) return;
    const rowEl = e.target.closest('tr');
    const id = rowEl?.id?.replace('visitor-row-', '');
    if (!id) return;
    const url = e.target.closest('td').querySelector('[onclick*="save"]')?.getAttribute('onclick')?.match(/'([^']+)'/)?.[1];
    if (e.key === 'Enter' && url) {
        if (e.target.classList.contains('visitor-name-input')) saveVisitorName(id, url);
        else saveVisitorInstitution(id, url);
    }
    if (e.key === 'Escape') {
        if (e.target.classList.contains('visitor-name-input')) cancelEditVisitor(id);
        else cancelEditInstitution(id);
    }
});
</script>
@endpush
@endsection
