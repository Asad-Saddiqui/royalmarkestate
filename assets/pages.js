(() => {
 const button=document.querySelector('.rm-page-theme');
 if(!button)return;
 function apply(theme){document.documentElement.dataset.siteTheme=theme;button.title=theme==='dark'?'Switch to light mode':'Switch to dark mode';button.setAttribute('aria-label',button.title);button.textContent=theme==='dark'?'☾':'☀';}
 let initial='light';try{initial=localStorage.getItem('royalmark-theme')==='dark'?'dark':'light'}catch(e){}apply(initial);
 button.addEventListener('click',()=>{const next=document.documentElement.dataset.siteTheme==='dark'?'light':'dark';apply(next);try{localStorage.setItem('royalmark-theme',next)}catch(e){}});
})();

// Bypass imported template navigation for the retired sign-in screen.
window.addEventListener('click',event=>{
 if(event.button!==0||event.ctrlKey||event.metaKey||event.shiftKey||event.altKey)return;
 const link=event.target.closest('a[href]');if(!link)return;
 const url=new URL(link.href,location.href);if(!/\/sign-in(?:\.html)?\/?$/.test(url.pathname))return;
 const login=document.querySelector('.page-login');if(!login)return;
 event.preventDefault();event.stopImmediatePropagation();location.assign(login.href);
},true);
