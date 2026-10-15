(()=>{'use strict';
const root=document.getElementById('main-content'),dialog=root?.querySelector('#contact-delete-dialog');
if(!root||!dialog||dialog.dataset.contactsReady)return;
dialog.dataset.contactsReady='true';let pending=null,opener=null;
root.querySelectorAll('.contact-delete-form').forEach(form=>{
 form.addEventListener('submit',event=>{
  if(form.dataset.confirmed==='true')return;
  event.preventDefault();pending=form;opener=event.submitter;
  dialog.querySelector('#contact-delete-name').textContent='Message from '+(form.dataset.name||'a website visitor');
  dialog.showModal();dialog.querySelector('[data-contact-cancel]').focus();
 });
});
dialog.querySelector('[data-contact-cancel]').addEventListener('click',()=>dialog.close());
dialog.querySelector('[data-contact-confirm]').addEventListener('click',()=>{
 if(!pending)return;const form=pending;form.dataset.confirmed='true';dialog.close();form.requestSubmit();
});
dialog.addEventListener('click',event=>{if(event.target===dialog){const bounds=dialog.getBoundingClientRect();if(event.clientX<bounds.left||event.clientX>bounds.right||event.clientY<bounds.top||event.clientY>bounds.bottom)dialog.close();}});
dialog.addEventListener('close',()=>{pending=null;opener?.focus();opener=null;});
})();
