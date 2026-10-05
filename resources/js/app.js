import Alpine from 'alpinejs';

// Animated 2027 logo. It builds up from an empty first frame, so it replaces the
// still image instead of layering over it. On small screens, with reduced motion,
// or if it has not loaded within 1.2s, the still image stays.
Alpine.data('brandLogo', (animatedSrc) => ({
    animated: false,
    stillHidden: false,

    init() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        if (!window.matchMedia('(min-width: 1024px)').matches) return;

        this.stillHidden = true;
        let settled = false;
        const showStill = () => {
            if (settled) return;
            settled = true;
            this.stillHidden = false;
        };
        const fallback = setTimeout(showStill, 1200);

        const img = new Image();
        img.onload = () => {
            if (settled) return;
            settled = true;
            clearTimeout(fallback);
            this.$refs.anim.src = img.src;
            this.animated = true;
        };
        img.onerror = showStill;
        img.src = animatedSrc;
    },
}));

window.Alpine = Alpine;
Alpine.start();
