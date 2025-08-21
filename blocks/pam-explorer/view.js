// blocks/pam-explorer/view.js
(function () {
  function ready(fn) { if (document.readyState !== 'loading') { fn() } else { document.addEventListener('DOMContentLoaded', fn); } }
  function fmtDur(m) { m = parseInt(m || 0, 10); if (m <= 0) return '—'; var h = (m / 60) | 0, r = m % 60; if (h && r) return h + ' h ' + r + ' min'; if (h) return h + ' h'; return r + ' min'; }
  function diffLabel(i) { i = parseInt(i, 10); return i === 1 ? 'Leicht' : (i === 2 ? 'Mittel' : 'Schwer'); }

  ready(function () {
    document.querySelectorAll('.pam-explorer--sliders [data-minmax-form]').forEach(function (form) {

      // Einzelslider-Ausgaben (optional)
      ['min_rating', 'min_beauty'].forEach(function (name) {
        var range = form.querySelector('input[name="' + name + '"]');
        var out = form.querySelector('output[data-out="' + name + '"]');
        if (range && out) { range.addEventListener('input', function () { out.textContent = range.value; }); }
      });

      form.querySelectorAll('.minmax').forEach(function (wrap) {
        // Wir gehen davon aus: 1. input = von, 2. input = bis
        var a = wrap.querySelector('input[type="range"]:nth-of-type(1)'); // von
        var b = wrap.querySelector('input[type="range"]:nth-of-type(2)'); // bis
        if (!a || !b) return;

        var track = wrap.querySelector('.track');
        var rangeEl = track ? track.querySelector('.range') : null;

        var outA = form.querySelector('output[data-out="' + (a.name || '') + '"]');
        var outB = form.querySelector('output[data-out="' + (b.name || '') + '"]');

        var min = parseInt(a.min, 10), max = parseInt(a.max, 10);
        var active = 'b'; // bis liegt oben

        function setActive(which) {
          active = which;
          if (which === 'a') { a.style.zIndex = 3; b.style.zIndex = 2; }
          else { b.style.zIndex = 3; a.style.zIndex = 2; }
        }

        function display(val, inputName) {
          if (!inputName) return String(val);
          if (inputName.indexOf('dur_') === 0) return fmtDur(val);
          if (inputName.indexOf('diff_') === 0) return diffLabel(val);
          return String(val);
        }

        function clampActive() {
          var va = parseInt(a.value, 10);
          var vb = parseInt(b.value, 10);
          if (active === 'a' && va > vb) { a.value = vb; }
          if (active === 'b' && vb < va) { b.value = va; }
        }

        function draw() {
          var va = parseInt(a.value, 10);
          var vb = parseInt(b.value, 10);

          // Prozentpositionen
          var pctA = ((va - min) / (max - min)) * 100;
          var pctB = ((vb - min) / (max - min)) * 100;

          // Sichtbarer Bereich in der Bahn
          if (rangeEl) {
            rangeEl.style.left = pctA + '%';
            rangeEl.style.right = (100 - pctB) + '%';
          } else if (track) {
            track.style.background =
              'linear-gradient(to right,#e5e5e5 ' + pctA + '%,#111 ' + pctA + '%,#111 ' + pctB + '%,#e5e5e5 ' + pctB + '%)';
          }

          // 🔑 Hier passiert die Magie:
          //   - von-Input klickbar nur LINKS bis zum eigenen Griff
          //   - bis-Input klickbar nur RECHTS ab eigenem Griff
          a.style.left = '0%';
          a.style.right = (100 - pctA) + '%'; // begrenzt die Breite nach rechts

          b.style.left = pctB + '%';         // begrenzt die Breite nach links
          b.style.right = '0%';

          if (outA) outA.textContent = display(va, a.name);
          if (outB) outB.textContent = display(vb, b.name);
        }

        // Pointer-Logik: aktiven Griff setzen, bevor der Browser draggt
        wrap.addEventListener('pointerdown', function (ev) {
          if (ev.target === a) { setActive('a'); return; }
          if (ev.target === b) { setActive('b'); return; }
          // Klick auf Track: näheren Griff aktivieren
          if (track) {
            var rect = track.getBoundingClientRect();
            var pct = (ev.clientX - rect.left) / rect.width; pct = Math.min(1, Math.max(0, pct));
            var val = min + Math.round(pct * (max - min));
            var da = Math.abs(val - parseInt(a.value, 10));
            var db = Math.abs(val - parseInt(b.value, 10));
            setActive(da <= db ? 'a' : 'b');
          }
        }, true);

        // Fokus per Tastatur
        a.addEventListener('focusin', function () { setActive('a'); });
        b.addEventListener('focusin', function () { setActive('b'); });

        // Ziehen / Eingabe
        a.addEventListener('input', function () { setActive('a'); clampActive(); draw(); });
        b.addEventListener('input', function () { setActive('b'); clampActive(); draw(); });

        // Initial
        if (parseInt(a.value, 10) > parseInt(b.value, 10)) { a.value = b.value; }
        setActive('b');
        draw();
      });
    });
  });
})();
