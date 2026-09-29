@extends('layouts.app')

@section('title', 'Edit Broadcast')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 dark:from-gray-900 dark:via-slate-900 dark:to-gray-900">
    {{-- Hero Header System --}}
    <div class="relative overflow-hidden">
        {{-- Subtle Background Pattern --}}
        <div class="absolute inset-0 opacity-30 dark:opacity-20">
            <div class="absolute top-0 left-1/4 w-96 h-96 bg-blue-200 dark:bg-blue-500/20 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 right-1/4 w-[500px] h-[500px] bg-purple-200 dark:bg-purple-500/20 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 w-72 h-72 bg-emerald-100 dark:bg-emerald-500/10 rounded-full blur-3xl"></div>
        </div>

        <div class="relative max-w-4xl mx-auto px-6 py-12">
            <div class="flex flex-col items-center text-center space-y-4">
                <a href="{{ route('admin.announcements.index') }}" class="group flex items-center gap-2 text-sm font-bold text-slate-400 hover:text-blue-600 transition-colors">
                    <svg class="w-4 h-4 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7"/></svg>
                    BACK TO REGISTRY
                </a>

                <h1 class="text-4xl md:text-5xl font-black text-slate-900 dark:text-white tracking-tight" style="font-family: 'Outfit', sans-serif;">
                    Modify <span class="bg-gradient-to-r from-amber-500 to-orange-600 bg-clip-text text-transparent">Broadcast.</span>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 font-medium max-w-lg leading-relaxed">
                    Update the details of your message. Changes synchronize across all devices within seconds.
                </p>
            </div>
        </div>
    </div>

    {{-- Form Section --}}
    <div class="max-w-4xl mx-auto px-6 pb-20">
        <form action="{{ route('admin.announcements.update', $announcement->id) }}" method="POST" class="space-y-8">
            @csrf
            @method('PUT')

            {{-- Main Form Card --}}
            <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-[2.5rem] border border-white dark:border-slate-700 shadow-2xl shadow-slate-200/50 dark:shadow-none overflow-hidden hover:border-amber-500/20 transition-all duration-500">
                {{-- Header --}}
                <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 flex items-center gap-4">
                    <div class="p-3 bg-amber-100 dark:bg-amber-500/20 rounded-2xl text-amber-600 dark:text-amber-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white leading-none">Edit Content</h2>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mt-1">Broadcast ID: #{{ $announcement->id }} • Created {{ $announcement->created_at->diffForHumans() }}</p>
                    </div>
                </div>

                <div class="p-8 space-y-8">
                    {{-- Title --}}
                    <div class="space-y-2">
                        <label for="title" class="text-sm font-bold text-slate-700 dark:text-slate-300 ml-1">Headline</label>
                        <input type="text" id="title" name="title" value="{{ old('title', $announcement->title) }}" required
                               class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl text-slate-900 dark:text-white focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all @error('title') border-red-500 @enderror"
                               placeholder="Catchy and informative headline...">
                        @error('title') <p class="text-xs text-red-500 mt-1 ml-1 font-bold">{{ $message }}</p> @enderror
                    </div>

                    {{-- Category Grid --}}
                    <div class="space-y-4">
                        <label class="text-sm font-bold text-slate-700 dark:text-slate-300 ml-1 uppercase tracking-widest">Classification</label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            @foreach(['General', 'Social', 'Urgent', 'Info', 'Update', 'Schedule'] as $cat)
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="category" value="{{ $cat }}" class="peer sr-only" {{ old('category', $announcement->category) === $cat ? 'checked' : '' }}>
                                    <div class="px-4 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-800 text-center transition-all group-hover:bg-slate-50 dark:group-hover:bg-slate-700/50
                                        peer-checked:border-amber-500 peer-checked:bg-amber-50 dark:peer-checked:bg-amber-500/10 peer-checked:shadow-lg peer-checked:shadow-amber-500/10">
                                        <div class="flex flex-col items-center gap-1">
                                            @php
                                                $icons = [
                                                    'General' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10',
                                                    'Social' => 'M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                                                    'Urgent' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
                                                    'Info' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                                                    'Update' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
                                                    'Schedule' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'
                                                ];
                                            @endphp
                                            <svg class="w-5 h-5 mb-1 {{ old('category', $announcement->category) === $cat ? 'text-amber-600' : 'text-slate-400' }} peer-checked:text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icons[$cat] ?? 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' }}"></path>
                                            </svg>
                                            <span class="text-xs font-black uppercase tracking-tighter {{ old('category', $announcement->category) === $cat ? 'text-amber-700' : 'text-slate-600 dark:text-slate-400' }} peer-checked:text-amber-700">{{ $cat }}</span>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Content --}}
                    <div class="space-y-2">
                        <label for="content" class="text-sm font-bold text-slate-700 dark:text-slate-300 ml-1">Message Body</label>
                        <textarea id="content" name="content" rows="6" required
                                  class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-3xl text-slate-900 dark:text-white focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all resize-none @error('content') border-red-500 @enderror"
                                  placeholder="Detailed information for the attendees...">{{ old('content', $announcement->content) }}</textarea>
                    </div>

                    {{-- Urgent Alert Toggle --}}
                    <div class="p-6 bg-red-50 dark:bg-red-500/10 border border-red-100 dark:border-red-500/20 rounded-3xl flex items-center justify-between group/urgent">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-red-100 dark:bg-red-500/20 rounded-2xl text-red-600 dark:text-red-400 group-hover/urgent:scale-110 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-black text-red-900 dark:text-red-400 uppercase tracking-tight">Critical Bypass</h4>
                                <p class="text-xs text-red-700/60 dark:text-red-400/60 leading-tight">Activate priority queue and push notification.</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_urgent" value="1" class="sr-only peer" {{ old('is_urgent', $announcement->is_urgent) ? 'checked' : '' }}>
                            <div class="w-14 h-8 bg-slate-200 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:rotate-180 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-red-600"></div>
                        </label>
                    </div>
                </div>

                {{-- Action Bar --}}
                <div class="p-8 bg-slate-50/50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between">
                    {{-- Delete Option --}}
                    <form action="{{ route('admin.announcements.destroy', $announcement->id) }}" method="POST" class="inline" onsubmit="return confirm('Archive this message permanently?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="flex items-center gap-2 text-red-600 hover:text-red-700 font-bold text-xs uppercase tracking-widest transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Archive Post
                        </button>
                    </form>

                    <div class="flex items-center gap-4">
                        <a href="{{ route('admin.announcements.index') }}"
                           class="px-6 py-3 text-slate-500 font-bold hover:text-slate-700 transition-colors uppercase text-xs tracking-widest">
                            Cancel
                        </a>
                        <button type="submit"
                                class="px-8 py-4 bg-gradient-to-r from-amber-500 to-orange-600 text-white font-black rounded-2xl shadow-xl shadow-amber-500/20 hover:shadow-amber-500/40 hover:-translate-y-1 transition-all">
                            Commit Changes
                        </button>
                    </div>
                </div>
            </div>

            {{-- Real-time Preview --}}
            <div class="bg-slate-900 rounded-[2.5rem] p-10 border border-slate-800 shadow-2xl relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-br from-amber-500/10 to-transparent opacity-50"></div>

                <div class="relative z-10 flex flex-col md:flex-row items-center gap-8">
                    <div class="w-48 h-96 bg-black rounded-[2.5rem] border-[6px] border-slate-800 shadow-2xl relative overflow-hidden flex flex-col">
                        <div class="h-6 w-24 bg-slate-800 mx-auto rounded-b-2xl mb-4"></div>
                        <div class="px-3 space-y-4">
                            <div class="h-2 w-12 bg-slate-800 rounded-full"></div>
                            <div class="h-32 w-full bg-slate-900 rounded-2xl border border-slate-800 p-3 space-y-2">
                                <div class="h-1.5 w-8 bg-amber-500 rounded-full"></div>
                                <div id="preview-title" class="text-[10px] font-bold text-white transition-all">{{ $announcement->title }}</div>
                                <div id="preview-content" class="text-[8px] text-slate-400 leading-tight">{{ \Illuminate\Support\Str::limit($announcement->content, 50) }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 space-y-4 text-center md:text-left">
                        <h4 class="text-2xl font-black text-white tracking-tight">Transmission Preview</h4>
                        <p class="text-slate-400 font-medium leading-relaxed">
                            Preview the existing broadcast configuration. Updating will refresh the message for all delegates currently viewing their app.
                        </p>
                        <div class="inline-flex items-center gap-2 px-3 py-1 bg-amber-500/10 border border-amber-500/20 rounded-full">
                            <span class="w-2 h-2 bg-amber-500 rounded-full animate-pulse"></span>
                            <span class="text-[10px] font-black text-amber-400 uppercase tracking-widest">Modification Protocol</span>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    const titleInput = document.getElementById('title');
    const contentInput = document.getElementById('content');
    const previewTitle = document.getElementById('preview-title');
    const previewContent = document.getElementById('preview-content');

    const updatePreview = () => {
        previewTitle.textContent = titleInput.value || '';
        previewContent.textContent = contentInput.value.substring(0, 50) + (contentInput.value.length > 50 ? '...' : '');
    };

    titleInput.addEventListener('input', updatePreview);
    contentInput.addEventListener('input', updatePreview);
</script>
@endsection
