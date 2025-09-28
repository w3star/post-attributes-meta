(function () {
    function clamp(n, min, max) { n = +n; return isNaN(n) ? min : Math.min(Math.max(n, min), max); }

    function updateTrackFor(group) {
        const a = group.querySelector('input[type="range"]:first-of-type');
        const b = group.querySelector('input[type="range"]:nth-of-type(2)');
        const bar = group.querySelector('.track .range');
        if (!a || !b || !bar) return;

        const min = ('min' in a) ? +a.min : 0;
        const max = ('max' in a) ? +a.max : 100;

        const va = clamp(a.value, min, max);
        const vb = clamp(b.value, min, max);

        const leftPct = ((Math.min(va, vb) - min) / (max - min)) * 100;
        const rightPct = 100 - ((Math.max(va, vb) - min) / (max - min)) * 100;

        bar.style.left = leftPct + '%';
        bar.style.right = rightPct + '%';
    }

    function initForm(form) {
        // NIE serverseitige Werte überschreiben – nur auslesen & spiegeln
        const ranges = form.querySelectorAll('input[type="range"]');

        ranges.forEach((r) => {
            const out = form.querySelector(`output[data-out="${r.name}"]`);
            if (out) out.textContent = r.value; // vorhandenen Wert spiegeln

            const group = r.closest('.minmax');
            if (group) updateTrackFor(group);
        });

        form.addEventListener('input', (e) => {
            const r = e.target;
            if (!(r instanceof HTMLInputElement) || r.type !== 'range') return;

            const out = form.querySelector(`output[data-out="${r.name}"]`);
            if (out) out.textContent = r.value;

            const group = r.closest('.minmax');
            if (group) updateTrackFor(group);
        });
    }

    function ready(fn) {
        if (document.readyState === 'complete' || document.readyState === 'interactive') fn();
        else document.addEventListener('DOMContentLoaded', fn, { once: true });
    }

    ready(function () {
        // deine Form hat data-minmax-form (laut aktuellem Markup)
        const form = document.querySelector('[data-minmax-form]');
        if (form) initForm(form);
    });
})();
