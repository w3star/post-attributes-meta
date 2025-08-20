
(function(){
  function ready(fn){ if(document.readyState!=='loading'){fn()} else { document.addEventListener('DOMContentLoaded', fn); } }
  function fmtDur(m){ m=parseInt(m||0,10); if(m<=0) return '—'; var h=(m/60)|0, r=m%60; if(h&&r) return h+' h '+r+' min'; if(h) return h+' h'; return r+' min'; }
  function diffLabel(i){ i=parseInt(i,10); return i===1?'Leicht':(i===2?'Mittel':'Schwer'); }

  ready(function(){
    document.querySelectorAll('.pam-explorer--sliders [data-minmax-form]').forEach(function(form){

      // Einzel-Slider Outputs (Rating/Beauty)
      ['min_rating','min_beauty'].forEach(function(name){
        var range=form.querySelector('input[name="'+name+'"]');
        var out=form.querySelector('output[data-out="'+name+'"]');
        if(range&&out){ range.addEventListener('input', function(){ out.textContent = range.value; }); }
      });

      // Dual-Slider Wrapper
      form.querySelectorAll('.minmax').forEach(function(wrap){
        // Inputs per Namen auflösen (robust gegen Klassen-Unterschiede)
        var a = wrap.querySelector('input[name="diff_from_i"], input[name="dur_from"]') || wrap.querySelector('input[type="range"]:nth-of-type(1)');
        var b = wrap.querySelector('input[name="diff_to_i"],   input[name="dur_to"]')   || wrap.querySelector('input[type="range"]:nth-of-type(2)');

        if(!a || !b) return;

        var track = wrap.querySelector('.track');
        var rangeEl = track ? track.querySelector('.range') : null;

        var outA = form.querySelector('output[data-out="'+(a.name||'')+'"]');
        var outB = form.querySelector('output[data-out="'+(b.name||'')+'"]');

        var min = parseInt(a.min,10), max = parseInt(a.max,10);
        var active = 'b'; // 'b' oben, damit 'a' nicht verdeckt wird

        function setActive(which){
          active = which;
          if(which==='a'){ a.style.zIndex=3; b.style.zIndex=2; }
          else { b.style.zIndex=3; a.style.zIndex=2; }
        }

        function display(val, inputName){
          if(!inputName) return String(val);
          if(inputName.indexOf('dur_')===0) return fmtDur(val);
          if(inputName.indexOf('diff_')===0) return diffLabel(parseInt(val,10));
          return String(val);
        }

        function draw(){
          var va=parseInt(a.value,10), vb=parseInt(b.value,10);
          if(rangeEl){
            var pctA=((va-min)/(max-min))*100, pctB=((vb-min)/(max-min))*100;
            rangeEl.style.left = pctA+'%';
            rangeEl.style.right = (100-pctB)+'%';
          } else if (track) {
            // Fallback: einfärben der Track-Bar
            var pctA=((va-min)/(max-min))*100, pctB=((vb-min)/(max-min))*100;
            track.style.background = 'linear-gradient(to right,#e5e5e5 '+pctA+'%,#111 '+pctA+'%,#111 '+pctB+'%,#e5e5e5 '+pctB+'%)';
          }
          if(outA) outA.textContent = display(va, a.name);
          if(outB) outB.textContent = display(vb, b.name);
        }

        function clampMove(){
          var va=parseInt(a.value,10), vb=parseInt(b.value,10);
          if(active==='a' && va>vb){ a.value = vb; va = vb; }
          if(active==='b' && vb<va){ b.value = va; vb = va; }
          draw();
        }

        // WICHTIG: Zuerst prüfen, ob direkt ein Griff angeklickt wurde.
        wrap.addEventListener('pointerdown', function(ev){
          if (ev.target === a) { setActive('a'); return; }
          if (ev.target === b) { setActive('b'); return; }
          if (!track) return;
          var rect=track.getBoundingClientRect();
          var pct=(ev.clientX - rect.left)/rect.width; pct=Math.min(1, Math.max(0, pct));
          var val=min + Math.round(pct*(max-min));
          var da=Math.abs(val - parseInt(a.value,10));
          var db=Math.abs(val - parseInt(b.value,10));
          setActive( da <= db ? 'a' : 'b' );
        }, true);

        // Tastatur-Fokus
        a.addEventListener('focusin', function(){ setActive('a'); });
        b.addEventListener('focusin', function(){ setActive('b'); });

        // Bewegungen
        a.addEventListener('input', function(){ setActive('a'); clampMove(); });
        b.addEventListener('input', function(){ setActive('b'); clampMove(); });

        // Initial
        var va0=parseInt(a.value,10), vb0=parseInt(b.value,10);
        if(va0>vb0){ a.value = vb0; }
        setActive('b');
        draw();
      });
    });
  });
})();
