@extends('layouts.app')

@section('title', isset($speaker) ? 'Reconfigure Speaker' : 'Enlist Speaker')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 dark:from-gray-900 dark:via-slate-900 dark:to-gray-900">
    <div class="relative max-w-[1400px] mx-auto px-6 py-12 text-slate-900 dark:text-white">

        {{-- Executive Back Navigation --}}
        <div class="mb-12">
            <a href="{{ route('admin.speakers.index') }}" class="group inline-flex items-center gap-4 px-6 py-3 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 hover:text-purple-600 dark:hover:text-purple-400 transition-all font-black text-xs uppercase tracking-widest">
                <svg class="w-5 h-5 group-hover:-translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Intelligence Hub
            </a>
        </div>

        <div class="grid lg:grid-cols-12 gap-12 items-start">

            {{-- Left: Identity Visualization --}}
            <div class="lg:col-span-4 space-y-8">
                <div class="p-10 bg-white/80 dark:bg-slate-800/80 backdrop-blur-2xl rounded-[3rem] border border-white dark:border-slate-700 shadow-2xl">
                    <h3 class="text-xl font-black text-slate-900 dark:text-white mb-8">Visual Identity</h3>

                    <div class="relative group">
                        <div id="photo-preview" class="relative w-full aspect-square rounded-[2.5rem] overflow-hidden bg-slate-100 dark:bg-slate-900 border-4 border-slate-50 dark:border-slate-800 shadow-inner group">
                            @if(isset($speaker) && $speaker->photo_path)
                                <img src="{{ $speaker->photo_url }}" alt="Speaker" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex flex-col items-center justify-center text-slate-300 dark:text-slate-600 p-10 text-center">
                                    <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <p class="text-xs font-black uppercase tracking-widest">Awaiting Visual Uplink</p>
                                </div>
                            @endif
                            <div class="absolute inset-0 bg-purple-600/20 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
                        </div>

                        {{-- Photo Control Overlay --}}
                        <div class="mt-8">
                            <label class="block text-[10px] font-black uppercase text-slate-400 tracking-widest mb-4">Uplink Profile Image</label>
                            <input type="file" name="photo" id="photo" accept="image/*" form="speaker-form"
                                class="block w-full text-xs text-slate-500 dark:text-slate-400
                                file:mr-6 file:py-3 file:px-6
                                file:rounded-xl file:border-0
                                file:text-[10px] file:font-black file:uppercase file:tracking-widest
                                file:bg-purple-600 file:text-white
                                hover:file:bg-purple-700
                                dark:file:bg-white dark:file:text-slate-900
                                cursor-pointer transition-all">
                            <p class="mt-3 text-[10px] font-bold text-slate-400 uppercase italic px-2">Optimal Resolution: 800x800px • Max 2MB</p>
                        </div>

                        @if(isset($speaker) && $speaker->photo_path)
                            <div class="mt-6 flex items-center gap-3 p-4 bg-rose-50 dark:bg-rose-900/10 rounded-2xl border border-rose-100 dark:border-rose-900/20">
                                <input type="checkbox" name="remove_photo" id="remove_photo" value="1" form="speaker-form" class="w-5 h-5 rounded border-rose-300 text-rose-600 focus:ring-rose-500">
                                <label for="remove_photo" class="text-xs font-black text-rose-600 uppercase tracking-widest cursor-pointer">Purge Current Proxy</label>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Status Hub --}}
                <div class="p-10 bg-slate-900 rounded-[3rem] border border-slate-800 shadow-2xl text-white">
                    <h3 class="text-xl font-black mb-8 uppercase tracking-tighter">Terminal Logic</h3>
                    <div class="space-y-8">
                        <div>
                            <label class="block text-[10px] font-black uppercase text-slate-500 tracking-widest mb-4">Operational Status</label>
                            <div class="flex items-center justify-between p-4 bg-slate-800/50 rounded-2xl border border-slate-700">
                                <span class="text-sm font-black uppercase">Visible in Nexus</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="is_active" value="0" form="speaker-form">
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $speaker->is_active ?? true) ? 'checked' : '' }} form="speaker-form" class="sr-only peer">
                                    <div class="w-14 h-7 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-1 after:left-1 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600 shadow-inner"></div>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black uppercase text-slate-500 tracking-widest mb-4">Strategic Order</label>
                            <input type="number" name="display_order" value="{{ old('display_order', $speaker->display_order ?? 0) }}" min="0" form="speaker-form"
                                class="w-full px-6 py-4 bg-slate-800/50 border border-slate-700 rounded-2xl text-white font-black text-lg focus:ring-2 focus:ring-purple-500 transition-all outline-none">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: Cognitive Profile Form --}}
            <div class="lg:col-span-8 space-y-12">
                <form id="speaker-form" action="{{ isset($speaker) ? route('admin.speakers.update', $speaker) : route('admin.speakers.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @if(isset($speaker)) @method('PUT') @endif

                    <div class="p-10 bg-white/80 dark:bg-slate-800/80 backdrop-blur-2xl rounded-[3rem] border border-white dark:border-slate-700 shadow-2xl space-y-10">
                        <div>
                            <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight mb-2">Cognitive Identity</h3>
                            <p class="text-sm font-medium text-slate-400">Specify the structural metadata for the speaker entity.</p>
                        </div>

                        <div class="grid md:grid-cols-2 gap-8">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-4">Entity Title</label>
                                <select name="title" class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-purple-500 transition-all">
                                    <option value="" {{ old('title', $speaker->title ?? '') == '' ? 'selected' : '' }}>No Title</option>
                                    @foreach(['Prof.', 'Dr.', 'Hon.', 'Mr.', 'Mrs.', 'Ms.', 'Sir', 'Dame'] as $t)
                                        <option value="{{ $t }}" {{ old('title', $speaker->title ?? '') == $t ? 'selected' : '' }}>{{ $t }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-4">Full Legal Identifier</label>
                                <input type="text" name="name" value="{{ old('name', $speaker->name ?? '') }}" required
                                    class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-purple-500 transition-all">
                            </div>

                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-4">Communication Uplink (Email)</label>
                                <input type="email" name="email" value="{{ old('email', $speaker->email ?? '') }}"
                                    class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-purple-500 transition-all">
                            </div>

                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-4">Strategic Echelon (Type)</label>
                                <select name="type" required class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-purple-500 transition-all">
                                    @foreach(['keynote' => 'Keynote Alpha', 'plenary' => 'Plenary Strategist', 'vip' => 'VIP Hub', 'guest_of_honor' => 'Guest of Honor', 'distinguished' => 'Distinguished Guest', 'chief_guest' => 'Chief Guest', 'special' => 'Special Guest', 'invited' => 'Invited Expert', 'panelist' => 'Panelist'] as $val => $label)
                                        <option value="{{ $val }}" {{ old('type', $speaker->type ?? '') == $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-4">Operational Designation (Position)</label>
                                <input type="text" name="position" value="{{ old('position', $speaker->position ?? '') }}" placeholder="e.g., Lead Strategist"
                                    class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-purple-500 transition-all">
                            </div>

                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-4">Primary Affiliation</label>
                                <input type="text" name="affiliation" value="{{ old('affiliation', $speaker->affiliation ?? '') }}" placeholder="e.g., Global Intelligence Corp"
                                    class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-purple-500 transition-all">
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-4">Biographical Intelligence</label>
                            <textarea name="bio" rows="6" class="w-full px-6 py-6 bg-slate-50 dark:bg-slate-900 border-none rounded-[2rem] text-slate-900 dark:text-white font-medium focus:ring-2 focus:ring-purple-500 transition-all outline-none resize-none leading-relaxed">{{ old('bio', $speaker->bio ?? '') }}</textarea>
                        </div>
                    </div>

                    <div class="mt-12 p-10 bg-white/80 dark:bg-slate-800/80 backdrop-blur-2xl rounded-[3rem] border border-white dark:border-slate-700 shadow-2xl space-y-10 text-white">
                         <div class="flex items-center gap-6 mb-2">
                            <div class="p-3 bg-purple-600 rounded-2xl">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.826a4 4 0 015.656 0l4 4a4 4 0 01-5.656 5.656l-1.103-1.103"/></svg>
                            </div>
                            <div>
                                <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Social Nexus</h3>
                                <p class="text-sm font-medium text-slate-400">Map the speaker's digital coordinates.</p>
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-8">
                            <div class="group">
                                <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-4 mb-2 block">Twitter Handle (X)</label>
                                <div class="flex items-center bg-slate-50 dark:bg-slate-900 rounded-2xl overflow-hidden focus-within:ring-2 focus-within:ring-purple-500 transition-all">
                                    <span class="px-6 py-4 bg-slate-100 dark:bg-slate-800 text-slate-400 font-black">@</span>
                                    <input type="text" name="twitter" value="{{ old('twitter', $speaker->social_links['twitter'] ?? '') }}" placeholder="username" class="w-full px-6 py-4 bg-transparent border-none text-slate-900 dark:text-white font-bold outline-none">
                                </div>
                            </div>
                            <div class="group">
                                <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-4 mb-2 block">LinkedIn Coordinates</label>
                                <input type="url" name="linkedin" value="{{ old('linkedin', $speaker->social_links['linkedin'] ?? '') }}" placeholder="https://linkedin.com/in/username" class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-purple-500 transition-all">
                            </div>
                            <div class="md:col-span-2 group">
                                <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-4 mb-2 block">Global Terminal (Website)</label>
                                <input type="url" name="website" value="{{ old('website', $speaker->social_links['website'] ?? '') }}" placeholder="https://example.com" class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-purple-500 transition-all">
                            </div>
                        </div>
                    </div>

                    <div class="mt-12 flex justify-end gap-6">
                        <a href="{{ route('admin.speakers.index') }}" class="px-10 py-5 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-2xl shadow-xl hover:-translate-y-1 transition-all font-black text-sm tracking-widest uppercase border border-slate-100 dark:border-slate-700">
                            Abort Enlistment
                        </a>
                        <button type="submit" class="px-10 py-5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl shadow-2xl hover:-translate-y-1 transition-all font-black text-sm tracking-widest uppercase flex items-center gap-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            Synchronize Entity
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // High-performance photo preview script
    document.getElementById('photo').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('photo-preview');
                preview.innerHTML = `<img src="${e.target.result}" alt="Preview" class="w-full h-full object-cover animate-fade-in">`;
                preview.classList.remove('p-10', 'text-center');
            }
            reader.readAsDataURL(file);
        }
    });
</script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;900&display=swap');
    :root { font-family: 'Outfit', sans-serif; }
    @keyframes fade-in { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
    .animate-fade-in { animation: fade-in 0.5s cubic-bezier(0.16, 1, 0.3, 1); }
</style>
@endsection
