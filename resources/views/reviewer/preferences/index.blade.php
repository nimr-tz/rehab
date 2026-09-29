@extends('layouts.app')

@section('title', 'Reviewing Preferences')

@section('content')
<div class="max-w-4xl mx-auto py-12 px-8">
        
        <div class="mb-12">
            <div class="flex items-center gap-3 mb-4">
                <span class="w-12 h-1 bg-emerald-600 rounded-full"></span>
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.4em]">Expert Onboarding</span>
            </div>
            <h1 class="text-5xl font-black text-slate-900 dark:text-white tracking-tighter mb-4">
                Scientific Expertise<span class="text-emerald-500">.</span>
            </h1>
            <p class="text-slate-600 dark:text-slate-400 max-w-2xl leading-relaxed">
                To enable automated and efficient peer review, please select your areas of expertise and your desired workload. This ensures you only receive abstracts that match your scientific background.
            </p>
        </div>

        <form action="{{ route('reviewer.preferences.store') }}" method="POST">
            @csrf
            
            <div class="space-y-8">
                <!-- 1. SUBTHEME SELECTION -->
                <div class="bg-white dark:bg-slate-900/50 p-10 rounded-[2.5rem] border border-slate-200 dark:border-white/5 shadow-sm">
                    <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight mb-8">Research Subthemes</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($subthemes as $name => $prefix)
                        <label class="group relative cursor-pointer">
                            <input type="checkbox" name="subthemes[]" value="{{ $name }}" class="peer sr-only" {{ in_array($name, $selectedSubthemes) ? 'checked' : '' }}>
                            <div class="h-full p-6 rounded-2xl border-2 border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 transition-all peer-checked:border-emerald-500 peer-checked:bg-emerald-50/50 dark:peer-checked:bg-emerald-500/10 group-hover:border-slate-300 dark:group-hover:border-slate-700">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-700 shadow-sm flex items-center justify-center font-black text-xs text-emerald-600 dark:text-emerald-400 border border-slate-100 dark:border-slate-600 peer-checked:bg-emerald-600 peer-checked:text-white transition-colors">
                                        {{ $prefix }}
                                    </div>
                                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300 leading-tight">{{ $name }}</span>
                                </div>
                            </div>
                        </label>
                        @endforeach
                    </div>
                    @error('subthemes')
                        <p class="mt-4 text-xs font-bold text-rose-500 uppercase tracking-widest">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 2. CAPACITY MANAGEMENT -->
                <div class="bg-white dark:bg-slate-900/50 p-10 rounded-[2.5rem] border border-slate-200 dark:border-white/5 shadow-sm">
                    <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight mb-8">Workload Capacity</h2>
                    
                    <div class="flex flex-col md:flex-row md:items-center gap-10">
                        <div class="flex-1">
                            <p class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Maximum Concurrent Abstracts</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-6">
                                The maximum number of abstracts you are willing to have assigned to you at any given time.
                            </p>
                            
                            <div class="flex items-center gap-6">
                                <input type="range" name="reviewer_max_load" min="1" max="20" value="{{ $user->reviewer_max_load ?? 5 }}" 
                                       class="flex-1 h-2 bg-slate-200 dark:bg-slate-800 rounded-lg appearance-none cursor-pointer accent-emerald-600"
                                       oninput="this.nextElementSibling.innerText = this.value">
                                <span class="w-12 h-12 flex items-center justify-center bg-emerald-600 text-white font-black rounded-2xl text-lg shadow-lg shadow-emerald-500/20">
                                    {{ $user->reviewer_max_load ?? 5 }}
                                </span>
                            </div>
                        </div>

                        <div class="w-px h-24 bg-slate-200 dark:bg-slate-800 hidden md:block"></div>

                        <div class="bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-900/30 p-6 rounded-3xl md:w-64">
                            <div class="flex items-center gap-3 mb-2 text-amber-700 dark:text-amber-400">
                                <svg class="w-5 h-5 font-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="text-xs font-black uppercase tracking-widest">Efficiency Rule</span>
                            </div>
                            <p class="text-xs font-bold text-amber-800 dark:text-amber-500 leading-snug">
                                Assigned abstracts must be reviewed within 72 hours to maintain high system throughput.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-6">
                    <button type="submit" class="group relative px-12 py-5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-[2rem] shadow-xl shadow-emerald-600/20 transition-all hover:scale-[1.03] active:scale-95 overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                        <span class="relative z-10 text-sm font-black uppercase tracking-[0.2em]">Activate Expertise Profile</span>
                    </button>
                </div>
            </div>
        </form>

    </div>
@endsection
