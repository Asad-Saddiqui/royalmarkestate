(() => {
 const button=document.querySelector('.rm-page-theme');
 if(!button)return;
 function apply(theme){document.documentElement.dataset.siteTheme=theme;button.title=theme==='dark'?'Switch to light mode':'Switch to dark mode';button.setAttribute('aria-label',button.title);button.textContent=theme==='dark'?'☾':'☀';}
 let initial='light';try{initial=localStorage.getItem('royalmark-theme')==='dark'?'dark':'light'}catch(e){}apply(initial);
 button.addEventListener('click',()=>{const next=document.documentElement.dataset.siteTheme==='dark'?'light':'dark';apply(next);try{localStorage.setItem('royalmark-theme',next)}catch(e){}});
})();
