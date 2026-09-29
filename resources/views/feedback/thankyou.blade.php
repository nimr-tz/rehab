@extends(Auth::check() ? 'layouts.app' : 'layouts.public')

@section('title', 'Thank You | ' . config('conference.short_name') . ' ' . config('conference.year'))

@section('content')
<div class="min-h-screen bg-slate-50 dark:bg-slate-900 overflow-hidden flex flex-col items-center justify-center py-20 px-6 relative @guest pt-28 @endguest">
    {{-- Animated Background Elements --}}
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-indigo-500/10 rounded-full blur-[100px] animate-pulse"></div>
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-blue-500/10 rounded-full blur-[100px] animate-pulse" style="animation-delay: 2s"></div>
    </div>

    {{-- Confetti Canvas (Simple CSS Confetti) --}}
    <div id="confetti-container" class="absolute inset-0 overflow-hidden pointer-events-none"></div>

    <div class="max-w-3xl w-full relative z-10 text-center animate-fade-in-up">
        {{-- Elite Icon --}}
        <div class="inline-flex items-center justify-center w-32 h-32 bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-2xl shadow-indigo-200 dark:shadow-none border border-slate-100 dark:border-slate-700 mb-10 transform scale-110 hover:rotate-12 transition-transform duration-500">
            <span class="text-7xl">🎉</span>
        </div>

        <h1 class="text-5xl md:text-7xl font-black text-slate-900 dark:text-white mb-6 tracking-tighter">
            Thank <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-blue-500">You!</span>
        </h1>

        <p class="text-xl text-slate-500 dark:text-slate-400 font-medium max-w-xl mx-auto leading-relaxed mb-12">
            Your feedback has been received. We appreciate you taking the time to share your experience — it will help us improve future editions of the conference.
        </p>

        {{-- Interactive Impact Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-16">
            <div class="p-8 bg-white dark:bg-slate-800 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-700 hover:-translate-y-2 transition-transform">
                <div class="text-3xl mb-4">🧬</div>
                <div class="text-2xl font-black text-slate-900 dark:text-white">Scientific</div>
                <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Growth Driven</div>
            </div>
            <div class="p-8 bg-white dark:bg-slate-800 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-700 hover:-translate-y-2 transition-transform">
                <div class="text-3xl mb-4">🤝</div>
                <div class="text-2xl font-black text-slate-900 dark:text-white">Community</div>
                <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Impact Made</div>
            </div>
            <div class="p-8 bg-white dark:bg-slate-800 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-700 hover:-translate-y-2 transition-transform">
                <div class="text-3xl mb-4">📊</div>
                <div class="text-2xl font-black text-slate-900 dark:text-white">Strategic</div>
                <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Insights Gained</div>
            </div>
        </div>

        {{-- Call to Actions --}}
        <div class="flex flex-col sm:flex-row items-center justify-center gap-6">
            @auth
                <a href="{{ route('dashboard') }}" class="w-full sm:w-auto px-10 py-5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl font-black text-sm uppercase tracking-widest shadow-2xl hover:scale-105 transition-all flex items-center justify-center gap-4">
                    Return to Dashboard
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                </a>
                @if(auth()->user()->canDownloadCertificate())
                <a href="{{ route('certificate.index') }}" class="w-full sm:w-auto px-10 py-5 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-2xl font-black text-sm uppercase tracking-widest border border-indigo-100 dark:border-indigo-800 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-all flex items-center justify-center gap-4">
                    Get Your Certificate
                    <span>🎓</span>
                </a>
                @endif
            @else
                <a href="{{ route('home') }}" class="w-full sm:w-auto px-10 py-5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl font-black text-sm uppercase tracking-widest shadow-2xl hover:scale-105 transition-all flex items-center justify-center gap-4">
                    Back to {{ config('conference.short_name') }} {{ config('conference.year') }}
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                </a>
            @endauth
        </div>

    </div>
</div>

<style>
    .animate-fade-in-up {
        animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(40px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .confetti {
        position: absolute;
        width: 10px;
        height: 10px;
        background-color: #f2d74e;
        top: -10px;
        z-index: 11;
        animation: confetti 5s ease-in infinite;
    }
    @keyframes confetti {
        0% { transform: translateY(0) rotateZ(0deg); opacity: 1; }
        100% { transform: translateY(100vh) rotateZ(720deg); opacity: 0; }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('confetti-container');
        const colors = ['#6366f1', '#3b82f6', '#10b981', '#f59e0b', '#ef4444'];

        for (let i = 0; i < 50; i++) {
            const confetti = document.createElement('div');
            confetti.classList.add('confetti');
            confetti.style.left = Math.random() * 100 + 'vw';
            confetti.style.animationDelay = Math.random() * 5 + 's';
            confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            confetti.style.width = (Math.random() * 8 + 4) + 'px';
            confetti.style.height = confetti.style.width;
            container.appendChild(confetti);
        }
    });
</script>
@endsection
