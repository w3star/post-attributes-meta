// blocks/pam-explorer/view.js
(function(){
  function ready(fn){ if(document.readyState!=='loading'){fn()} else { document.addEventListener('DOMContentLoaded', fn); } }

  function fmtDur(m){
    m = parseInt(m||0,10);
    if(m<=0) return '—';
    var h=(m/60)|0, r=m%60;
    if(h && r) return h+' h '+r+' min';
    if(h) return h+' h';
    return r+' min';
  }
  function diffLabel(i){
    i=parseInt(i||0,10);
    return i===1?'Leicht':(i===2?'Mittel':'Schwer');
  }

  ready(function(){

    // ===== Single-Slider (Rating / Beauty) =====
    document.querySelectorAll('[data-minmax-form]').forEach(function(form){
      ['min_rating','min_beauty'].forEach(function(name){
        var range = form.querySelector('input[name="'+name+'"]');
        if(!range) return;

        // Output möglichst nahe am Input suchen (innerhalb desselben <label>)
        var label = range.closest('label') || form;
        var out = label.querySelector('output[data-out="'+name+'"]') || form.querySelector('output[data-out="'+name+'"]');
        var update = function(){
          if(!out) return;
          out.textContent = String(range.value);
        };
        range.addEventListener('input', update);
        update();
      });
    });

    // ===== Dual-Slider (Schwierigkeit / Dauer) =====
    document.querySelectorAll('.pam-explorer--sliders [data-minmax-form] .minmax').forEach(function(wrap){
      // Inputs ermitteln: bevorzugt per Name, sonst per Reihenfolge
      var a = wrap.querySelector('input[name="diff_from_i"], input[name="dur_from"]') || wrap.querySelector('input[type="range"]:nth-of-type(1)'); // von
      var b = wrap.querySelector('input[name="diff_to_i"],   input[name="dur_to"]')   || wrap.querySelector('input[type="range"]:nth-of-type(2)'); // bis
      if(!a || !b) return;

      // Track/Range-Balken
      var track   = wrap.querySelector('.track');
      var rangeEl = track ? track.querySelector('.range') : null;

      // Outputs direkt unterhalb der jeweiligen Gruppe (im selben <label>)
      var labelContainer = wrap.closest('label') || wrap.parentElement || document;
      var outA = labelContainer.querySelector('output[data-out="'+(a.name||'')+'"]');
      var outB = labelContainer.querySelector('output[data-out="'+(b.name||'')+'"]');

      // Wertebereich
      var min = parseInt(a.min,10), max = parseInt(a.max,10);
      var active = 'b'; // Standard: "bis" liegt oben

      function setActive(which){
        active = which;
        if(which==='a'){ a.style.zIndex=3; b.style.zIndex=2; }
        else           { b.style.zIndex=3; a.style.zIndex=2; }
      }

      function display(val, inputName){
        if(!inputName) return String(val);
        if(inputName.indexOf('dur_')===0)  return fmtDur(val);
        if(inputName.indexOf('diff_')===0) return diffLabel(val);
        return String(val);
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

        if(outA) outA.textContent = display(va, a.name||'');
        if(outB) outB.textContent = display(vb, b.name||'');
      }

      function clampActive(){
        var va = parseInt(a.value,10);
        var vb = parseInt(b.value,10);
        // Kein Swap: nur den aktiven Griff begrenzen
        if (active==='a' && va>vb){ a.value = vb; }
        if (active==='b' && vb<va){ b.value = va; }
        draw();
      }

      // Vor dem nativen Drag aktiven Griff festlegen
      wrap.addEventListener('pointerdown', function(ev){
        if (ev.target === a) { setActive('a'); }
        else if (ev.target === b) { setActive('b'); }
        else if (track) {
          var rect = track.getBoundingClientRect();
          var pct  = (ev.clientX - rect.left) / rect.width;
          pct = Math.min(1, Math.max(0, pct));
          var val  = min + Math.round(pct * (max - min));
          var da   = Math.abs(val - parseInt(a.value,10));
          var db   = Math.abs(val - parseInt(b.value,10));
          setActive( da <= db ? 'a' : 'b' );
        }
      }, true); // capture = vor nativer Drag-Logik

      // Tastatur-Fokus
      a.addEventListener('focusin', function(){ setActive('a'); });
      b.addEventListener('focusin', function(){ setActive('b'); });

      // Ziehen/Eingabe
      a.addEventListener('input', function(){ setActive('a'); clampActive(); });
      b.addEventListener('input', function(){ setActive('b'); clampActive(); });

      // Initial (von <= bis) & Zeichnen
      if (parseInt(a.value,10) > parseInt(b.value,10)) { a.value = b.value; }
      setActive('b');
      draw();
    });

  });
})();
