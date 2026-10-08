// Pays with an M-Pesa prompt without leaving the page.
//
// The request returns once the participant enters their PIN (about 15 seconds)
// or M-Pesa gives up (about 30). If the answer is unclear or the connection
// drops, the page keeps asking the server, which asks M-Pesa, until it knows.

const POLL_EVERY_MS = 5000;
const POLL_FOR_MS = 3 * 60 * 1000;

export default ({ storeUrl, statusUrl, initialState = 'idle' }) => ({
    state: initialState, // idle | sending | pending | confirmed | failed | unknown
    message: '',
    transaction: null,
    seconds: 0,
    timer: null,

    init() {
        if (this.state === 'pending') this.poll();
    },

    get busy() {
        return this.state === 'sending' || this.state === 'pending';
    },

    async pay(form) {
        this.start('sending');

        try {
            const response = await fetch(storeUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form),
            });

            if (response.status === 422) {
                const body = await response.json();
                this.finish('failed', body.errors?.mpesa_phone?.[0] ?? body.message);
                return;
            }
            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            this.apply(await response.json());
        } catch {
            // Cut off before M-Pesa answered. The payment is saved: ask for it.
            this.poll();
        }
    },

    async poll() {
        this.start('pending');
        const until = Date.now() + POLL_FOR_MS;

        while (this.state === 'pending' && Date.now() < until) {
            await new Promise((resolve) => setTimeout(resolve, POLL_EVERY_MS));
            try {
                const response = await fetch(statusUrl, { headers: { Accept: 'application/json' } });
                if (response.ok) this.apply(await response.json());
            } catch {
                // Keep trying until the time is up.
            }
        }

        if (this.state === 'pending') {
            this.finish('unknown', 'M-Pesa has not confirmed the payment yet. If you entered your PIN, check again in a few minutes. You will not be charged twice.');
        }
    },

    apply({ state, message, transaction }) {
        if (state === 'pending') {
            if (this.state !== 'pending') this.poll();
            return;
        }

        this.transaction = transaction;
        this.finish(state, message);

        // Reload so the badge and status show the confirmed registration, or,
        // from the waiting screen, so a failed payment can be tried again.
        if (state === 'confirmed' || initialState === 'pending') setTimeout(() => window.location.reload(), 2500);
    },

    start(state) {
        this.state = state;
        this.message = '';
        if (!this.timer) {
            this.seconds = 0;
            this.timer = setInterval(() => this.seconds++, 1000);
        }
    },

    finish(state, message) {
        this.state = state;
        this.message = message;
        clearInterval(this.timer);
        this.timer = null;
    },
});
