// The public gallery lightbox (PhotoSwipe). Each photo link carries its size,
// download link, removal link and caption as data attributes. Opening a photo
// puts #photo-<id> in the address bar, so a single photo can be shared.

import PhotoSwipeLightbox from 'photoswipe/lightbox';
import 'photoswipe/style.css';
import '../css/gallery.css';

const icon = (path) =>
    `<svg aria-hidden="true" class="pswp__icn" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="${path}"/></svg>`;

const DOWNLOAD = 'M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3';
const FLAG = 'M3 3v1.5M3 21v-6m0 0 2.77-.69a9 9 0 0 1 6.21.68l.11.05a9 9 0 0 0 6.2.68l3.11-.78a48.5 48.5 0 0 1-.01-10.5l-3.1.77a9 9 0 0 1-6.21-.68l-.11-.05a9 9 0 0 0-6.21-.68L3 4.5M3 15V4.5';

document.querySelectorAll('[data-gallery]').forEach((gallery) => {
    const links = [...gallery.querySelectorAll('a[data-pswp-width]')];
    const current = (pswp) => pswp.currSlide.data.element;

    const lightbox = new PhotoSwipeLightbox({
        gallery,
        children: 'a[data-pswp-width]',
        pswpModule: () => import('photoswipe'),
        bgOpacity: 0.95,
        paddingFn: (viewport) => (viewport.x >= 768 ? { top: 56, bottom: 88, left: 72, right: 72 } : { top: 48, bottom: 88, left: 0, right: 0 }),
    });

    lightbox.on('uiRegister', () => {
        const ui = lightbox.pswp.ui;

        ui.registerElement({
            name: 'download',
            order: 8,
            isButton: true,
            tagName: 'a',
            title: 'Download full resolution',
            html: icon(DOWNLOAD),
            onInit: (el, pswp) => {
                el.setAttribute('download', '');
                pswp.on('change', () => (el.href = current(pswp).dataset.download));
            },
        });

        ui.registerElement({
            name: 'removal',
            order: 7,
            isButton: true,
            title: 'Ask for this photo to be removed',
            html: icon(FLAG),
            onClick: (event, el, pswp) => {
                const link = current(pswp);
                pswp.close();
                window.dispatchEvent(new CustomEvent('photo-removal', { detail: { url: link.dataset.removal, thumb: link.querySelector('img').src } }));
            },
        });

        ui.registerElement({
            name: 'caption',
            order: 9,
            isButton: false,
            appendTo: 'root',
            onInit: (el, pswp) => {
                el.classList.add('gallery-caption');
                pswp.on('change', () => (el.textContent = current(pswp).dataset.caption || ''));
            },
        });
    });

    lightbox.on('change', () => {
        history.replaceState(null, '', `#${current(lightbox.pswp).id}`);
    });
    lightbox.on('close', () => {
        history.replaceState(null, '', location.pathname + location.search);
    });

    lightbox.init();

    const shared = links.findIndex((link) => `#${link.id}` === location.hash);
    if (shared >= 0) lightbox.loadAndOpen(shared);
});
