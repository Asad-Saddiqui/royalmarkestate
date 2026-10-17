(() => {
    'use strict';
    const dialog=document.querySelector('.rm-email-modal');if(!dialog)return;
    const form=dialog.querySelector('[data-email-form]'),feedback=dialog.querySelector('[data-email-feedback]'),contextBox=dialog.querySelector('[data-email-context]'),send=dialog.querySelector('[data-email-send]');
    let opener,url,busy=false,load=0;
    function fitViewport(){
        dialog.style.setProperty('--rm-email-viewport',(window.visualViewport?.height||window.innerHeight)+'px');
    }
    window.visualViewport?.addEventListener('resize',fitViewport);
    window.addEventListener('resize',fitViewport);
    fitViewport();
    function message(text,error=false){feedback.textContent=text;feedback.classList.toggle('is-error',error);}
    document.addEventListener('click',async event=>{
        const link=event.target.closest('a[href^="mailto:"]');if(!link)return;
        event.preventDefault();if(busy)return;
        opener=link;const token=++load;
        const source=link.closest('[data-email-kind]');
        url=new URL(dialog.dataset.emailEndpoint,document.baseURI);
        url.searchParams.set('kind',source?.dataset.emailKind||'company');url.searchParams.set('slug',source?.dataset.emailSlug||'');
        const requestUrl=new URL(url);
        form.reset();form.hidden=true;send.disabled=true;contextBox.replaceChildren();message('Loading email form…');
        fitViewport();
        if(!dialog.open)dialog.showModal();document.documentElement.classList.add('rm-email-open');
        dialog.querySelector('.rm-email-body').scrollTop=0;
        try{
            const response=await fetch(requestUrl,{headers:{Accept:'application/json'},cache:'no-store'});const result=await response.json();
            if(token!==load||!dialog.open)return;
            if(!response.ok)throw Error(result.message||'Unable to load this email form.');
            const context=result.context;
            const title=document.createElement('strong');title.textContent=context.title;contextBox.append(title);
            for(const [label,value] of Object.entries(context.details)){const row=document.createElement('p');row.textContent=label+': '+value;contextBox.append(row);}
            const recipient=document.createElement('p');recipient.textContent='To: '+context.recipient;contextBox.append(recipient);
            form.elements.csrf.value=result.csrf;form.elements.request_id.value=result.request_id;
            form.elements.subject.value=context.kind==='company'?'General enquiry':'Enquiry about '+context.title;
            form.hidden=false;send.disabled=false;send.textContent='Send email';message('');form.elements.name.focus({preventScroll:true});
        }catch(error){if(token===load)message(error.message,true);}
    });
    dialog.querySelector('[data-email-close]').addEventListener('click',()=>{if(!busy)dialog.close();});
    dialog.addEventListener('cancel',event=>{if(busy)event.preventDefault();});
    dialog.addEventListener('click',event=>{if(event.target!==dialog||busy)return;const b=dialog.getBoundingClientRect();if(event.clientX<b.left||event.clientX>b.right||event.clientY<b.top||event.clientY>b.bottom)dialog.close();});
    dialog.addEventListener('close',()=>{load++;document.documentElement.classList.remove('rm-email-open');opener?.focus({preventScroll:true});});
    form.addEventListener('submit',async event=>{
        event.preventDefault();if(busy||!form.reportValidity())return;
        busy=true;send.disabled=true;send.textContent='Sending…';form.setAttribute('aria-busy','true');message('Sending your email…');
        try{
            const response=await fetch(url,{method:'POST',body:new FormData(form),headers:{Accept:'application/json'}});const result=await response.json();
            if(!response.ok||!result.ok)throw Error(result.message||'Email could not be sent.');
            message(result.message);form.hidden=true;
        }catch(error){message(error.message,true);send.disabled=false;}
        finally{busy=false;send.textContent='Send email';form.removeAttribute('aria-busy');feedback.focus({preventScroll:true});}
    });
})();
