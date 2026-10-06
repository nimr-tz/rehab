import Alpine from 'alpinejs';
import photoUploader from './photo-uploader';

// Animated 2027 logo. It builds up from an empty first frame, so it replaces the
// still image instead of layering over it. On small screens, with reduced motion,
// or if it has not loaded within 1.2s, the still image stays.
//
// Browsers do not agree on the WebP loop count, so the page ends the animation
// itself: after one run it swaps back to the still, which is the final frame.
const LOGO_ANIMATION_MS = 2800;

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

            setTimeout(() => {
                this.stillHidden = false;
                this.animated = false;
                this.$refs.anim.removeAttribute('src');
            }, LOGO_ANIMATION_MS);
        };
        img.onerror = showStill;
        img.src = animatedSrc;
    },
}));

Alpine.data('photoUploader', photoUploader);

// The gallery lightbox loads only on pages with a photo grid.
if (document.querySelector('[data-gallery]')) {
    import('./gallery');
}

window.Alpine = Alpine;
Alpine.start();
