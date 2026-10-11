(()=>{'use strict';const loader=document.getElementById('admin-page-loading');if(!loader)return;let timer;const names={projects:'projects',catalog:'catalog'};
function hide(){loader.hidden=true;clearTimeout(timer);}
function show(page){loader.querySelector('.admin-loading-text').textContent='Loading '+names[page]+'…';loader.hidden=false;clearTimeout(timer);timer=setTimeout(hide,12000);}
if(names[loader.dataset.initial]&&document.readyState!=='complete')show(loader.dataset.initial);
window.addEventListener('load',hide,{once:true});window.addEventListener('pageshow',hide);
document.addEventListener('click',event=>{if(event.defaultPrevented||event.button!==0||event.ctrlKey||event.metaKey||event.shiftKey||event.altKey)return;const link=event.target.closest('a[href]');if(!link||link.hasAttribute('download')||(link.target&&link.target!=='_self'))return;const url=new URL(link.href,location.href);if(url.origin!==location.origin||url.href===location.href)return;const page=url.pathname.match(/\/admin\/(projects|catalog)(?:\.php)?\/?$/)?.[1];if(page)show(page);});
document.addEventListener('submit',event=>{if(event.defaultPrevented)return;const form=event.target;const url=new URL(form.action||location.href,location.href);const page=url.pathname.match(/\/admin\/(projects|catalog)(?:\.php)?\/?$/)?.[1];if(page)show(page);});
})();
