(()=>{'use strict';const buttons=[...document.querySelectorAll('[data-theme-choice]')],theme=document.querySelector('.theme-button'),status=document.getElementById('settings-status');
function sync(){const active=document.documentElement.dataset.theme==='dark'?'dark':'light';buttons.forEach(button=>button.setAttribute('aria-pressed',String(button.dataset.themeChoice===active)));}
buttons.forEach(button=>button.addEventListener('click',()=>{if((document.documentElement.dataset.theme||'light')!==button.dataset.themeChoice)theme.click();sync();status.textContent=button.dataset.themeChoice==='dark'?'Dark mode selected.':'Light mode selected.';}));
new MutationObserver(sync).observe(document.documentElement,{attributes:true,attributeFilter:['data-theme']});sync();})();
