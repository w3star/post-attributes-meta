// blocks/pam-explorer/view.js — wrapper-gesteuerter Dual-Drag (kein nativer Thumb-Drag nötig)
(function () {
  function ready(fn){ if(document.readyState!=='loading'){fn()} else { document.addEventListener('DOMContentLoaded', fn); } }

  function fmtDur(m){ m=parseInt(m||0,10); if(m<=0) return '—'; var h=(m/60)|0, r=m%60; if(h&&r) return h+' h '+r+' min'; if(h) return h+' h'; return r+' min'; }
  function diffLabel(i){ i=parseInt(i||0,10); return i===1?'Leicht':(i===2?'Mittel':'Schwer'); }

  ready(function () {
    const forms = document.querySelectorAll('.pam-explorer--sliders [data-minmax-form]');

    // Single-Slider (Rating / Beauty)
    forms.forEach(function(form){
      ['min_rating','min_beauty'].forEach(function(name){
        const range = form.querySelector('input[name="'+name+'"]');
        const out = form.querySelector('output[data-out="'+name+'"]');
        if(!range || !out) return;
        const update = ()=>{ out.textContent = String(range.value); };
        range.addEventListener('input', update);
        update();
      });
    });

    // Dual-Slider (Schwierigkeit / Dauer)
    forms.forEach(function(form){
      form.querySelectorAll('.minmax').forEach(function(wrap){
        // Inputs robust ermitteln (per Namen, sonst per Reihenfolge)
        const a = wrap.querySelector('input[name="diff_from_i"], input[name="dur_from"]') || wrap.querySelector('input[type="range"]:nth-of-type(1)'); // von
        const b = wrap.querySelector('input[name="diff_to_i"],   input[name="dur_to"]')   || wrap.querySelector('input[type="range"]:nth-of-type(2)'); // bis
        if(!a || !b) return;

        const track   = wrap.querySelector('.track');
        const rangeEl = track ? track.querySelector('.range') : null;

        const label = wrap.closest('label') || form;
        const outA = label.querySelector('output[data-out="'+(a.name||'')+'"]');
        const outB = label.querySelector('output[data-out="'+(b.name||'')+'"]');

        const min = parseInt(a.min,10);
        const max = parseInt(a.max,10);

        let active = 'b';           // aktuell aktiver Griff: 'a' | 'b'
        let dragging = false;       // ob wrapper-Drag läuft
        let rect = null;            // Track-Rect für Umrechnung x->Wert

        function setActive(which){
          active = which;
          if(which==='a'){ a.style.zIndex=3; b.style.zIndex=2; }
          else           { b.style.zIndex=3; a.style.zIndex=2; }
        }

        function valueToPct(val){ return ((val - min) / (max - min)) * 100; }
        function pctToValue(pct){ return min + Math.round(pct * (max - min)); }

        function draw(){
          const va = parseInt(a.value,10);
          const vb = parseInt(b.value,10);
          const pctA = valueToPct(va);
          const pctB = valueToPct(vb);

          if (rangeEl) {
            rangeEl.style.left  = pctA + '%';
            rangeEl.style.right = (100 - pctB) + '%';
          } else if (track) {
            track.style.background =
              'linear-gradient(to right,#e5e5e5 '+pctA+'%,#111 '+pctA+'%,#111 '+pctB+'%,#e5e5e5 '+pctB+'%)';
          }
          if(outA){
            outA.textContent = (a.name && a.name.indexOf('dur_')===0) ? fmtDur(va)
                              : (a.name && a.name.indexOf('diff_')===0) ? diffLabel(va)
                              : String(va);
          }
          if(outB){
            outB.textContent = (b.name && b.name.indexOf('dur_')===0) ? fmtDur(vb)
                              : (b.name && b.name.indexOf('diff_')===0) ? diffLabel(vb)
                              : String(vb);
          }
        }

        function clampActive(){
          let va = parseInt(a.value,10);
          let vb = parseInt(b.value,10);
          if (active==='a' && va>vb){ a.value = vb; }
          if (active==='b' && vb<va){ b.value = va; }
          draw();
        }

        // ===== Wrapper-gesteuerter Drag (umgeht „oberer Input frisst Events“) =====
        function startDrag(ev){
          dragging = true;
          rect = (track || wrap).getBoundingClientRect();

          // Aktiven Griff wählen:
          if (ev.target === a) setActive('a');
          else if (ev.target === b) setActive('b');
          else {
            // Nähesten Griff anhand x-Position bestimmen
            const pct = Math.min(1, Math.max(0, (ev.clientX - rect.left) / rect.width));
            const val = pctToValue(pct);
            const da = Math.abs(val - parseInt(a.value,10));
            const db = Math.abs(val - parseInt(b.value,10));
            setActive( da <= db ? 'a' : 'b' );
          }

          // Während des Drags sind beide Inputs „durchlässig“ – wir steuern komplett per wrapper
          a.style.pointerEvents = 'none';
          b.style.pointerEvents = 'none';

          moveDrag(ev);
          window.addEventListener('pointermove', moveDrag, true);
          window.addEventListener('pointerup',   endDrag,  true);
          ev.preventDefault();
        }

        function moveDrag(ev){
          if(!dragging || !rect) return;
          const pct = Math.min(1, Math.max(0, (ev.clientX - rect.left) / rect.width));
          const val = pctToValue(pct);
          if (active==='a'){
            a.value = Math.min(val, parseInt(b.value,10));
          } else {
            b.value = Math.max(val, parseInt(a.value,10));
          }
          draw();
        }

        function endDrag(){
          dragging = false;
          a.style.pointerEvents = 'auto';
          b.style.pointerEvents = 'auto';
          window.removeEventListener('pointermove', moveDrag, true);
          window.removeEventListener('pointerup',   endDrag,  true);
        }

        // Events
        wrap.addEventListener('pointerdown', startDrag, true);

        // Tastatur: nativer Input funktioniert weiterhin
        a.addEventListener('focusin', function(){ setActive('a'); });
        b.addEventListener('focusin', function(){ setActive('b'); });
        a.addEventListener('input', function(){ setActive('a'); clampActive(); });
        b.addEventListener('input', function(){ setActive('b'); clampActive(); });

        // Initial ausrichten
        if (parseInt(a.value,10) > parseInt(b.value,10)) a.value = b.value;
        setActive('b');
        draw();
      });
    });
  });
})();
