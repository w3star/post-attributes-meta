document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('.pam-explorer .minmax').forEach(wrap=>{
    const a=wrap.querySelector('input.min');
    const b=wrap.querySelector('input.max');
    const track=wrap.querySelector('.track');
    let active='a';

    function setActive(which){
      active=which;
      if(which==='a'){a.style.zIndex=3;b.style.zIndex=2;}
      else{b.style.zIndex=3;a.style.zIndex=2;}
    }

    wrap.addEventListener('pointerdown',ev=>{
      if(ev.target===a){setActive('a');return;}
      if(ev.target===b){setActive('b');return;}
      if(track){
        const rect=track.getBoundingClientRect();
        let val=(ev.clientX-rect.left)/rect.width*(b.max-b.min)+parseInt(b.min);
        const da=Math.abs(val-parseInt(a.value,10));
        const db=Math.abs(val-parseInt(b.value,10));
        setActive(da<=db?'a':'b');
      }
    },true);

    function clamp(){
      let av=parseInt(a.value,10), bv=parseInt(b.value,10);
      if(av>bv){ if(active==='a'){a.value=bv;} else{b.value=av;} }
    }

    function draw(){
      clamp();
      const min=parseInt(a.min), max=parseInt(a.max);
      const av=parseInt(a.value), bv=parseInt(b.value);
      const p1=(av-min)/(max-min)*100, p2=(bv-min)/(max-min)*100;
      if(track)track.style.background=`linear-gradient(to right,#ddd ${p1}%,#000 ${p1}%,#000 ${p2}%,#ddd ${p2}%)`;
    }

    a.addEventListener('input',()=>{setActive('a');draw();});
    b.addEventListener('input',()=>{setActive('b');draw();});
    draw();
  });
});
