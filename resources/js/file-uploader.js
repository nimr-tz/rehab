// One large file (presentation slides), sent in small chunks so it passes the
// server's request limit. A dropped connection only costs one chunk, which is
// retried. When the last chunk is in, the page reloads to show the new file.

const ATTEMPTS = 4;

const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

export default ({ url, chunkSize, maxBytes, extensions }) => ({
    file: null,
    progress: 0,
    busy: false,
    error: null,

    pick(event) {
        const file = event.target.files[0];
        event.target.value = '';
        if (!file) return;

        const extension = file.name.split('.').pop().toLowerCase();
        if (!extensions.includes(extension)) {
            this.error = `Upload the slides as ${extensions.map((e) => e.toUpperCase()).join(', ')}.`;
            return;
        }
        if (file.size > maxBytes) {
            this.error = `Slides can be up to ${Math.round(maxBytes / 1048576)} MB.`;
            return;
        }

        this.file = file;
        this.upload();
    },

    async upload() {
        Object.assign(this, { busy: true, error: null, progress: 0 });

        const total = Math.max(1, Math.ceil(this.file.size / chunkSize));
        const uploadId = crypto.randomUUID();

        try {
            for (let index = 0; index < total; index++) {
                const body = new FormData();
                body.append('upload_id', uploadId);
                body.append('name', this.file.name);
                body.append('size', this.file.size);
                body.append('total', total);
                body.append('index', index);
                body.append('chunk', this.file.slice(index * chunkSize, (index + 1) * chunkSize), 'chunk');

                const result = await this.send(body);
                this.progress = Math.round(((index + 1) / total) * 100);

                if (result.done) {
                    window.location.reload();
                    return;
                }
            }

            throw new Error('The upload did not finish. Try again.');
        } catch (error) {
            this.error = error.message;
            this.busy = false;
        }
    },

    async send(body, attempt = 1) {
        const retry = async (message) => {
            if (attempt >= ATTEMPTS) throw new Error(message);
            await wait(attempt * 2000);
            return this.send(body, attempt + 1);
        };

        let response;
        try {
            response = await fetch(url, {
                method: 'POST',
                body,
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });
        } catch {
            return retry('The connection dropped. Check the network and try again.');
        }

        if (response.ok) return response.json();

        if (response.status === 422 || response.status === 403) {
            const data = await response.json().catch(() => ({}));
            const first = data.errors ? Object.values(data.errors)[0][0] : null;
            throw new Error(first || data.message || 'The slides could not be uploaded.');
        }
        if (response.status === 419) throw new Error('Your session has expired. Reload the page and sign in again.');
        if (response.status >= 500 || response.status === 429) return retry(`The server had a problem (error ${response.status}). Try again.`);

        throw new Error(`The upload failed (error ${response.status}).`);
    },
});
