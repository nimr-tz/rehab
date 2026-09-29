import axios from 'axios';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Send the CSRF token with every AJAX request so writes don't 419. Axios also
// auto-sends the XSRF-TOKEN cookie, but the meta tag is the authoritative token
// for the current page and covers cases where the cookie is missing/stale.
const csrfToken = document.head.querySelector('meta[name="csrf-token"]');
if (csrfToken) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.content;
}
