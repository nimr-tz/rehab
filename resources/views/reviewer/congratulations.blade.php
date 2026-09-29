@extends('layouts.app')
@section('title', 'Thank You — ' . config('conference.short_name') . ' ' . config('conference.year') . ' Review')

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.createElement('canvas');
    canvas.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999;opacity:0.85;';
    document.body.appendChild(canvas);
    const ctx = canvas.getContext('2d');
    let W, H, particles = [], fireworks = [], animId;

    const COLORS = ['#FFD700','#FFC0CB','#00BFFF','#7CFC00','#FF6347','#DA70D6','#40E0D0','#FFFFFF'];

    function resize() {
        W = canvas.width  = window.innerWidth;
        H = canvas.height = window.innerHeight;
    }
    resize();
    window.addEventListener('resize', resize);

    // ── Confetti ──────────────────────────────────────────────
    class Confetto {
        constructor() { this.reset(true); }
        reset(fromTop = false) {
            this.x  = Math.random() * W;
            this.y  = fromTop ? -20 : Math.random() * H;
            this.w  = 5 + Math.random() * 8;
            this.h  = 3 + Math.random() * 5;
            this.vx = (Math.random() - 0.5) * 2;
            this.vy = 1.5 + Math.random() * 3;
            this.angle = Math.random() * Math.PI * 2;
            this.spin  = (Math.random() - 0.5) * 0.15;
            this.color = COLORS[Math.floor(Math.random() * COLORS.length)];
            this.opacity = 0.85 + Math.random() * 0.15;
        }
        update() {
            this.x += this.vx;
            this.y += this.vy;
            this.angle += this.spin;
            if (this.y > H + 30) this.reset();
        }
        draw() {
            ctx.save();
            ctx.globalAlpha = this.opacity;
            ctx.translate(this.x, this.y);
            ctx.rotate(this.angle);
            ctx.fillStyle = this.color;
            ctx.fillRect(-this.w / 2, -this.h / 2, this.w, this.h);
            ctx.restore();
        }
    }

    // ── Firework sparks ────────────────────────────────────────
    class Spark {
        constructor(x, y, color) {
            const angle = Math.random() * Math.PI * 2;
            const speed = 1.5 + Math.random() * 4;
            this.x = x; this.y = y;
            this.vx = Math.cos(angle) * speed;
            this.vy = Math.sin(angle) * speed;
            this.color = color;
            this.life = 1;
            this.decay = 0.015 + Math.random() * 0.025;
            this.size = 2 + Math.random() * 2;
        }
        update() { this.x += this.vx; this.y += this.vy; this.vy += 0.06; this.life -= this.decay; }
        get alive() { return this.life > 0; }
        draw() {
            ctx.save();
            ctx.globalAlpha = Math.max(0, this.life);
            ctx.fillStyle = this.color;
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size * this.life, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
        }
    }

    class Firework {
        constructor() {
            this.x = W * 0.15 + Math.random() * W * 0.7;
            this.y = H;
            this.targetY = H * 0.1 + Math.random() * H * 0.45;
            this.speed = 8 + Math.random() * 5;
            this.color = COLORS[Math.floor(Math.random() * COLORS.length)];
            this.sparks = [];
            this.exploded = false;
        }
        update() {
            if (!this.exploded) {
                this.y -= this.speed;
                if (this.y <= this.targetY) {
                    this.exploded = true;
                    for (let i = 0; i < 80; i++) {
                        this.sparks.push(new Spark(this.x, this.y, this.color));
                    }
                }
            }
            this.sparks = this.sparks.filter(s => { s.update(); return s.alive; });
        }
        get done() { return this.exploded && this.sparks.length === 0; }
        draw() {
            if (!this.exploded) {
                ctx.save();
                ctx.globalAlpha = 0.9;
                ctx.fillStyle = this.color;
                ctx.beginPath();
                ctx.arc(this.x, this.y, 3, 0, Math.PI * 2);
                ctx.fill();
                ctx.restore();
            }
            this.sparks.forEach(s => s.draw());
        }
    }

    // Init confetti
    for (let i = 0; i < 120; i++) particles.push(new Confetto());

    // Launch bursts of fireworks
    function launchFireworks(count = 3) {
        for (let i = 0; i < count; i++) {
            setTimeout(() => fireworks.push(new Firework()), i * 300);
        }
    }
    launchFireworks(5);
    // Repeat every 4 seconds for 20 seconds, then slow down
    let launches = 0;
    const interval = setInterval(() => {
        launches++;
        launchFireworks(2 + Math.floor(Math.random() * 2));
        if (launches >= 5) clearInterval(interval);
    }, 3500);

    function loop() {
        ctx.clearRect(0, 0, W, H);
        particles.forEach(p => { p.update(); p.draw(); });
        fireworks = fireworks.filter(f => !f.done);
        fireworks.forEach(f => { f.update(); f.draw(); });
        animId = requestAnimationFrame(loop);
    }
    loop();
});
</script>
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-br from-[#06153D] via-[#0a2260] to-[#05499c] flex items-center justify-center px-4 py-16">
    <div class="w-full max-w-2xl">

        {{-- Trophy / celebration icon --}}
        <div class="flex justify-center mb-8">
            <div class="relative">
                <div class="w-24 h-24 rounded-full bg-gradient-to-br from-yellow-300 to-yellow-500 flex items-center justify-center shadow-2xl shadow-yellow-500/30">
                    <svg class="w-12 h-12 text-white drop-shadow" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                </div>
                <div class="absolute -top-1 -right-1 w-6 h-6 rounded-full bg-emerald-400 flex items-center justify-center shadow-lg">
                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Main card --}}
        <div class="bg-white/10 backdrop-blur-sm rounded-3xl border border-white/20 shadow-2xl overflow-hidden">

            {{-- Header --}}
            <div class="px-8 pt-10 pb-6 text-center">
                <p class="text-yellow-300 text-xs font-bold uppercase tracking-widest mb-3">{{ config('conference.short_name') }} {{ config('conference.year') }} · Scientific Review</p>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-white leading-tight mb-4">
                    Thank You,<br>{{ $reviewer->name }}.
                </h1>
                <p class="text-blue-100 text-base leading-relaxed max-w-lg mx-auto">
                    The {{ config('conference.edition') }} {{ config('conference.name') }} review process is now complete.
                    Your dedication to scientific rigour has been invaluable in shaping the quality of {{ config('conference.short_name') }} {{ config('conference.year') }}.
                    On behalf of the Scientific Committee, we extend our sincere gratitude.
                </p>
            </div>

            {{-- Divider --}}
            <div class="mx-8 border-t border-white/10"></div>

            {{-- Performance stats --}}
            @if($totalReviewed > 0)
            <div class="px-8 py-8">
                <p class="text-center text-blue-200 text-xs font-bold uppercase tracking-widest mb-6">Your Review Performance</p>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">

                    <div class="bg-white/10 rounded-2xl p-4 text-center border border-white/10">
                        <p class="text-3xl font-black text-white">{{ $totalReviewed }}</p>
                        <p class="text-blue-200 text-xs font-semibold mt-1 uppercase tracking-wide">Abstracts Reviewed</p>
                    </div>

                    @if($qualityScore > 0)
                    <div class="bg-white/10 rounded-2xl p-4 text-center border border-white/10">
                        <p class="text-3xl font-black {{ $qualityScore >= 80 ? 'text-emerald-300' : ($qualityScore >= 60 ? 'text-yellow-300' : 'text-red-300') }}">
                            {{ $qualityScore }}<span class="text-lg font-bold">%</span>
                        </p>
                        <p class="text-blue-200 text-xs font-semibold mt-1 uppercase tracking-wide">Review Quality</p>
                    </div>
                    @endif

                    @if($consistency > 0)
                    <div class="bg-white/10 rounded-2xl p-4 text-center border border-white/10">
                        <p class="text-3xl font-black {{ $consistency >= 80 ? 'text-emerald-300' : 'text-yellow-300' }}">
                            {{ round($consistency) }}<span class="text-lg font-bold">%</span>
                        </p>
                        <p class="text-blue-200 text-xs font-semibold mt-1 uppercase tracking-wide">Consistency</p>
                    </div>
                    @endif

                    @if($onTimeRate > 0)
                    <div class="bg-white/10 rounded-2xl p-4 text-center border border-white/10">
                        <p class="text-3xl font-black {{ $onTimeRate >= 70 ? 'text-emerald-300' : 'text-yellow-300' }}">
                            {{ $onTimeRate }}<span class="text-lg font-bold">%</span>
                        </p>
                        <p class="text-blue-200 text-xs font-semibold mt-1 uppercase tracking-wide">On-Time Rate</p>
                    </div>
                    @endif

                    @if($avgScore > 0)
                    <div class="bg-white/10 rounded-2xl p-4 text-center border border-white/10">
                        <p class="text-3xl font-black text-white">{{ round($avgScore) }}<span class="text-lg font-bold">/100</span></p>
                        <p class="text-blue-200 text-xs font-semibold mt-1 uppercase tracking-wide">Avg. Score Given</p>
                    </div>
                    @endif

                    @if($avgCommentLen > 0)
                    <div class="bg-white/10 rounded-2xl p-4 text-center border border-white/10">
                        <p class="text-3xl font-black text-white">{{ $avgCommentLen }}</p>
                        <p class="text-blue-200 text-xs font-semibold mt-1 uppercase tracking-wide">Avg. Comment Length</p>
                    </div>
                    @endif

                </div>
            </div>

            {{-- Divider --}}
            <div class="mx-8 border-t border-white/10"></div>
            @endif

            {{-- Areas for improvement --}}
            @if(!empty($improvements))
            <div class="px-8 py-8">
                <p class="text-center text-blue-200 text-xs font-bold uppercase tracking-widest mb-1">Areas for Improvement</p>
                <p class="text-center text-blue-300 text-xs mb-6">Honest feedback to help you review even better in future conferences.</p>
                <div class="space-y-4">
                    @foreach($improvements as $item)
                    <div class="bg-amber-500/10 border border-amber-400/30 rounded-2xl p-5">
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 shrink-0">
                                <svg class="w-5 h-5 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-amber-200 text-sm font-semibold mb-1">{{ $item['title'] }}</p>
                                <p class="text-blue-100 text-sm leading-relaxed">{{ $item['body'] }}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="mx-8 border-t border-white/10"></div>
            @endif

            {{-- Closing message --}}
            <div class="px-8 py-8 text-center">
                <p class="text-blue-100 text-sm leading-relaxed max-w-lg mx-auto mb-6">
                    The conference programme is now being finalised. We look forward to welcoming you at
                    <span class="text-white font-semibold">{{ config('conference.short_name') }} {{ config('conference.year') }}</span> and celebrating the research
                    you have helped select and elevate.
                </p>
                <p class="text-yellow-300 font-bold text-sm tracking-wide">
                    The {{ config('conference.short_name') }} {{ config('conference.year') }} Scientific Committee
                </p>
                <p class="text-blue-300 text-xs mt-1">{{ config('conference.host') }} · Tanzania</p>
            </div>

        </div>

        {{-- Footer note --}}
        <p class="text-center text-blue-400 text-xs mt-6">
            For any queries, please contact the conference secretariat at
            <a href="mailto:{{ config('conference.contact_email') }}" class="text-blue-300 hover:text-white underline transition-colors">{{ config('conference.contact_email') }}</a>
        </p>

    </div>
</div>
@endsection
