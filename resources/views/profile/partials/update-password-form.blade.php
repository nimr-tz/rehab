<section class="max-w-4xl">
    <form method="post" action="{{ route('password.update') }}" class="space-y-10">
        @csrf
        @method('put')

        {{-- Security Tip --}}
        <div class="p-6 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-3xl flex items-center gap-5">
            <div class="w-12 h-12 bg-white dark:bg-slate-900 rounded-2xl flex items-center justify-center text-purple-600 shadow-sm border border-slate-100 dark:border-slate-800 flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
            <div>
                <p class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-widest mb-1">Security Best Practice</p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium leading-relaxed">Ensure your password is at least 8 characters long and includes a mix of letters, numbers, and symbols.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            {{-- Current Password --}}
            <div class="space-y-2">
                <label for="update_password_current_password" class="text-xs font-bold text-slate-500 dark:text-slate-400 ml-1 uppercase tracking-widest">Current Password</label>
                <div class="relative">
                    <input id="update_password_current_password" name="current_password" type="password" class="w-full h-14 px-5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl text-slate-900 dark:text-white font-bold text-sm focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all outline-none" placeholder="••••••••">
                    <button type="button" onclick="togglePass('update_password_current_password')" class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-300 hover:text-slate-500 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
                @error('current_password', 'updatePassword') <p class="text-[10px] text-rose-500 font-bold mt-1 ml-1">{{ $message }}</p> @enderror
            </div>

            <div class="hidden md:block"></div>

            {{-- New Password --}}
            <div class="space-y-2">
                <label for="update_password_password" class="text-xs font-bold text-slate-500 dark:text-slate-400 ml-1 uppercase tracking-widest">New Password</label>
                <div class="relative">
                    <input id="update_password_password" name="password" type="password" class="w-full h-14 px-5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl text-slate-900 dark:text-white font-bold text-sm focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all outline-none" placeholder="••••••••">
                    <button type="button" onclick="togglePass('update_password_password')" class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-300 hover:text-slate-500 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
                @error('password', 'updatePassword') <p class="text-[10px] text-rose-500 font-bold mt-1 ml-1">{{ $message }}</p> @enderror
            </div>

            {{-- Confirm Password --}}
            <div class="space-y-2">
                <label for="update_password_password_confirmation" class="text-xs font-bold text-slate-500 dark:text-slate-400 ml-1 uppercase tracking-widest">Confirm Password</label>
                <div class="relative">
                    <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="w-full h-14 px-5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl text-slate-900 dark:text-white font-bold text-sm focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all outline-none" placeholder="••••••••">
                    <button type="button" onclick="togglePass('update_password_password_confirmation')" class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-300 hover:text-slate-500 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between pt-10 border-t border-slate-100 dark:border-slate-800">
            <div>
                @if (session('status') === 'password-updated')
                    <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest animate-fade-in flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        Password Updated
                    </p>
                @endif
            </div>

            <button type="submit" class="px-10 py-4 bg-purple-600 text-white text-xs font-black uppercase tracking-[0.2em] rounded-2xl shadow-xl shadow-purple-500/20 hover:bg-purple-700 active:scale-95 transition-all">
                Update Password
            </button>
        </div>
    </form>

    <script>
        function togglePass(id) {
            const input = document.getElementById(id);
            input.type = input.type === 'password' ? 'text' : 'password';
        }
    </script>
</section>
