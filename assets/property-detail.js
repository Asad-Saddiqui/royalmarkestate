(()=>{'use strict';const pending=new WeakMap();
window.rmPropertyPhotoTransition=async(image,source)=>{if(!image||!source)return false;const token={};pending.set(image,token);const loaded=new Image();const ready=new Promise((resolve,reject)=>{loaded.onload=resolve;loaded.onerror=reject;});loaded.src=source;try{await ready;if(loaded.decode)await loaded.decode().catch(()=>{});}catch{return false;}if(pending.get(image)!==token)return false;image.getAnimations?.().forEach(animation=>animation.cancel());image.parentElement.querySelectorAll('.pd-photo-transition-layer').forEach(layer=>layer.remove());image.src=loaded.src;if(!matchMedia('(prefers-reduced-motion:reduce)').matches&&image.animate)image.animate([{transform:'scale(.96)',opacity:.75},{transform:'scale(1)',opacity:1}],{duration:380,easing:'cubic-bezier(.2,.65,.3,1)'});return true;};
const image=document.getElementById('pd-main-photo'),buttons=[...document.querySelectorAll('[data-photo]')];buttons.forEach(button=>button.addEventListener('click',()=>{window.rmPropertyPhotoTransition(image,button.dataset.photo);buttons.forEach(item=>item.setAttribute('aria-pressed',String(item===button)));}));})();

(()=>{'use strict';const image=document.getElementById('pd-main-photo'),stage=image?.parentElement,buttons=[...document.querySelectorAll('[data-photo]')];if(!image||buttons.length<2)return;const reduced=matchMedia('(prefers-reduced-motion:reduce)');let timer,zoom,index=0,visible=true,generation=0,outgoing;const duration=7500;
function stop(){clearTimeout(timer);generation++;zoom?.cancel();zoom=null;outgoing?.remove();outgoing=null;}
function available(){return visible&&!document.hidden&&!document.getElementById('pd-lightbox')?.open&&!reduced.matches;}
function schedule(){clearTimeout(timer);if(!available())return;zoom?.cancel();zoom=image.animate([{transform:'scale(1)'},{transform:'scale(1.14)'}],{duration,easing:'linear',fill:'forwards'});timer=setTimeout(async()=>{if(!available())return;const token=generation,next=(index+1)%buttons.length,loaded=new Image();loaded.src=buttons[next].dataset.photo;try{await loaded.decode();}catch{if(token===generation)schedule();return;}if(token!==generation||!available())return;
const old=image.cloneNode(false);old.removeAttribute('id');old.removeAttribute('role');old.removeAttribute('tabindex');old.setAttribute('aria-hidden','true');old.className='pd-auto-outgoing';old.style.transform=getComputedStyle(image).transform;stage.append(old);outgoing=old;zoom?.cancel();image.src=loaded.src;index=next;buttons.forEach((button,i)=>button.setAttribute('aria-pressed',String(i===index)));schedule();const fade=old.animate([{opacity:1},{opacity:0}],{duration:900,easing:'ease-in-out',fill:'forwards'});fade.finished.then(()=>{old.remove();if(outgoing===old)outgoing=null;}).catch(()=>old.remove());},duration);}
buttons.forEach((button,i)=>button.addEventListener('click',()=>{stop();index=i;const token=generation;setTimeout(()=>{if(token===generation)schedule();},450);}));document.addEventListener('visibilitychange',()=>{stop();schedule();});reduced.addEventListener('change',()=>{stop();schedule();});const dialog=document.getElementById('pd-lightbox');if(dialog)new MutationObserver(()=>{stop();schedule();}).observe(dialog,{attributes:true,attributeFilter:['open']});if('IntersectionObserver' in window)new IntersectionObserver(entries=>{visible=entries[0].isIntersecting;stop();schedule();},{threshold:.2}).observe(stage);else schedule();buttons.forEach(button=>{const preload=new Image();preload.src=button.dataset.photo;});})();

(()=>{'use strict';const feedback=document.querySelector('.pd-share-feedback');let timeout;function message(text){feedback.textContent=text;clearTimeout(timeout);timeout=setTimeout(()=>feedback.textContent='',5000);}function link(){const url=new URL(location.href);url.hash='';const slug=url.searchParams.get('slug');url.search='';if(slug)url.searchParams.set('slug',slug);return url.href;}async function copy(url){try{await navigator.clipboard.writeText(url);return true;}catch{const field=document.createElement('textarea');field.value=url;field.style.cssText='position:fixed;left:-9999px';document.body.append(field);field.select();const result=document.execCommand('copy');field.remove();return result;}}
document.querySelectorAll('[data-property-share]').forEach(button=>button.addEventListener('click',async()=>{const platform=button.dataset.propertyShare,url=link();if(platform==='facebook'||platform==='whatsapp'){const target=platform==='facebook'?'https://www.facebook.com/sharer/sharer.php?u=':'https://wa.me/?text=';window.open(target+encodeURIComponent(url),'_blank','noopener,noreferrer');return;}if(platform!=='copy'&&navigator.share){try{await navigator.share({url});return;}catch(error){if(error.name==='AbortError')return;}}const copied=await copy(url);message(copied?(platform==='copy'?'Property link copied.':'Link copied. Paste it into '+(platform==='tiktok'?'TikTok':'Instagram')+'.'):'Could not copy. Copy the link from your address bar.');}));})();

// Follow manual and automatic photo changes within the thumbnail strip only.
(() => {
    'use strict';
    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    document.querySelectorAll('.pd-thumbnails').forEach(strip => {
        function follow() {
            const active = strip.querySelector('[aria-pressed="true"]');
            if (!active || !strip.clientWidth) return;
            const bounds = strip.getBoundingClientRect();
            const thumb = active.getBoundingClientRect();
            const left = strip.scrollLeft + thumb.left - bounds.left - (strip.clientWidth - thumb.width) / 2;
            strip.scrollTo({left: Math.max(0, Math.min(left, strip.scrollWidth - strip.clientWidth)), behavior: reduced.matches ? 'auto' : 'smooth'});
        }
        new MutationObserver(follow).observe(strip, {subtree: true, attributes: true, attributeFilter: ['aria-pressed']});
        if ('ResizeObserver' in window) new ResizeObserver(follow).observe(strip);
        follow();
    });
})();
