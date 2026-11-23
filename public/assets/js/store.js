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
  let active=Math.max(0,Math.min(cards.length-1,Number(carousel.dataset.selectedIndex||0)));
  const mobile=window.matchMedia('(max-width: 640px)');
  const paint=()=>{
   cards.forEach((card,i)=>{
    const raw=i-active;
    const delta=raw===0?0:(Math.abs(raw)>cards.length/2?raw-Math.sign(raw)*cards.length:raw);
    card.classList.toggle('is-selected',delta===0);
    card.classList.toggle('is-center',delta===0);
    card.setAttribute('aria-current',delta===0?'true':'false');
    if(mobile.matches){
     card.style.transform=`translateX(calc(-50% + ${delta*64}%)) scale(${1-Math.min(Math.abs(delta),2)*.13}) rotateY(${delta*-13}deg)`;
     card.style.opacity=String(1-Math.min(Math.abs(delta),2)*.28);
     card.style.zIndex=String(10-Math.abs(delta));
    }else{
     card.style.removeProperty('transform');card.style.removeProperty('opacity');card.style.removeProperty('z-index');
    }
   });
  };
  const selectedIndexFor=url=>{
   const target=new URL(url,location.href);
   const slug=target.searchParams.get('category');
   if(!slug)return 0;
   const index=cards.findIndex(card=>new URL(card.href,location.href).searchParams.get('category')===slug);
   return index<0?0:index;
  };
  const loadProducts=async(href,push=true)=>{
   const target=new URL(href,location.href);
   target.hash='products';
   try{
    const response=await fetch(target.pathname+target.search,{headers:{'X-Requested-With':'XMLHttpRequest'}});
    if(!response.ok)throw new Error('Unable to load category');
    const html=await response.text();
    const parsed=new DOMParser().parseFromString(html,'text/html');
    const next=parsed.querySelector('#products');
    const current=document.querySelector('#products');
    if(!next||!current)throw new Error('Products section missing');
    current.replaceWith(next);
    active=selectedIndexFor(target.href);
    carousel.dataset.selectedIndex=String(active);
    paint();
    if(push)history.pushState({category:target.searchParams.get('category')||''},'',target.pathname+target.search+'#products');
   }catch(error){
    window.location.href=target.pathname+target.search+'#products';
   }
  };
  let startX=0,swiped=false;
  carousel.addEventListener('touchstart',e=>{startX=e.touches[0].clientX;swiped=false;},{passive:true});
  carousel.addEventListener('touchend',e=>{
   const dx=e.changedTouches[0].clientX-startX;
   if(Math.abs(dx)>35&&cards.length>1){
    active=(active+(dx<0?1:-1)+cards.length)%cards.length;paint();swiped=true;
    loadProducts(cards[active].href);
   }
  },{passive:true});
  document.addEventListener('click',e=>{
   const card=e.target.closest('[data-category-card]');
   const filter=e.target.closest('.category-filter-bar a');
   const link=card||filter;
   if(!link)return;
   if(swiped){e.preventDefault();swiped=false;return;}
   e.preventDefault();
   if(card)active=cards.indexOf(card);
   else active=selectedIndexFor(link.href);
   paint();
   loadProducts(link.href);
  });
  window.addEventListener('popstate',()=>loadProducts(location.href,false));
  window.addEventListener('resize',paint,{passive:true});
  paint();
 }
})();