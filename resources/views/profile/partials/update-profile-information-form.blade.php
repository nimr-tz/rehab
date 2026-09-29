<section class="max-w-4xl">
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-12" data-autosave="profile-info">
        @csrf
        @method('patch')

        {{-- Basic Information --}}
        <div class="grid grid-cols-1 md:grid-cols-12 gap-10">
            {{-- Avatar Upload --}}
            <div class="md:col-span-4 flex flex-col items-center">
                <div class="relative group cursor-pointer">
                    <div class="absolute -inset-1 bg-gradient-to-tr from-indigo-600 to-blue-500 rounded-[2.5rem] blur opacity-25 group-hover:opacity-50 transition duration-500"></div>
                    <div id="image-preview-container" class="relative">
                        @if(Auth::user()->profile_image)
                            <img id="current-image"
                                 src="{{ asset('storage/' . Auth::user()->profile_image) }}"
                                 class="h-40 w-40 rounded-[2.2rem] object-cover border-4 border-white dark:border-slate-800 shadow-xl"
                                 alt="Profile">
                        @else
                            <div id="current-image-placeholder"
                                 class="h-40 w-40 rounded-[2.2rem] bg-slate-50 dark:bg-slate-800 flex items-center justify-center border-4 border-white dark:border-slate-800 shadow-xl">
                                <svg class="w-16 h-16 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                        @endif
                        
                        <label for="profile_image" class="absolute -bottom-2 -right-2 p-3 bg-indigo-600 text-white rounded-2xl shadow-lg cursor-pointer hover:scale-110 active:scale-95 transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </label>
                    </div>
                </div>
                <div class="mt-4 text-center">
                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Profile Photo</p>
                    @if(Auth::user()->profile_image)
                        <button type="button" onclick="removeImage()" class="mt-2 text-[10px] font-black text-rose-500 hover:text-rose-600 uppercase tracking-widest transition-colors">Remove Photo</button>
                    @endif
                    <input id="profile_image" name="profile_image" type="file" class="hidden" onchange="previewImage(this)">
                    <input type="hidden" name="remove_profile_image" id="remove_profile_image" value="0">
                </div>
            </div>

            {{-- Basic Info Fields --}}
            <div class="md:col-span-8 space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 ml-1 uppercase tracking-widest">Title</label>
                        <div class="w-full h-14 px-5 bg-slate-100 dark:bg-slate-800 border border-transparent rounded-2xl text-slate-400 dark:text-slate-500 font-bold text-sm flex items-center">
                            {{ Auth::user()->title ?: '—' }}
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 ml-1 uppercase tracking-widest">Email Address</label>
                        <div class="w-full h-14 px-5 bg-slate-100 dark:bg-slate-800 border border-transparent rounded-2xl text-slate-400 dark:text-slate-500 font-bold text-sm flex items-center">
                            {{ Auth::user()->email }}
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 ml-1 uppercase tracking-widest">First Name</label>
                        <div class="w-full h-14 px-5 bg-slate-100 dark:bg-slate-800 border border-transparent rounded-2xl text-slate-400 dark:text-slate-500 font-bold text-sm flex items-center">
                            {{ Auth::user()->first_name }}
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 ml-1 uppercase tracking-widest">Last Name</label>
                        <div class="w-full h-14 px-5 bg-slate-100 dark:bg-slate-800 border border-transparent rounded-2xl text-slate-400 dark:text-slate-500 font-bold text-sm flex items-center">
                            {{ Auth::user()->last_name }}
                        </div>
                    </div>
                </div>
                <p class="text-[10px] text-slate-400 font-medium ml-1">
                    Your name and title are locked because they appear on your conference certificates. Contact the organizers if a correction is needed.
                </p>
            </div>
        </div>

        {{-- Professional Details --}}
        <div class="pt-10 border-t border-slate-100 dark:border-slate-800">
            <h3 class="text-xs font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-[0.2em] mb-8">Professional Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-2">
                    <label for="affiliation" class="text-xs font-bold text-slate-500 dark:text-slate-400 ml-1 uppercase tracking-widest">Institution / Affiliation</label>
                    <input id="affiliation" name="affiliation" type="text" class="w-full h-14 px-5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl text-slate-900 dark:text-white font-bold text-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none" value="{{ old('affiliation', Auth::user()->affiliation) }}" placeholder="e.g. {{ config('conference.host_short') }}">
                </div>
                <div class="space-y-2">
                    <label for="country" class="text-xs font-bold text-slate-500 dark:text-slate-400 ml-1 uppercase tracking-widest">Country</label>
                    <input id="country" name="country" type="text" class="w-full h-14 px-5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl text-slate-900 dark:text-white font-bold text-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none" value="{{ old('country', Auth::user()->country) }}" placeholder="e.g. Tanzania">
                </div>
                <div class="space-y-2">
                    <label for="institute" class="text-xs font-bold text-slate-500 dark:text-slate-400 ml-1 uppercase tracking-widest">Department / Division</label>
                    <input id="institute" name="institute" type="text" class="w-full h-14 px-5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl text-slate-900 dark:text-white font-bold text-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none" value="{{ old('institute', Auth::user()->institute) }}" placeholder="e.g. Public Health">
                </div>
                <div class="space-y-2">
                    <label for="phone" class="text-xs font-bold text-slate-500 dark:text-slate-400 ml-1 uppercase tracking-widest">Phone Number</label>
                    <input id="phone" name="phone" type="tel" class="w-full h-14 px-5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl text-slate-900 dark:text-white font-bold text-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none" value="{{ old('phone', Auth::user()->phone) }}" placeholder="+255 ...">
                </div>
            </div>
        </div>

        {{-- CPD Information --}}
        <div class="pt-10 border-t border-slate-100 dark:border-slate-800">
            <h3 class="text-xs font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-[0.2em] mb-2">CPD Points</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-8">Required to issue your Continuing Professional Development (CPD) certificate after the conference.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-2">
                    <label for="professional_board" class="text-xs font-bold text-slate-500 dark:text-slate-400 ml-1 uppercase tracking-widest">Professional Board / Council</label>
                    <input id="professional_board" name="professional_board" type="text" class="w-full h-14 px-5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl text-slate-900 dark:text-white font-bold text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all outline-none" value="{{ old('professional_board', Auth::user()->professional_board) }}" placeholder="e.g. Medical Council of Tanganyika">
                </div>
                <div class="space-y-2">
                    <label for="registration_number" class="text-xs font-bold text-slate-500 dark:text-slate-400 ml-1 uppercase tracking-widest">Registration Number</label>
                    <input id="registration_number" name="registration_number" type="text" class="w-full h-14 px-5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl text-slate-900 dark:text-white font-bold text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all outline-none" value="{{ old('registration_number', Auth::user()->registration_number) }}" placeholder="e.g. MCT-12345">
                </div>
            </div>
        </div>

        {{-- Networking & Social Links --}}
        <div class="pt-10 border-t border-slate-100 dark:border-slate-800">
            <h3 class="text-xs font-black text-blue-600 dark:text-blue-400 uppercase tracking-[0.2em] mb-8">Digital Business Card & Networking</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-2">
                    <label for="linkedin_url" class="text-xs font-bold text-slate-500 dark:text-slate-400 ml-1 uppercase tracking-widest">LinkedIn Profile URL</label>
                    <input id="linkedin_url" name="linkedin_url" type="url" class="w-full h-14 px-5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl text-slate-900 dark:text-white font-bold text-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none" value="{{ old('linkedin_url', Auth::user()->linkedin_url) }}" placeholder="https://linkedin.com/in/...">
                </div>
                <div class="space-y-2">
                    <label for="twitter_url" class="text-xs font-bold text-slate-500 dark:text-slate-400 ml-1 uppercase tracking-widest">Twitter / X Profile URL</label>
                    <input id="twitter_url" name="twitter_url" type="url" class="w-full h-14 px-5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl text-slate-900 dark:text-white font-bold text-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none" value="{{ old('twitter_url', Auth::user()->twitter_url) }}" placeholder="https://twitter.com/...">
                </div>
            </div>
            <p class="mt-4 text-[10px] text-slate-400 font-medium">
                Note: These links may be shown alongside your speaker profile.
            </p>
        </div>

        {{-- Academic Status (Conditional) --}}
        @if(Auth::user()->student_status === 'yes')
        <div class="pt-10 border-t border-slate-100 dark:border-slate-800">
            <h3 class="text-xs font-black text-amber-600 uppercase tracking-[0.2em] mb-8">Academic Verification</h3>
            <div class="p-6 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-[2rem]">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-600 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-widest">Upload Student ID</p>
                            @if(Auth::user()->student_verification_status === 'verified')
                                <span class="text-[10px] text-emerald-600 font-bold uppercase tracking-widest">Status: Verified</span>
                            @elseif(Auth::user()->student_verification_status === 'rejected')
                                <span class="text-[10px] text-rose-600 font-bold uppercase tracking-widest">Status: Re-upload Required</span>
                            @else
                                <span class="text-[10px] text-amber-600 font-bold uppercase tracking-widest">Status: Pending Verification</span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <label for="student_document" class="flex items-center gap-3 px-6 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl cursor-pointer hover:border-indigo-500 transition-all shadow-sm">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0l-4-4m4 4V4"/></svg>
                            <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Choose File</span>
                            <input type="file" id="student_document" name="student_document" class="hidden" accept=".pdf,.jpg,.jpeg,.png">
                        </label>
                    </div>
                </div>
                @if(Auth::user()->student_verification_status === 'rejected')
                    <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-xs font-bold text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/20 dark:text-rose-300">
                        Your previous student ID was not approved. Please upload a clearer or updated student ID to restart verification.
                    </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Biography --}}
        <div class="pt-10 border-t border-slate-100 dark:border-slate-800">
            <label for="bio" class="text-xs font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-[0.2em] mb-4 block">Short Bio</label>
            <textarea id="bio" name="bio" rows="4" class="w-full px-5 py-5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-[2rem] text-slate-900 dark:text-white font-medium text-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none resize-none leading-relaxed" placeholder="Tell us about your research interests...">{{ old('bio', Auth::user()->bio) }}</textarea>
        </div>

        {{-- Final Save --}}
        <div class="flex items-center justify-between pt-10 mt-10 border-t border-slate-100 dark:border-slate-800">
            <div>
                @if (session('status') === 'profile-updated')
                    <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest animate-fade-in flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        Profile Updated Successfully
                    </p>
                @endif
            </div>

            <button type="submit" class="px-10 py-4 bg-indigo-600 text-white text-xs font-black uppercase tracking-[0.2em] rounded-2xl shadow-xl shadow-indigo-500/20 hover:bg-indigo-700 active:scale-95 transition-all">
                Save Changes
            </button>
        </div>
    </form>

    <script>
        function removeImage() {
            const container = document.getElementById('image-preview-container');
            container.innerHTML = `
                <div class="h-40 w-40 mx-auto rounded-[2.2rem] bg-slate-50 dark:bg-slate-800 flex items-center justify-center border-4 border-white dark:border-slate-800 shadow-xl">
                    <svg class="w-16 h-16 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
            `;
            document.getElementById('profile_image').value = '';
            document.getElementById('remove_profile_image').value = '1';
        }

        function previewImage(input) {
            if (input.files && input.files[0]) {
                document.getElementById('remove_profile_image').value = '0';
                const reader = new FileReader();
                reader.onload = (e) => {
                    const container = document.getElementById('image-preview-container');
                    container.innerHTML = `<img src="${e.target.result}" class="h-40 w-40 mx-auto rounded-[2.2rem] object-cover border-4 border-white dark:border-slate-800 shadow-xl transition-transform duration-500">`;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</section>
