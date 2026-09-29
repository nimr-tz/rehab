/**
 * Lightweight, opt-in form auto-save to localStorage.
 *
 * Why: even with graceful 419 handling, a dropped session (or a refresh / closed
 * tab / crash) can wipe out a long form. This keeps a local copy of what the user
 * typed and restores it when they come back — no server round-trip, so it survives
 * even a fully expired session.
 *
 * Usage:
 *   <form data-autosave="abstract-create"> ... </form>
 *   - The data-autosave value is the storage key; make it unique per form
 *     (include a record id for edit forms, e.g. "abstract-edit-42").
 *   - Add data-no-autosave to any field that must never be persisted.
 *
 * Rich widgets (rich-text editors, custom pickers, dynamically-added rows) keep
 * their value in plain inputs that we save, but their visible UI needs to be
 * re-synced after a restore. Two hooks are provided:
 *   - window.FormAutosave.get(key)  -> returns the saved { field: value } map (or null)
 *   - the form fires an `autosave:restored` event with { detail: { data } }
 *   - call window.FormAutosave.save(formEl) after programmatically changing a
 *     hidden input (programmatic value changes don't fire input events).
 *
 * Storage is cleared automatically when the form is submitted.
 */

const PREFIX = 'conference:autosave:';
const TTL_MS = 24 * 60 * 60 * 1000; // drop drafts older than 24h
const DEBOUNCE_MS = 600;

// Never persist these — CSRF/method spoofing tokens, secrets, and binaries.
const SKIP_NAMES = ['_token', '_method'];
const SKIP_TYPES = ['password', 'file'];

function keyFor(form) {
    return PREFIX + form.dataset.autosave;
}

function shouldSkip(field) {
    if (!field.name) return true;
    if (field.dataset.noAutosave !== undefined) return true;
    if (SKIP_NAMES.includes(field.name)) return true;
    if (SKIP_TYPES.includes((field.type || '').toLowerCase())) return true;
    return false;
}

function collect(form) {
    const data = {};
    form.querySelectorAll('input, select, textarea').forEach((field) => {
        if (shouldSkip(field)) return;

        const type = (field.type || '').toLowerCase();
        if (type === 'checkbox') {
            data[field.name] = field.checked;
        } else if (type === 'radio') {
            if (field.checked) data[field.name] = field.value;
        } else {
            data[field.name] = field.value;
        }
    });
    return data;
}

function save(form) {
    if (!form || !form.dataset.autosave) return;
    try {
        localStorage.setItem(keyFor(form), JSON.stringify({
            savedAt: Date.now(),
            data: collect(form),
        }));
        flashIndicator('Draft saved');
    } catch (e) {
        // localStorage can be full or disabled (private mode) — fail silently.
    }
}

function read(key) {
    try {
        const raw = localStorage.getItem(PREFIX + key);
        if (!raw) return null;
        const parsed = JSON.parse(raw);
        if (!parsed || !parsed.data) return null;
        if (Date.now() - (parsed.savedAt || 0) > TTL_MS) {
            localStorage.removeItem(PREFIX + key);
            return null;
        }
        return parsed.data;
    } catch (e) {
        return null;
    }
}

function clear(key) {
    try {
        localStorage.removeItem(PREFIX + key);
    } catch (e) {
        // ignore
    }
}

function restore(form) {
    const data = read(form.dataset.autosave);
    if (!data) return;

    form.querySelectorAll('input, select, textarea').forEach((field) => {
        if (shouldSkip(field) || !(field.name in data)) return;

        const type = (field.type || '').toLowerCase();
        if (type === 'checkbox') {
            field.checked = !!data[field.name];
        } else if (type === 'radio') {
            field.checked = field.value === data[field.name];
        } else {
            field.value = data[field.name];
        }
    });

    // Let rich widgets (Quill, custom pickers, dynamic rows) re-sync their UI.
    form.dispatchEvent(new CustomEvent('autosave:restored', { detail: { data } }));
    flashIndicator('Draft restored', true);
}

// --- tiny, dependency-free status pill ---------------------------------------
let indicatorEl = null;
let indicatorTimer = null;

function flashIndicator(text, sticky = false) {
    if (typeof document === 'undefined') return;
    if (!indicatorEl) {
        indicatorEl = document.createElement('div');
        indicatorEl.setAttribute('role', 'status');
        indicatorEl.style.cssText = [
            'position:fixed', 'bottom:18px', 'right:18px', 'z-index:9999',
            'padding:8px 14px', 'border-radius:9999px',
            'background:rgba(15,23,42,0.92)', 'color:#fff',
            'font:600 12px/1 ui-sans-serif,system-ui,sans-serif',
            'box-shadow:0 6px 20px rgba(0,0,0,0.25)',
            'opacity:0', 'transition:opacity .25s ease', 'pointer-events:none',
        ].join(';');
        document.body.appendChild(indicatorEl);
    }
    indicatorEl.textContent = '✓ ' + text;
    indicatorEl.style.opacity = '1';
    clearTimeout(indicatorTimer);
    indicatorTimer = setTimeout(() => { indicatorEl.style.opacity = '0'; }, sticky ? 3500 : 1500);
}

function init(form) {
    if (form.__autosaveBound) return;
    form.__autosaveBound = true;

    restore(form);

    let debounce = null;
    const queueSave = () => {
        clearTimeout(debounce);
        debounce = setTimeout(() => save(form), DEBOUNCE_MS);
    };

    form.addEventListener('input', queueSave);
    form.addEventListener('change', queueSave);

    // Capture the final state if the user closes/navigates away without submitting.
    window.addEventListener('pagehide', () => save(form));

    // A successful submit means the data is now server-side; drop the local copy.
    form.addEventListener('submit', () => clear(form.dataset.autosave));
}

function boot() {
    document.querySelectorAll('form[data-autosave]').forEach(init);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

// Public API for rich-widget integration.
window.FormAutosave = {
    get: (key) => read(key),
    save,
    clear,
};
