<section class="max-w-4xl">
    <div class="mb-10">
        <h3 class="text-xs font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-[0.2em] mb-2">Dashboard Configuration</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 font-medium leading-relaxed">
            Customize your conference experience. Your dashboard will adapt based on the roles you select below.
        </p>
    </div>

    <form method="post" action="{{ route('profile.update-journey') }}" class="space-y-12">
        @csrf
        @method('patch')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Regular Attendee --}}
            <label class="group relative cursor-pointer md:col-span-2">
                <input type="checkbox" name="intent_attendee" value="1" class="peer hidden" {{ auth()->user()->intent_attendee ? 'checked' : '' }}>
                <div class="h-full p-6 rounded-[2rem] border-2 border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm transition-all duration-300 peer-checked:border-blue-600 peer-checked:bg-blue-50/10 dark:peer-checked:bg-blue-900/10 group-hover:border-slate-200 dark:group-hover:border-slate-700 flex items-center gap-6">
                    <div class="w-16 h-16 rounded-2xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-3xl group-hover:scale-110 transition-transform">🎓</div>
                    <div class="flex-1">
                        <h4 class="text-lg font-bold text-slate-900 dark:text-white mb-1 tracking-tight">Regular Participant</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed font-medium">Access scientific sessions, keynotes, and social events.</p>
                    </div>
                    <div class="text-blue-600 opacity-0 peer-checked:opacity-100 transition-opacity pr-4">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    </div>
                </div>
            </label>

            {{-- Presenter --}}
            <label class="group relative cursor-pointer">
                <input type="checkbox" name="intent_presenter" value="1" class="peer hidden" {{ auth()->user()->intent_presenter ? 'checked' : '' }}>
                <div class="h-full p-6 rounded-[2rem] border-2 border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm transition-all duration-300 peer-checked:border-indigo-600 peer-checked:bg-indigo-50/10 dark:peer-checked:bg-indigo-900/10 group-hover:border-slate-200 dark:group-hover:border-slate-700">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">🔬</div>
                        <div class="text-indigo-600 opacity-0 peer-checked:opacity-100 transition-opacity">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        </div>
                    </div>
                    <h4 class="text-md font-bold text-slate-900 dark:text-white mb-1 tracking-tight">Scientific Presenter</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed font-medium">Activate abstract submissions and presentation tools.</p>
                </div>
            </label>

            {{-- Group Leader --}}
            <label class="group relative cursor-pointer">
                <input type="checkbox" name="intent_group_leader" id="profile_intent_group_leader" value="1" class="peer hidden" {{ auth()->user()->intent_group_leader ? 'checked' : '' }}>
                <div class="h-full p-6 rounded-[2rem] border-2 border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm transition-all duration-300 peer-checked:border-emerald-600 peer-checked:bg-emerald-50/10 dark:peer-checked:bg-emerald-900/10 group-hover:border-slate-200 dark:group-hover:border-slate-700">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">👥</div>
                        <div class="text-emerald-600 opacity-0 peer-checked:opacity-100 transition-opacity">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        </div>
                    </div>
                    <h4 class="text-md font-bold text-slate-900 dark:text-white mb-1 tracking-tight">Group Coordinator</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed font-medium">Manage registrations for your research team.</p>
                </div>
            </label>

        </div>

        <!-- Group Registration Info (Conditional) -->
        <div id="profile-group-reg-info" class="{{ auth()->user()->intent_group_leader ? '' : 'hidden' }} mt-8 animate-in fade-in slide-in-from-top-4 duration-500">
            <div class="bg-indigo-900 rounded-[2rem] p-8 relative overflow-hidden group shadow-2xl">
                <div class="relative z-10">
                    <h3 class="text-lg font-bold text-white mb-2 flex items-center gap-3">
                        <span class="bg-indigo-600 p-2 rounded-xl"><svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span>
                        Group Coordination Active
                    </h3>
                    <p class="text-indigo-200 text-sm font-medium mb-6">
                        Specialized tools for team management have been enabled for your account.
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="p-4 bg-white/10 rounded-2xl">
                            <p class="text-xs font-bold text-white uppercase tracking-widest mb-1">Bulk Tools</p>
                            <p class="text-[10px] text-indigo-300 font-medium">Register teams of 3+</p>
                        </div>
                        <div class="p-4 bg-white/10 rounded-2xl">
                            <p class="text-xs font-bold text-white uppercase tracking-widest mb-1">Single Invoice</p>
                            <p class="text-[10px] text-indigo-300 font-medium">Centralized payments</p>
                        </div>
                        <div class="p-4 bg-white/10 rounded-2xl">
                            <p class="text-xs font-bold text-white uppercase tracking-widest mb-1">Badge Control</p>
                            <p class="text-[10px] text-indigo-300 font-medium">Manage team credentials</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between pt-10 border-t border-slate-100 dark:border-slate-800">
            <div>
                @if (session('status') === 'journey-updated')
                    <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest animate-fade-in flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        Preferences Updated
                    </p>
                @endif
            </div>

            <button type="submit" class="px-10 py-4 bg-indigo-600 text-white text-xs font-black uppercase tracking-[0.2em] rounded-2xl shadow-xl shadow-indigo-500/20 hover:bg-indigo-700 active:scale-95 transition-all">
                Update Preferences
            </button>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const groupLeaderCheckbox = document.getElementById('profile_intent_group_leader');
            const groupRegInfo = document.getElementById('profile-group-reg-info');

            if (groupLeaderCheckbox) {
                groupLeaderCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        groupRegInfo.classList.remove('hidden');
                        groupRegInfo.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    } else {
                        groupRegInfo.classList.add('hidden');
                    }
                });
            }
        });
    </script>
</section>
