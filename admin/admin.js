(() => {'use strict';
 const menu=document.querySelector('.mobile-menu'),close=document.querySelector('.sidebar-close'),mask=document.querySelector('.sidebar-mask'),sidebar=document.querySelector('.sidebar'),theme=document.querySelector('.theme-button'),media=matchMedia('(min-width:1000px)');
 function drawer(open){document.body.classList.toggle('drawer-open',open);mask.hidden=!open;menu.setAttribute('aria-expanded',String(open));if(open)close.focus();else menu.focus();}
 menu.addEventListener('click',()=>drawer(true));close.addEventListener('click',()=>drawer(false));mask.addEventListener('click',()=>drawer(false));
 document.addEventListener('keydown',e=>{if(!document.body.classList.contains('drawer-open'))return;if(e.key==='Escape'){drawer(false);return;}if(e.key==='Tab'){const items=[...sidebar.querySelectorAll('a,button')].filter(el=>el.getClientRects().length),first=items[0],last=items.at(-1);if(e.shiftKey&&document.activeElement===first){e.preventDefault();last.focus();}else if(!e.shiftKey&&document.activeElement===last){e.preventDefault();first.focus();}}});
 media.addEventListener('change',()=>{if(media.matches){document.body.classList.remove('drawer-open');mask.hidden=true;menu.setAttribute('aria-expanded','false');}});
 function setTheme(dark){document.documentElement.dataset.theme=dark?'dark':'light';theme.setAttribute('aria-pressed',String(dark));theme.setAttribute('aria-label',dark?'Switch to light mode':'Switch to dark mode');theme.firstElementChild.textContent=dark?'☀':'☾';}
 setTheme(window.RoyalmarkTheme?.read('admin')==='dark');
 theme.addEventListener('click',()=>{const dark=document.documentElement.dataset.theme!=='dark';setTheme(dark);window.RoyalmarkTheme?.save('admin',dark?'dark':'light');});
})();
