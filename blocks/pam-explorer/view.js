// blocks/pam-explorer/view.js — stabile Wrapper-Drag-Variante (ein Skript, kein Umschalten in input)
(function () {
  function ready(fn){ if(document.readyState!=='loading'){fn()} else { document.addEventListener('DOMContentLoaded', fn); } }

  function fmtDur(m){ m=parseInt(m||0,10); if(m<=0) return '—'; var h=(m/60)|0, r=m%60; if(h&&r) return h+' h '+r+' min'; if(h) return h+' h'; return r+' min'; }
  function diffLabel(i){ i=parseInt(i||0,10); return i===1?'Leicht':(i===2?'Mittel':'Schwer'); }

  ready(function () {
    const forms = document.querySelectorAll('.pam-explorer--sliders [data-minmax-form]');

    // Single-Slider (Rating/Beauty)
    forms.forEach(function(form){
      ['min_rating','min_beauty'].forEach(function(name){
        const range=form.querySelector('input[name="'+name+'"]');
        const out=(range && (range.closest('label')||form).querySelector('output[data-out="'+name+'"]'))||null;
        if(!range||!out) return;
        const update=()=>{ out.textContent=String(range.value); };
        range.addEventListener('input', update); update();
      });
    });

    // Dual-Slider (Schwierigkeit/Dauer)
    forms.forEach(function(form){
      form.querySelectorAll('.minmax').forEach(function(wrap){
        // Inputs finden (per Name, sonst Reihenfolge)
        const a = wrap.querySelector('input[name="diff_from_i"], input[name="dur_from"]') || wrap.querySelector('input[type="range"]:nth-of-type(1)'); // von
        const b = wrap.querySelector('input[name="diff_to_i"],   input[name="dur_to"]')   || wrap.querySelector('input[type="range"]:nth-of-type(2)'); // bis
        if(!a || !b) return;

        const track   = wrap.querySelector('.track');
        const rangeEl = track ? track.querySelector('.range') : null;
        const label   = wrap.closest('label') || form;
        const outA    = label.querySelector('output[data-out="'+(a.name||'')+'"]');
        const outB    = label.querySelector('output[data-out="'+(b.name||'')+'"]');

        const min = parseInt(a.min,10), max = parseInt(a.max,10);

        let active = 'b';     // 'a'|'b' – wird beim Drag-Start anhand der Mausposition bestimmt
        let dragging = false;
        let rect = null;

        function setActive(which){
          active = which;
          if(which==='a'){ a.style.zIndex=3; b.style.zIndex=2; }
          else           { b.style.zIndex=3; a.style.zIndex=2; }
        }

        function valueToPct(val){ return ((val - min) / (max - min)) * 100; }
        function pctToValue(pct){ return min + Math.round(pct * (max - min)); }

        function draw(){
          const va=parseInt(a.value,10), vb=parseInt(b.value,10);
          const pctA=valueToPct(va), pctB=valueToPct(vb);
          if (rangeEl) { rangeEl.style.left=pctA+'%'; rangeEl.style.right=(100-pctB)+'%'; }
          else if (track) {
            track.style.background =
              'linear-gradient(to right,#e5e5e5 '+pctA+'%,#111 '+pctA+'%,#111 '+pctB+'%,#e5e5e5 '+pctB+'%)';
          }
          if(outA){
            outA.textContent = a.name.indexOf('dur_')===0 ? fmtDur(va) :
                               a.name.indexOf('diff_')===0 ? diffLabel(va) : String(va);
          }
          if(outB){
            outB.textContent = b.name.indexOf('dur_')===0 ? fmtDur(vb) :
                               b.name.indexOf('diff_')===0 ? diffLabel(vb) : String(vb);
          }
        }

        function startDrag(ev){
          dragging = true;
          rect = (track || wrap).getBoundingClientRect();

          // immer: aktiven Griff anhand der aktuellen Mausposition wählen (näher dran)
          const pct = Math.min(1, Math.max(0, (ev.clientX - rect.left) / rect.width));
          const val = pctToValue(pct);
          const da  = Math.abs(val - parseInt(a.value,10));
          const db  = Math.abs(val - parseInt(b.value,10));
          setActive( da <= db ? 'a' : 'b' );

          // Inputs während Drag durchlässig – wir steuern nur noch per Wrapper
          a.style.pointerEvents='none';
          b.style.pointerEvents='none';

          moveDrag(ev);
          window.addEventListener('pointermove', moveDrag, true);
          window.addEventListener('pointerup',   endDrag,  true);
          ev.preventDefault();
        }

        function moveDrag(ev){
          if(!dragging || !rect) return;
          const pct = Math.min(1, Math.max(0, (ev.clientX - rect.left) / rect.width));
          const val = pctToValue(pct);
          if (active==='a')     a.value = Math.min(val, parseInt(b.value,10));
          else /* active==='b'*/ b.value = Math.max(val, parseInt(a.value,10));
          draw();
        }

        function endDrag(){
          dragging = false;
          a.style.pointerEvents='auto';
          b.style.pointerEvents='auto';
          window.removeEventListener('pointermove', moveDrag, true);
          window.removeEventListener('pointerup',   endDrag,  true);
        }

        // Events
        wrap.addEventListener('pointerdown', startDrag, true);

        // Tastatur (nativer Input erlaubt)
        a.addEventListener('focusin', function(){ setActive('a'); });
        b.addEventListener('focusin', function(){ setActive('b'); });
        a.addEventListener('input',   function(){ /* NICHT setActive – nur zeichnen/clampen */ draw(); });
        b.addEventListener('input',   function(){ draw(); });

        // Initial (von <= bis) & Zeichnen
        if (parseInt(a.value,10) > parseInt(b.value,10)) a.value = b.value;
        setActive('b');
        draw();
      });
    });
  });
})();
