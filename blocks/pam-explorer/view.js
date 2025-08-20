
(function(){
function ready(fn){ if(document.readyState!=='loading'){fn()} else { document.addEventListener('DOMContentLoaded', fn); } }
function fmtDur(m){ m=parseInt(m||0,10); if(m<=0) return '—'; var h=(m/60)|0, r=m%60; if(h&&r) return h+' h '+r+' min'; if(h) return h+' h'; return r+' min'; }
function diffLabel(i){ i=parseInt(i,10); return i===1?'Leicht':(i===2?'Mittel':'Schwer'); }

ready(function(){
  document.querySelectorAll('.pam-explorer--sliders [data-minmax-form]').forEach(function(form){
    // Single sliders outputs
    ['min_rating','min_beauty'].forEach(function(name){
      var range=form.querySelector('input[name="'+name+'"]');
      var out=form.querySelector('output[data-out="'+name+'"]');
      if(range&&out){ range.addEventListener('input', function(){ out.textContent = range.value; }); }
    });

    // Dual sliders
    form.querySelectorAll('.minmax').forEach(function (wrap) {
      // "von" ist der 1. Range, "bis" der 2. Range (wie im HTML des Baselines)
      var a = wrap.querySelector('input[type="range"]:nth-of-type(1)'); // von
      var b = wrap.querySelector('input[type="range"]:nth-of-type(2)'); // bis
      if (!a || !b) return;

      var track   = wrap.querySelector('.track');
      var rangeEl = track ? track.querySelector('.range') : null;
      var min     = parseInt(a.min,10);
      var max     = parseInt(a.max,10);
      var active  = 'b'; // bis-Handle oben, damit "von" nicht verdeckt ist

      function setActive(which){
        active = which;
        if (which === 'a') { a.style.zIndex = 3; b.style.zIndex = 2; }
        else               { b.style.zIndex = 3; a.style.zIndex = 2; }
      }

      function draw(){
        var va = parseInt(a.value,10);
        var vb = parseInt(b.value,10);
        if (rangeEl) {
          var pctA = ((va - min) / (max - min)) * 100;
          var pctB = ((vb - min) / (max - min)) * 100;
          rangeEl.style.left  = pctA + '%';
          rangeEl.style.right = (100 - pctB) + '%';
        }
      }

      function clampActive(){
        var va = parseInt(a.value,10);
        var vb = parseInt(b.value,10);
        // WICHTIG: Kein Swap. Nur den AKTIVEN Griff begrenzen:
        if (active === 'a' && va > vb) { a.value = vb; }
        if (active === 'b' && vb < va) { b.value = va; }
        draw();
      }

      // 1) Wenn direkt auf einen Griff geklickt wird → genau dieser wird aktiv.
      wrap.addEventListener('pointerdown', function(ev){
        if (ev.target === a) { setActive('a'); return; }
        if (ev.target === b) { setActive('b'); return; }

        // 2) Klick auf die Leiste: wähle den NÄHEREN Griff
        if (track) {
          var rect = track.getBoundingClientRect();
          var pct  = (ev.clientX - rect.left) / rect.width;
          pct = Math.min(1, Math.max(0, pct));
          var val  = min + Math.round(pct * (max - min));
          var da   = Math.abs(val - parseInt(a.value,10));
          var db   = Math.abs(val - parseInt(b.value,10));
          setActive(da <= db ? 'a' : 'b');
        }
      }, true); // capture, damit der aktive Griff feststeht bevor der Browser draggt

      // Tastatur: Fokus setzt aktiven Griff
      a.addEventListener('focusin', function(){ setActive('a'); });
      b.addEventListener('focusin', function(){ setActive('b'); });

      // Ziehen/Eingabe
      a.addEventListener('input', function(){ setActive('a'); clampActive(); });
      b.addEventListener('input', function(){ setActive('b'); clampActive(); });

      // Initiale Korrektur (von <= bis) & Zeichnen
      if (parseInt(a.value,10) > parseInt(b.value,10)) { a.value = b.value; }
      setActive('b');
      draw();
    });

  });
});
})();
