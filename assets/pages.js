(() => {
 const button=document.querySelector('.rm-page-theme');
 if(!button)return;
 const icons={light:'<circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/>',dark:'<path d="M20.9 13a9 9 0 0 1-9.9-9.9A9 9 0 1 0 20.9 13Z"/>'};
 function apply(theme){document.documentElement.dataset.siteTheme=theme;button.title=theme==='dark'?'Switch to light mode':'Switch to dark mode';button.setAttribute('aria-label',button.title);button.setAttribute('aria-pressed',String(theme==='dark'));button.innerHTML='<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:block">'+icons[theme]+'</svg>';}
 apply(window.RoyalmarkTheme?.read('site')||'light');
 button.addEventListener('click',()=>{const next=document.documentElement.dataset.siteTheme==='dark'?'light':'dark';apply(next);window.RoyalmarkTheme?.save('site',next);});
})();

// Bypass imported template navigation for the retired sign-in screen.
window.addEventListener('click',event=>{
 if(event.button!==0||event.ctrlKey||event.metaKey||event.shiftKey||event.altKey)return;
 const link=event.target.closest('a[href]');if(!link)return;
 const url=new URL(link.href,location.href);if(!/\/sign-in(?:\.html)?\/?$/.test(url.pathname))return;
 const login=document.querySelector('.page-login');if(!login)return;
 event.preventDefault();event.stopImmediatePropagation();location.assign(login.href);
},true);
