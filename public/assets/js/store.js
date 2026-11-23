'use strict';
(() => {
 const toggle=document.querySelector('.menu-toggle'),nav=document.querySelector('#main-nav');
 if(toggle&&nav){toggle.addEventListener('click',()=>{const open=nav.classList.toggle('open');toggle.setAttribute('aria-expanded',String(open));});nav.querySelectorAll('a').forEach(a=>a.addEventListener('click',()=>{nav.classList.remove('open');toggle.setAttribute('aria-expanded','false');}));}
 const slides=[...document.querySelectorAll('.hero-slide')],dots=[...document.querySelectorAll('[data-slide-to]')];let current=0,timer=null;
 const show=(index)=>{if(!slides.length)return;current=(index+slides.length)%slides.length;slides.forEach((s,i)=>{s.classList.toggle('is-active',i===current);s.setAttribute('aria-hidden',String(i!==current));});dots.forEach((d,i)=>d.classList.toggle('active',i===current));};
 const start=()=>{if(slides.length>1){clearInterval(timer);timer=setInterval(()=>show(current+1),3000);}};
 dots.forEach(d=>d.addEventListener('click',()=>{show(Number(d.dataset.slideTo));start();}));
 document.querySelector('.hero-next')?.addEventListener('click',()=>{show(current+1);start();});
 document.querySelector('.hero-prev')?.addEventListener('click',()=>{show(current-1);start();});start();
 const carousel=document.querySelector('[data-category-carousel]');
 if(carousel){
  const cards=[...carousel.querySelectorAll('[data-category-card]')];
  if(cards.length){
   let active=Math.max(0,Math.min(cards.length-1,Number(carousel.dataset.selectedIndex||0)));
   const mobile=window.matchMedia('(max-width: 640px)');
   const paint=()=>{
    cards.forEach((card,i)=>{
     const raw=i-active;
     const delta=raw===0?0:(Math.abs(raw)>cards.length/2?raw-Math.sign(raw)*cards.length:raw);
     card.classList.toggle('is-center',delta===0);
     card.setAttribute('aria-current',delta===0?'true':'false');
     if(mobile.matches){
      card.style.setProperty('--card-offset',String(delta));
      card.style.transform=`translateX(calc(-50% + ${delta*64}%)) scale(${1-Math.min(Math.abs(delta),2)*.13}) rotateY(${delta*-13}deg)`;
      card.style.opacity=String(1-Math.min(Math.abs(delta),2)*.28);
      card.style.zIndex=String(10-Math.abs(delta));
     }else{
      card.style.removeProperty('--card-offset');card.style.removeProperty('transform');card.style.removeProperty('opacity');card.style.removeProperty('z-index');
     }
    });
   };
   let startX=0,swiped=false;
   carousel.addEventListener('touchstart',e=>{startX=e.touches[0].clientX;swiped=false;},{passive:true});
   carousel.addEventListener('touchend',e=>{
    const dx=e.changedTouches[0].clientX-startX;
    if(Math.abs(dx)>35){active=(active+(dx<0?1:-1)+cards.length)%cards.length;paint();swiped=true;}
   },{passive:true});
   carousel.addEventListener('click',e=>{
    if(swiped){e.preventDefault();swiped=false;return;}
    const card=e.target.closest('[data-category-card]');
    if(card){active=cards.indexOf(card);paint();}
   });
   window.addEventListener('resize',paint,{passive:true});
   paint();
  }
 }
})();
