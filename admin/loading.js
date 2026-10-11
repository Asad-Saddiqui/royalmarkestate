(()=>{'use strict';const loader=document.getElementById('admin-page-loading');if(!loader)return;let shownAt=0,finishTimer,safetyTimer;
function busy(value){document.documentElement.classList.toggle('admin-is-loading',value);const main=document.getElementById('main-content');if(main)main.setAttribute('aria-busy',String(value));}
function hide(){loader.hidden=true;busy(false);clearTimeout(finishTimer);clearTimeout(safetyTimer);}
function show(label){clearTimeout(finishTimer);clearTimeout(safetyTimer);shownAt=performance.now();loader.querySelector('.admin-loading-text').textContent='Loading '+(label||'dashboard')+'...';loader.hidden=false;busy(true);safetyTimer=setTimeout(hide,12000);}
function finish(){clearTimeout(finishTimer);finishTimer=setTimeout(hide,Math.max(0,650-(performance.now()-shownAt)));}
show(loader.dataset.initial);
document.addEventListener('DOMContentLoaded',()=>busy(!loader.hidden),{once:true});
if(document.readyState==='complete')finish();else window.addEventListener('load',finish,{once:true});
window.addEventListener('pageshow',event=>{if(event.persisted)hide();});
function destination(value){const url=new URL(value,location.href);return url.origin===location.origin&&url.pathname.includes('/admin')?url:null;}
document.addEventListener('click',event=>{if(event.defaultPrevented||event.button!==0||event.ctrlKey||event.metaKey||event.shiftKey||event.altKey)return;const link=event.target.closest('a[href]');if(!link||link.hasAttribute('download')||(link.target&&link.target!=='_self'))return;const url=destination(link.href);if(!url||url.href===location.href||url.pathname===location.pathname&&url.search===location.search)return;show(link.textContent.trim()||'dashboard');});
document.addEventListener('submit',event=>{if(!event.defaultPrevented&&destination(event.target.action||location.href))show(loader.dataset.initial);});
})();
