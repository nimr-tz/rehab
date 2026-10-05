{{-- Soft waves, glows and orbs behind the sign-in screens. Decorative only. --}}
<div class="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
    <div class="absolute -left-40 -top-40 h-[36rem] w-[36rem] rounded-full bg-brand-100/60 blur-3xl"></div>
    <div class="absolute -bottom-48 right-[-10rem] h-[40rem] w-[40rem] rounded-full bg-brand-200/50 blur-3xl"></div>
    <div class="absolute left-[30%] top-[55%] h-72 w-72 rounded-full bg-sun-100/50 blur-3xl"></div>

    <svg class="absolute bottom-0 left-0 w-full" viewBox="0 0 1440 320" preserveAspectRatio="none" style="height: 42vh">
        <defs>
            <linearGradient id="wave-a" x1="0" x2="1" y1="0" y2="0">
                <stop offset="0" stop-color="#d7edf4" stop-opacity="0.55"/>
                <stop offset="1" stop-color="#7cc0d7" stop-opacity="0.45"/>
            </linearGradient>
            <linearGradient id="wave-b" x1="0" x2="1" y1="0" y2="0">
                <stop offset="0" stop-color="#ffffff" stop-opacity="0.9"/>
                <stop offset="1" stop-color="#b0dbe9" stop-opacity="0.5"/>
            </linearGradient>
        </defs>
        <path fill="url(#wave-a)" d="M0,192 C240,120 420,288 720,240 C1010,194 1180,96 1440,150 L1440,320 L0,320 Z"/>
        <path fill="url(#wave-b)" d="M0,256 C300,200 520,320 820,280 C1080,246 1260,206 1440,236 L1440,320 L0,320 Z"/>
    </svg>

    <svg class="absolute right-0 top-0 h-full w-[45%] opacity-60" viewBox="0 0 600 900" preserveAspectRatio="none">
        <path fill="#eff8fb" d="M600,0 L600,900 L420,900 C520,760 380,620 470,470 C560,320 430,170 520,0 Z"/>
    </svg>

    <span class="orb absolute left-[7%] top-[9%] h-16 w-16 rounded-full bg-gradient-to-br from-brand-300 to-brand-600 opacity-40 blur-[1px]"></span>
    <span class="orb orb-slow absolute left-[42%] top-[13%] h-9 w-9 rounded-full bg-gradient-to-br from-sun-200 to-sun-400 opacity-80"></span>
    <span class="orb absolute left-[50%] top-[30%] h-7 w-7 rounded-full bg-gradient-to-br from-brand-300 to-brand-700 opacity-70"></span>
    <span class="orb orb-slow absolute left-[11%] top-[66%] h-12 w-12 rounded-full bg-gradient-to-br from-coral-200 to-coral-500 opacity-50 blur-[1px]"></span>
    <span class="orb absolute left-[6%] top-[52%] h-8 w-8 rounded-full bg-gradient-to-br from-brand-400 to-brand-800 opacity-60"></span>
    <span class="orb orb-slow absolute left-[46%] top-[70%] h-3 w-3 rounded-full bg-ember-500 opacity-80"></span>
    <span class="orb absolute right-[4%] top-[18%] h-3 w-3 rounded-full bg-brand-500 opacity-40"></span>

    <div class="absolute left-[3%] top-[18%] h-28 w-24 bg-[radial-gradient(#7cc0d7_1.2px,transparent_1.2px)] [background-size:12px_12px] opacity-50"></div>
    <div class="absolute bottom-[18%] left-[49%] h-24 w-24 bg-[radial-gradient(#7cc0d7_1.2px,transparent_1.2px)] [background-size:12px_12px] opacity-40"></div>
</div>

