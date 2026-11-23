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
 if(carousel&&window.matchMedia('(max-width: 640px)').matches){
  const cards=[...carousel.children];let active=0;
  const paint=()=>cards.forEach((card,i)=>{const delta=((i-active+cards.length+Math.floor(cards.length/2))%cards.length)-Math.floor(cards.length/2);card.style.setProperty('--card-offset',delta);card.style.transform=`translateX(calc(-50% + ${delta*64}%)) scale(${1-Math.min(Math.abs(delta),2)*.13}) rotateY(${delta*-13}deg)`;card.style.opacity=String(1-Math.min(Math.abs(delta),2)*.28);card.style.zIndex=String(10-Math.abs(delta));card.classList.toggle('is-center',delta===0);card.setAttribute('aria-current',delta===0?'true':'false');});
  cards.forEach((card,i)=>card.addEventListener('click',e=>{if(i!==active){e.preventDefault();active=i;paint();}}));
  let startX=0;carousel.addEventListener('touchstart',e=>{startX=e.touches[0].clientX;},{passive:true});
  carousel.addEventListener('touchend',e=>{const dx=e.changedTouches[0].clientX-startX;if(Math.abs(dx)>35){active=(active+(dx<0?1:-1)+cards.length)%cards.length;paint();}},{passive:true});
  paint();
 }
})();