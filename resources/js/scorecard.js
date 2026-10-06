// The reviewer's scorecard: rubric sliders, the live total and verdict band,
// the technical sub-checklist and a per-browser draft. The rubric itself
// (criteria, maxima, bands, level words) comes from config/review.php.

export default (config) => ({
    rubric: config.rubric,
    scores: config.scores,
    checks: config.checks,
    recommendation: config.recommendation,
    comment: config.comment,
    note: config.note,
    locked: config.locked,
    draftKey: config.draftKey,
    shownTotal: 0,
    heroVisible: true,
    footerVisible: false,
    restored: false,

    init() {
        if (config.useDraft) this.restoreDraft();
        this.shownTotal = this.total;

        this.$watch('total', (to) => this.countTo(to));
        if (!this.locked) {
            ['scores', 'checks', 'recommendation', 'comment', 'note'].forEach((key) =>
                this.$watch(key, () => this.saveDraft(), { deep: true }),
            );
        }

        // The floating total shows only while neither the hero nor the submit bar is on screen.
        if ('IntersectionObserver' in window && this.$refs.hero) {
            new IntersectionObserver(([entry]) => (this.heroVisible = entry.isIntersecting), { rootMargin: '-80px 0px 0px 0px' })
                .observe(this.$refs.hero);
            if (this.$refs.footer) {
                new IntersectionObserver(([entry]) => (this.footerVisible = entry.isIntersecting)).observe(this.$refs.footer);
            }
        }
    },

    // Totals and bands

    get total() {
        return Object.values(this.scores).reduce((sum, v) => sum + (v ?? 0), 0);
    },

    get scored() {
        return Object.values(this.scores).filter((v) => v !== null).length;
    },

    get complete() {
        return this.scored === this.rubric.criteria.length;
    },

    get percent() {
        return (this.total / this.rubric.max) * 100;
    },

    get band() {
        if (this.scored === 0) return null;
        return this.rubric.bands.find((b) => this.percent >= b.min) ?? this.rubric.bands.at(-1);
    },

    pct(field) {
        const c = this.criterion(field);
        return this.scores[field] === null ? 0 : (this.scores[field] / c.max) * 100;
    },

    criterion(field) {
        return this.rubric.criteria.find((c) => c.field === field);
    },

    level(field) {
        if (this.scores[field] === null) return 'Not scored';
        return this.rubric.levels.find((l) => this.pct(field) >= l.min)?.label ?? 'Weak';
    },

    // Points for the middle of a level, used when the reviewer taps a level word.
    pointsForLevel(field, index) {
        const c = this.criterion(field);
        const levels = [...this.rubric.levels].reverse(); // lowest first
        const from = levels[index].min;
        const to = levels[index + 1]?.min ?? 100;
        return Math.min(c.max, Math.round((((from + to) / 2) / 100) * c.max));
    },

    get weakest() {
        if (!this.complete) return null;
        return [...this.rubric.criteria].sort((a, b) => this.pct(a.field) - this.pct(b.field))[0];
    },

    // Technical sub-checklist: each check met is an equal share of the criterion.

    toggleCheck(key) {
        if (this.locked) return;
        this.checks = this.checks.includes(key) ? this.checks.filter((k) => k !== key) : [...this.checks, key];
    },

    get suggestion() {
        const c = this.criterion('score_technical');
        return c ? Math.round((this.checks.length / this.rubric.checks.length) * c.max) : null;
    },

    // Recommendation guidance

    fits(value) {
        if (!this.band) return false;
        return this.band.key === 'reject' ? value === 'reject' : value !== 'reject';
    },

    get nudge() {
        if (!this.band || !this.recommendation) return null;
        const accepting = this.recommendation !== 'reject';
        const band = `“${this.band.label.toLowerCase()}” band`;

        if (this.band.key === 'reject' && accepting) {
            return { tone: 'warning', text: `Your scores are in the ${band}. If you still recommend acceptance, tell the committee why in the confidential note.` };
        }
        if (this.band.key !== 'reject' && !accepting) {
            return { tone: 'warning', text: `Your scores are in the ${band}. If you still recommend rejection, tell the committee why in the confidential note.` };
        }
        if (this.band.key === 'revise') {
            return { tone: 'info', text: `Your scores are in the ${band}. List the changes the author must make in your comments.` };
        }
        return null;
    },

    get commentLength() {
        return this.comment.trim().length;
    },

    get ready() {
        return this.complete && this.recommendation !== '' && this.commentLength >= 20;
    },

    get missing() {
        const left = [];
        if (!this.complete) left.push(`${this.rubric.criteria.length - this.scored} more ${this.rubric.criteria.length - this.scored === 1 ? 'score' : 'scores'}`);
        if (!this.recommendation) left.push('a recommendation');
        if (this.commentLength < 20) left.push('comments for the author');
        return left.join(', ');
    },

    // Ring geometry: the arc is drawn on a circle of circumference 100.
    get dash() {
        return `${Math.max(0, Math.min(100, this.percent))} 100`;
    },

    // Animate the big number towards the new total.
    countTo(to) {
        const from = this.shownTotal;
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || from === to) {
            this.shownTotal = to;
            return;
        }
        const start = performance.now();
        const step = (now) => {
            const t = Math.min(1, (now - start) / 350);
            this.shownTotal = Math.round(from + (to - from) * (1 - Math.pow(1 - t, 3)));
            if (t < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    },

    // Drafts live in this browser only, until the review is submitted.

    saveDraft() {
        try {
            localStorage.setItem(this.draftKey, JSON.stringify({
                scores: this.scores, checks: this.checks, recommendation: this.recommendation, comment: this.comment, note: this.note,
            }));
        } catch (e) { /* storage unavailable: no draft */ }
    },

    restoreDraft() {
        try {
            const draft = JSON.parse(localStorage.getItem(this.draftKey) ?? 'null');
            if (!draft) return;
            for (const c of this.rubric.criteria) {
                const v = draft.scores?.[c.field];
                if (Number.isInteger(v) && v >= 0 && v <= c.max) this.scores[c.field] = v;
            }
            this.checks = (draft.checks ?? []).filter((k) => this.rubric.checks.includes(k));
            this.recommendation = draft.recommendation ?? '';
            this.comment = draft.comment ?? '';
            this.note = draft.note ?? '';
            this.restored = this.scored > 0 || this.comment !== '';
        } catch (e) { /* unreadable draft: start fresh */ }
    },

    discardDraft() {
        this.clearDraft();
        this.rubric.criteria.forEach((c) => (this.scores[c.field] = null));
        this.checks = [];
        this.recommendation = '';
        this.comment = '';
        this.note = '';
        this.restored = false;
    },

    clearDraft() {
        try {
            localStorage.removeItem(this.draftKey);
        } catch (e) { /* nothing to clear */ }
    },
});
