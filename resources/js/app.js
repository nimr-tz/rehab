import './bootstrap';
import './autosave';
import './dashboard-theming';

// Import Chart.js and expose globally
import Chart from 'chart.js/auto';
window.Chart = Chart;

import Alpine from 'alpinejs';

window.Alpine = Alpine;

function startAlpine() {
    if (window.__alpineStarted) return;
    window.__alpineStarted = true;
    Alpine.start();
}

if (document.readyState === 'complete') {
    startAlpine();
} else {
    window.addEventListener('load', startAlpine, { once: true });
}

// Reviewer Assignment Page Modal Logic
window.showChangeReviewerModal = function (abstractId, position, currentReviewerName) {
    const form = document.getElementById('change-reviewer-form');
    const positionInput = document.getElementById('change-reviewer-position');
    const currentNameElement = document.getElementById('current-reviewer-name');
    if (!form) return;
    form.action = `/admin/abstracts/${abstractId}/assign-reviewer`;
    positionInput.value = position;
    currentNameElement.textContent = currentReviewerName;
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'change-reviewer-modal' }));
};

window.suggestReviewers = function (abstractId) {
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'ai-suggest-modal' }));
};

window.checkConflicts = function (abstractId) {
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'conflict-modal' }));
};

window.clearAllAssignments = function (abstractId) {
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'clear-assignments-modal', abstractId }));
};

window.showAutoAssignModal = function () {
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'auto-assign-modal' }));
};
