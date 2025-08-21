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

      form.querySelectorAll('.minmax').forEach(function(wrap){
        // Robuste Zuordnung per Name (falls vorhanden), sonst per Reihenfolge:
        var a = wrap.querySelector('input[name="diff_from_i"], input[name="dur_from"]') || wrap.querySelector('input[type="range"]:nth-of-type(1)'); // von
        var b = wrap.querySelector('input[name="diff_to_i"],   input[name="dur_to"]')   || wrap.querySelector('input[type="range"]:nth-of-type(2)'); // bis
        if(!a || !b) return;

        var track   = wrap.querySelector('.track');
        var rangeEl = track ? track.querySelector('.range') : null;

        var min = parseInt(a.min,10), max = parseInt(a.max,10);
        var active = 'b'; // Standard: bis oben
        function setActive(which){
          active = which;
          if(which==='a'){ a.style.zIndex=3; b.style.zIndex=2; }
          else           { b.style.zIndex=3; a.style.zIndex=2; }
        }

        function draw(){
          var va = parseInt(a.value,10);
          var vb = parseInt(b.value,10);
          var pctA = ((va - min) / (max - min)) * 100;
          var pctB = ((vb - min) / (max - min)) * 100;

          if (rangeEl) {
            rangeEl.style.left  = pctA + '%';
            rangeEl.style.right = (100 - pctB) + '%';
          } else if (track) {
            track.style.background =
              'linear-gradient(to right,#e5e5e5 '+pctA+'%,#111 '+pctA+'%,#111 '+pctB+'%,#e5e5e5 '+pctB+'%)';
          }
        }

        function clampActive(){
          var va = parseInt(a.value,10);
          var vb = parseInt(b.value,10);
          if (active==='a' && va>vb){ a.value = vb; }
          if (active==='b' && vb<va){ b.value = va; }
          draw();
        }

        // Aktiven Griff vor dem nativen Drag festlegen
        wrap.addEventListener('pointerdown', function(ev){
          if (ev.target === a) { setActive('a'); }
          else if (ev.target === b) { setActive('b'); }
          else if (track) {
            var rect=track.getBoundingClientRect();
            var pct=(ev.clientX - rect.left)/rect.width; pct=Math.min(1, Math.max(0, pct));
            var val=min + Math.round(pct*(max-min));
            var da=Math.abs(val - parseInt(a.value,10));
            var db=Math.abs(val - parseInt(b.value,10));
            setActive( da <= db ? 'a' : 'b' );
          }
        }, true);

        // Tastatur-Fokus
        a.addEventListener('focusin', function(){ setActive('a'); });
        b.addEventListener('focusin', function(){ setActive('b'); });

        // Ziehen/Eingabe
        a.addEventListener('input', function(){ setActive('a'); clampActive(); });
        b.addEventListener('input', function(){ setActive('b'); clampActive(); });

        // Initial (von <= bis)
        if (parseInt(a.value,10) > parseInt(b.value,10)) { a.value = b.value; }
        setActive('b');
        draw();
      });

        // Pointer-Logik: aktiven Griff setzen, bevor der Browser draggt
        wrap.addEventListener('pointerdown', function(ev){
          // 1) Aktiven Griff bestimmen (direkt getroffen? sonst: näherer)
          if (ev.target === a) { setActive('a'); }
          else if (ev.target === b) { setActive('b'); }
          else if (track) {
            var rect=track.getBoundingClientRect();
            var pct=(ev.clientX - rect.left)/rect.width; pct=Math.min(1, Math.max(0, pct));
            var val=min + Math.round(pct*(max-min));
            var da=Math.abs(val - parseInt(a.value,10));
            var db=Math.abs(val - parseInt(b.value,10));
            setActive( da <= db ? 'a' : 'b' );
          }

          // 2) Nicht-aktiven Griff für die Drag-Phase „durchlässig“ machen
          if (active === 'a') { b.classList.add('pe-none'); a.classList.remove('pe-none'); }
          else { a.classList.add('pe-none'); b.classList.remove('pe-none'); }

          // 3) Beim Loslassen wieder herstellen
          var up = function(){
            a.classList.remove('pe-none'); b.classList.remove('pe-none');
            window.removeEventListener('pointerup', up, true);
          };
          window.addEventListener('pointerup', up, true);
        }, true); // capture = vor nativem Drag aktivieren


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
