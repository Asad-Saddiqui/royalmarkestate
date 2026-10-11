(()=>{'use strict';
function notify(message,error=false){let region=document.querySelector('.action-notifications');if(!region){region=document.createElement('div');region.className='action-notifications';region.setAttribute('aria-label','Notifications');if('showPopover' in region)region.setAttribute('popover','manual');document.body.append(region);}const card=document.createElement('div');card.className='action-notification'+(error?' is-error':'');card.setAttribute('role',error?'alert':'status');const text=document.createElement('span');text.textContent=message;const close=document.createElement('button');close.type='button';close.setAttribute('aria-label','Dismiss notification');close.textContent='×';close.onclick=()=>card.remove();card.append(text,close);region.append(card);if(region.showPopover)region.showPopover();if(!error)setTimeout(()=>card.remove(),6500);}
document.addEventListener('submit',async event=>{
 const form=event.target;if(form.method.toLowerCase()!=='post'||form.classList.contains('logout-form')||event.defaultPrevented)return;
 if(form.classList.contains('delete-user-form')&&form.dataset.confirmed!=='true')return;
 const url=new URL(form.getAttribute('action')||location.href,location.href);if(url.origin!==location.origin||!url.pathname.includes('/admin/'))return;
 event.preventDefault();if(form.dataset.saving==='true')return;form.dataset.saving='true';
 const payload=new FormData(form);if(event.submitter?.name)payload.append(event.submitter.name,event.submitter.value);
 const buttons=[...form.querySelectorAll('button[type="submit"],button:not([type])')],previous=buttons.map(button=>({button,disabled:button.disabled,text:button.textContent}));buttons.forEach(button=>{button.disabled=true;button.textContent='Saving...';});form.setAttribute('aria-busy','true');
 try{
  const response=await fetch(url,{method:'POST',body:payload,credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}});
  const html=await response.text(),doc=new DOMParser().parseFromString(html,'text/html');
  const errors=[...doc.querySelectorAll('.form-error,.catalog-notice.is-error')];if(errors.length)throw new Error(errors[0].textContent.replace('×','').trim());
  const next=doc.getElementById('main-content');if(!response.ok||!next)throw new Error(response.status===403?'Your session or form token expired. Refresh and sign in again.':'Unable to save. Please try again.');
  const notices=[...next.querySelectorAll('.success-note,.user-notification,.catalog-notice')];const message=notices.filter(node=>!node.querySelector('code')).map(node=>node.textContent.replace('×','').trim()).filter(Boolean).join(' ')||'Changes saved successfully.';
  notices.forEach(node=>{if(!node.querySelector('code'))node.remove();});
  const scripts=[...next.querySelectorAll('script[src]')].map(script=>new URL(script.getAttribute('src'),response.url));next.querySelectorAll('script').forEach(script=>script.remove());
  document.querySelectorAll('dialog[open]').forEach(dialog=>dialog.close());document.querySelectorAll('.delete-confirm-modal,.rm-select-panel').forEach(node=>node.remove());document.body.classList.remove('user-modal-open');
  document.getElementById('main-content').replaceWith(document.importNode(next,true));document.title=doc.title;history.replaceState(null,'',response.url);
  document.querySelectorAll('.sidebar a').forEach(link=>{const match=[...doc.querySelectorAll('.sidebar a')].find(other=>other.getAttribute('href')===link.getAttribute('href'));if(match?.hasAttribute('aria-current'))link.setAttribute('aria-current','page');else link.removeAttribute('aria-current');if(match?.querySelector('small')&&link.querySelector('small'))link.querySelector('small').textContent=match.querySelector('small').textContent;});
  const heading=doc.querySelector('.breadcrumb strong');if(heading)document.querySelector('.breadcrumb strong').textContent=heading.textContent;
  for(const src of scripts){if(!/\/(users|catalog|settings|form-preview|blog)\.js$/.test(src.pathname))continue;await new Promise((resolve,reject)=>{const script=document.createElement('script');script.src=src.href;script.onload=()=>{script.remove();resolve();};script.onerror=()=>{script.remove();reject(new Error('Saved, but some controls could not refresh. Please refresh the page.'));};document.body.append(script);});}
  notify(message);
 }catch(error){notify(error.message||'Unable to save. Please try again.',true);}
 finally{delete form.dataset.saving;delete form.dataset.confirmed;form.removeAttribute('aria-busy');previous.forEach(({button,disabled,text})=>{button.disabled=disabled;button.textContent=text;});}
},true);
})();
