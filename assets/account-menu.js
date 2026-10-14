(() => {
    'use strict';
    const account=document.querySelector('.rm-page-header .rm-account');
    if(!account)return;
    const trigger=account.querySelector('summary');
    const panel=account.querySelector('.rm-account-menu');
    function position(){
        if(!account.open)return;
        const rect=trigger.getBoundingClientRect();
        const width=Math.min(320,innerWidth-32);
        panel.style.width=width+'px';
        panel.style.left=Math.max(16,Math.min(rect.right-width,innerWidth-width-16))+'px';
        panel.style.top=Math.min(rect.bottom+12,Math.max(16,innerHeight-panel.offsetHeight-16))+'px';
    }
    function close(restore=false){account.open=false;if(restore)trigger.focus();}
    account.addEventListener('toggle',()=>{trigger.setAttribute('aria-expanded',String(account.open));position();});
    document.addEventListener('pointerdown',e=>{if(account.open&&!account.contains(e.target))close();});
    document.addEventListener('focusin',e=>{if(account.open&&!account.contains(e.target))close();});
    account.addEventListener('keydown',e=>{
        if(e.key==='Escape'&&account.open){e.preventDefault();e.stopPropagation();close(true);}
        if(e.key==='ArrowDown'&&e.target===trigger){e.preventDefault();account.open=true;requestAnimationFrame(()=>{position();panel.querySelector('a')?.focus();});}
    });
    panel.addEventListener('click',e=>{if(e.target.closest('a'))close();});
    const mobile=matchMedia('(max-width:1199.98px)');
    mobile.addEventListener('change',()=>{if(mobile.matches)close();});
    addEventListener('resize',position);
    addEventListener('scroll',()=>{if(account.open)close();},{passive:true});
})();
