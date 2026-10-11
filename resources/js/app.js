import Alpine from 'alpinejs';
import fileUploader from './file-uploader';
import photoUploader from './photo-uploader';
import scorecard from './scorecard';
import mpesaPay from './mpesa-pay';

Alpine.data('scorecard', scorecard);
Alpine.data('mpesaPay', mpesaPay);

// Animated 2027 logo, in two parts: an intro that builds the logo up from an
// empty first frame (played once), then a loop of the dots orbiting the
// finished logo, which repeats without a visible restart. The intro's last
// frame leads straight into the loop's first.
//
// With reduced motion the still image stays. If the intro has not loaded
// within 1.2s, the still stays until the loop is ready, then the loop takes
// over: it shows the finished logo, so the change is not noticeable.
const LOGO_INTRO_MS = 20 * 80;

Alpine.data('brandLogo', (introSrc, loopSrc) => ({
    animated: false,
    stillHidden: false,

    init() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        const loop = new Image();
        const loopReady = new Promise((resolve) => {
            loop.onload = resolve;
            loop.onerror = () => {};
        });
        loop.src = loopSrc;

        const play = (src) => {
            this.$refs.anim.src = src;
            this.animated = true;
            this.stillHidden = true;
        };

        this.stillHidden = true;
        let introStarted = false;
        const fallback = setTimeout(() => {
            if (introStarted) return;
            introStarted = true;
            this.stillHidden = false;
            loopReady.then(() => play(loopSrc));
        }, 1200);

        const intro = new Image();
        intro.onload = () => {
            if (introStarted) return;
            introStarted = true;
            clearTimeout(fallback);
            play(introSrc);
            setTimeout(() => loopReady.then(() => play(loopSrc)), LOGO_INTRO_MS);
        };
        intro.onerror = () => {};
        intro.src = introSrc;
    },
}));

Alpine.data('photoUploader', photoUploader);
Alpine.data('fileUploader', fileUploader);

// The gallery lightbox loads only on pages with a photo grid.
if (document.querySelector('[data-gallery]')) {
    import('./gallery');
}

window.Alpine = Alpine;
Alpine.start();
