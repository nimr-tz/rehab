// Photo uploads for photographers. Each photo goes up in small chunks, two
// photos at a time, so large camera files pass the server's request limits and
// a dropped connection only costs one chunk, which is retried automatically.

const PARALLEL = 2;
const ATTEMPTS = 4;
const ACTIVE = ['waiting', 'uploading', 'processing'];

const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

export default ({ url, chunkSize, maxBytes }) => ({
    items: [],
    publish: false,
    dragging: false,
    added: 0,
    running: 0,

    init() {
        window.addEventListener('beforeunload', (event) => {
            if (this.pending > 0) event.preventDefault();
        });
    },

    get pending() {
        return this.items.filter((item) => ACTIVE.includes(item.status)).length;
    },

    get failed() {
        return this.items.filter((item) => item.status === 'failed').length;
    },

    get finished() {
        return this.items.filter((item) => ['done', 'duplicate'].includes(item.status)).length;
    },

    pick(event) {
        this.add(event.target.files);
        event.target.value = '';
    },

    drop(event) {
        this.dragging = false;
        this.add(event.dataTransfer.files);
    },

    add(files) {
        for (const file of files) {
            const item = { key: crypto.randomUUID(), file, name: file.name, size: file.size, progress: 0, status: 'waiting', error: null, retryable: true };

            if (!/\.(jpe?g|png)$/i.test(file.name)) {
                Object.assign(item, { status: 'failed', error: 'Only JPEG and PNG photos can be uploaded.', retryable: false });
            } else if (file.size > maxBytes) {
                Object.assign(item, { status: 'failed', error: `Photos can be up to ${Math.round(maxBytes / 1048576)} MB.`, retryable: false });
            }

            this.items.push(item);
        }

        this.next();
    },

    next() {
        while (this.running < PARALLEL) {
            const item = this.items.find((candidate) => candidate.status === 'waiting');
            if (!item) return;

            this.running++;
            this.upload(item).finally(() => {
                this.running--;
                this.next();
            });
        }
    },

    async upload(item) {
        item.status = 'uploading';

        const total = Math.max(1, Math.ceil(item.size / chunkSize));
        const uploadId = crypto.randomUUID(); // a fresh id per attempt, so a retry starts clean

        try {
            for (let index = 0; index < total; index++) {
                const body = new FormData();
                body.append('upload_id', uploadId);
                body.append('name', item.name);
                body.append('size', item.size);
                body.append('total', total);
                body.append('index', index);
                body.append('publish', this.publish ? '1' : '0');
                body.append('chunk', item.file.slice(index * chunkSize, (index + 1) * chunkSize), 'chunk');

                if (index === total - 1) item.status = 'processing';

                const result = await this.send(body);
                item.progress = Math.round(((index + 1) / total) * 100);

                if (result.done) {
                    item.status = result.duplicate ? 'duplicate' : 'done';
                    if (!result.duplicate) this.added++;
                }
            }

            if (!['done', 'duplicate'].includes(item.status)) {
                throw new Error('The upload did not finish. Retry this photo.');
            }
        } catch (error) {
            item.status = 'failed';
            item.error = error.message;
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
            return retry('The connection dropped. Check the network, then retry.');
        }

        if (response.ok) return response.json();

        if (response.status === 422) {
            const data = await response.json().catch(() => ({}));
            const first = data.errors ? Object.values(data.errors)[0][0] : null;
            throw new Error(first || data.message || 'This photo could not be uploaded.');
        }
        if (response.status === 419) throw new Error('Your session has expired. Reload the page and sign in again.');
        if (response.status === 413) throw new Error('The server refused the upload size. Ask the administrator to lower GALLERY_CHUNK_KB.');
        if (response.status >= 500 || response.status === 429) return retry(`The server had a problem (error ${response.status}). Retry this photo.`);

        throw new Error(`The upload failed (error ${response.status}).`);
    },

    retry(item) {
        Object.assign(item, { status: 'waiting', progress: 0, error: null });
        this.next();
    },

    retryFailed() {
        this.items.filter((item) => item.status === 'failed' && item.retryable)
            .forEach((item) => Object.assign(item, { status: 'waiting', progress: 0, error: null }));
        this.next();
    },

    clearFinished() {
        this.items = this.items.filter((item) => !['done', 'duplicate'].includes(item.status));
    },

    sizeLabel(bytes) {
        return bytes >= 1048576 ? `${(bytes / 1048576).toFixed(1)} MB` : `${Math.max(1, Math.round(bytes / 1024))} KB`;
    },

    statusLabel(item) {
        return {
            waiting: `Waiting · ${this.sizeLabel(item.size)}`,
            uploading: `Uploading · ${item.progress}%`,
            processing: 'Preparing the web copies…',
            done: 'Uploaded',
            duplicate: 'Already in this album, so it was skipped',
            failed: item.error,
        }[item.status];
    },

    get summary() {
        const total = this.items.length;
        const parts = [`${this.finished} of ${total} uploaded`];
        if (this.failed) parts.push(`${this.failed} failed`);

        return parts.join(' · ');
    },
});
