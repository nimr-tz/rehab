@extends('layouts.app')

@section('title', 'Conference Feedback Analytics')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-indigo-50/30 dark:from-gray-900 dark:via-slate-900 dark:to-indigo-900/10 py-12">
    <div class="max-w-[1400px] mx-auto px-6">

        <!-- Premium Executive Header -->
        <div class="relative overflow-hidden mb-12 p-10 rounded-[2.5rem] bg-slate-900 dark:bg-white shadow-2xl group">
            {{-- Abstract Orbs --}}
            <div class="absolute -top-24 -right-24 w-80 h-80 bg-indigo-500/20 dark:bg-indigo-200 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-cyan-500/10 dark:bg-cyan-100 rounded-full blur-3xl"></div>

            <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-8">
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <span class="px-3 py-1 bg-indigo-500/20 dark:bg-indigo-100 text-indigo-400 dark:text-indigo-600 text-[10px] font-black uppercase tracking-[0.2em] rounded-lg border border-indigo-500/30">Administrative Feedback Dashboard</span>
                    </div>
                    <h1 class="text-5xl font-black text-white dark:text-slate-900 tracking-tight mb-3">
                        Conference <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-cyan-400">Feedback Analysis</span>
                    </h1>
                    <p class="text-slate-400 dark:text-slate-500 text-lg font-medium max-w-2xl leading-relaxed">
                        Reviewing participant feedback to improve the {{ config('conference.edition') }} {{ config('conference.name') }} experience.
                    </p>
                </div>

                <div class="flex gap-4">
                    <a href="{{ route('admin.feedback.report') }}" class="group flex items-center gap-3 px-8 py-4 bg-indigo-600 hover:bg-indigo-700 border border-indigo-500 rounded-2xl text-white font-bold transition-all transform hover:-translate-y-1 shadow-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Committee Report (PDF)
                    </a>
                    <a href="{{ route('admin.feedback.export') }}" class="group flex items-center gap-3 px-8 py-4 bg-white/10 dark:bg-slate-100 hover:bg-white/20 dark:hover:bg-slate-200 border border-white/20 dark:border-slate-300 rounded-2xl text-white dark:text-slate-900 font-bold transition-all transform hover:-translate-y-1 shadow-xl">
                        <svg class="w-5 h-5 group-hover:bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Export Master Data
                    </a>
                </div>
            </div>
        </div>

        {{-- Sentiment Overview Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <!-- Overall Satisfaction -->
            <div class="p-8 bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-xl shadow-indigo-100/50 dark:shadow-none border border-slate-100 dark:border-slate-700 transition-all hover:scale-[1.02]">
                <div class="flex justify-between items-start mb-6">
                    <div class="p-4 bg-indigo-50 dark:bg-indigo-900/40 rounded-2xl text-indigo-600 dark:text-indigo-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Global Satisfaction</span>
                </div>
                <div class="flex items-baseline gap-2">
                    <h2 class="text-4xl font-black text-slate-900 dark:text-white">{{ number_format($averageSatisfaction, 1) }}</h2>
                    <span class="text-slate-400 font-bold">/ 5.0</span>
                </div>
                <div class="mt-4 h-2 w-full bg-slate-100 dark:bg-slate-900 rounded-full overflow-hidden">
                    <div class="h-full bg-indigo-500 rounded-full" style="width: {{ ($averageSatisfaction/5)*100 }}%"></div>
                </div>
            </div>

            <!-- Scientific Quality -->
            <div class="p-8 bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-xl shadow-indigo-100/50 dark:shadow-none border border-slate-100 dark:border-slate-700 transition-all hover:scale-[1.02]">
                <div class="flex justify-between items-start mb-6">
                    <div class="p-4 bg-emerald-50 dark:bg-emerald-900/40 rounded-2xl text-emerald-600 dark:text-emerald-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    </div>
                    <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Scientific Content</span>
                </div>
                <div class="flex items-baseline gap-2">
                    <h2 class="text-4xl font-black text-slate-900 dark:text-white">{{ number_format($averageContent, 1) }}</h2>
                    <span class="text-slate-400 font-bold">/ 5.0</span>
                </div>
                <p class="text-[10px] font-black text-slate-500 uppercase mt-4 tracking-widest">Academic Rigor Rating</p>
            </div>

            <!-- Net Promoter Score -->
            <div class="p-8 bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-xl shadow-indigo-100/50 dark:shadow-none border border-slate-100 dark:border-slate-700 transition-all hover:scale-[1.02]">
                <div class="flex justify-between items-start mb-6">
                    <div class="p-4 bg-purple-50 dark:bg-purple-900/40 rounded-2xl text-purple-600 dark:text-purple-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 01-12 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Recommendation Likelihood</span>
                </div>
                <div class="flex items-baseline gap-2">
                    <h2 class="text-4xl font-black text-slate-900 dark:text-white">{{ number_format(($recommendationStats['yes'] ?? 0) / (max(1, $totalFeedback)) * 100) }}%</h2>
                </div>
                <div class="mt-4 flex gap-1">
                    @for($i=0; $i<10; $i++)
                        <div class="h-1.5 flex-1 rounded-full bg-{{ $i < 9 ? 'emerald' : 'slate' }}-500"></div>
                    @endfor
                </div>
            </div>

            <!-- Loyalty Metric -->
            <div class="p-8 bg-slate-900 rounded-[2.5rem] shadow-2xl transition-all hover:scale-[1.02] text-white">
                <div class="flex justify-between items-start mb-6">
                    <div class="p-4 bg-slate-800 rounded-2xl">
                        <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <span class="text-[10px] font-black uppercase text-slate-500 tracking-widest">Future Intent</span>
                </div>
                <h2 class="text-4xl font-black">{{ number_format(($futureParticipationStats['yes'] ?? 0) / (max(1, $totalFeedback)) * 100) }}%</h2>
                <p class="text-xs font-bold text-slate-400 uppercase mt-4 tracking-widest">Plan to attend in 2027</p>
            </div>
        </div>

        <div class="grid lg:grid-cols-12 gap-10">
            <!-- Sentiment Feed (Left Column) -->
            <div class="lg:col-span-8 space-y-8">
                <div class="bg-white dark:bg-slate-800 rounded-[3rem] p-10 shadow-2xl shadow-indigo-100/50 dark:shadow-none border border-slate-100 dark:border-slate-700">
                    <div class="flex items-center justify-between mb-10">
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Qualitative Records</h3>
                        <div class="flex items-center gap-4">
                            <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Showing {{ $feedbacks->count() }} of {{ $totalFeedback }} entries</span>
                        </div>
                    </div>

                    <div class="space-y-6">
                        @foreach($feedbacks as $feedback)
                            <div class="p-8 bg-slate-50 dark:bg-slate-900/50 rounded-[2rem] border border-slate-100 dark:border-slate-700 transition-all hover:shadow-lg">
                                <div class="flex justify-between items-start mb-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-blue-600 text-white flex items-center justify-center font-black text-lg">
                                            {{ $feedback->user ? substr($feedback->user->first_name, 0, 1) : 'A' }}
                                        </div>
                                        <div>
                                            <h4 class="font-black text-slate-900 dark:text-white">{{ $feedback->user ? $feedback->user->name : 'Anonymous Participant' }}</h4>
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">{{ $feedback->user->affiliation ?? ($feedback->participant_type ?? 'Delegated Intelligence') }}</p>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-end">
                                        <div class="flex gap-1 mb-2">
                                            @for($i=1; $i<=5; $i++)
                                                <svg class="w-3.5 h-3.5 {{ $i <= $feedback->overall_rating ? 'text-amber-400 fill-current' : 'text-slate-300 dark:text-slate-700' }}" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                            @endfor
                                        </div>
                                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $feedback->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div class="p-6 bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm italic text-slate-600 dark:text-slate-400 leading-relaxed">
                                        "{{ $feedback->what_worked_well ?? $feedback->most_valuable_session }}"
                                    </div>
                                    @if($feedback->what_needs_improvement)
                                        <div class="flex gap-3 px-4">
                                            <div class="w-1.5 h-1.5 bg-amber-500 rounded-full mt-2 shrink-0"></div>
                                            <p class="text-sm text-slate-500 dark:text-slate-500 leading-relaxed">
                                                <span class="font-black text-[10px] uppercase tracking-widest text-slate-400 mr-2">Suggestion:</span>
                                                {{ $feedback->what_needs_improvement }}
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-12">
                        {{ $feedbacks->links() }}
                    </div>
                </div>
            </div>

            <!-- Statistics Sidebar (Right Column) -->
            <div class="lg:col-span-4 space-y-10">
                <!-- Theme Sentiment -->
                <div class="p-10 bg-white dark:bg-slate-800 rounded-[3rem] shadow-2xl border border-slate-100 dark:border-slate-700">
                    <h3 class="text-xl font-black text-slate-900 dark:text-white tracking-tight mb-8">Metric Density</h3>

                    <div class="space-y-8">
                        @foreach([
                            ['label' => 'Content relevance', 'avg' => $averageContent, 'color' => 'indigo'],
                            ['label' => 'Technical Quality', 'avg' => $averageTechnical, 'color' => 'cyan'],
                            ['label' => 'Logistics Flow', 'avg' => $averageOrganization, 'color' => 'emerald'],
                        ] as $metric)
                            <div>
                                <div class="flex justify-between items-end mb-3">
                                    <span class="text-[10px] font-black uppercase text-slate-500 tracking-widest">{{ $metric['label'] }}</span>
                                    <span class="text-sm font-black text-slate-900 dark:text-white">{{ number_format($metric['avg'], 1) }}</span>
                                </div>
                                <div class="w-full bg-slate-50 dark:bg-slate-900 rounded-full h-3 p-0.5 border border-slate-100 dark:border-slate-700">
                                    <div class="h-full bg-{{ $metric['color'] }}-500 rounded-full transition-all duration-1000 shadow-[0_0_10px_rgba(var(--{{ $metric['color'] }}-500),0.3)]" style="width: {{ ($metric['avg']/5)*100 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Distribution Matrix -->
                <div class="p-10 bg-slate-900 rounded-[3rem] shadow-2xl text-white">
                    <h3 class="text-xl font-black tracking-tight mb-8">Satisfaction Matrix</h3>

                    <div class="space-y-6">
                        @php
                            $satisfactionLabels = [
                                5 => 'Exceptional',
                                4 => 'Very Robust',
                                3 => 'Standard',
                                2 => 'Marginal',
                                1 => 'Critical'
                            ];
                        @endphp

                        @foreach([5, 4, 3, 2, 1] as $level)
                            @php
                                $count = $satisfactionDistribution[$level] ?? 0;
                                $percentage = $totalFeedback > 0 ? ($count / $totalFeedback) * 100 : 0;
                            @endphp
                            <div class="group">
                                <div class="flex justify-between items-center mb-2">
                                    <div class="flex items-center gap-3">
                                        <span class="text-[10px] font-black uppercase text-slate-500 tracking-widest">{{ $level }} ★</span>
                                        <span class="text-[10px] font-black uppercase text-slate-400 hidden group-hover:inline">{{ $satisfactionLabels[$level] }}</span>
                                    </div>
                                    <span class="text-xs font-black text-slate-300">{{ $count }}</span>
                                </div>
                                <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-indigo-500 to-cyan-400 rounded-full transition-all duration-1000" style="width: {{ $percentage }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Strategic Summary -->
                <div class="relative group">
                    <div class="absolute inset-0 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-[3rem] blur-xl opacity-20 group-hover:opacity-40 transition-opacity"></div>
                    <div class="relative p-10 bg-white dark:bg-slate-800 rounded-[3rem] border border-slate-100 dark:border-slate-700 overflow-hidden">
                        <div class="absolute top-0 right-0 p-6 opacity-10">
                            <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                        </div>
                        @php
                            $sentimentLabel = 'Exceptional';
                            $sentimentColor = 'indigo';
                            if ($averageSatisfaction < 3) {
                                $sentimentLabel = 'Critical';
                                $sentimentColor = 'red';
                            } elseif ($averageSatisfaction < 4) {
                                $sentimentLabel = 'Standard';
                                $sentimentColor = 'amber';
                            } elseif ($averageSatisfaction < 4.5) {
                                $sentimentLabel = 'Robust';
                                $sentimentColor = 'emerald';
                            }
                        @endphp
                        <div class="flex items-center gap-2 px-3 py-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-full w-fit mb-4">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span class="text-[10px] font-black uppercase tracking-widest">Conference Insight Summary</span>
                        </div>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-4 tracking-tight">Summary of Experience</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed font-medium">
                            Based on participant data, the conference is currently rated as
                            <span class="text-{{ $sentimentColor }}-600 dark:text-{{ $sentimentColor }}-400 font-bold">{{ $sentimentLabel }}</span>.
                            {{ $averageContent > 4 ? 'Exceptional content quality' : 'Organization and logistics' }} is the primary driver of participant satisfaction.
                        </p>
                        <div class="mt-8 pt-8 border-t border-slate-100 dark:border-slate-700">
                            <div class="flex items-center gap-4">
                                <span class="w-3 h-3 bg-emerald-500 rounded-full animate-ping"></span>
                                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Automated Insight Active</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;900&display=swap');
    :root { font-family: 'Outfit', sans-serif; }

    .bounce { animation: bounce 2s infinite; }
    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
        40% {transform: translateY(-5px);}
        60% {transform: translateY(-3px);}
    }
</style>
@endsection
