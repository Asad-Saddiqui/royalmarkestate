(()=>{'use strict';const modal=document.getElementById('create-user-modal'),opener=document.querySelector('[data-open-user-modal]');if(!modal||!opener)return;
function open(){modal.showModal();document.body.classList.add('user-modal-open');modal.querySelector('input[name="name"]').focus();}
function close(){modal.close();}
opener.addEventListener('click',open);modal.querySelectorAll('.user-modal-close,[data-close-user-modal]').forEach(button=>button.addEventListener('click',close));
modal.addEventListener('click',event=>{if(event.target!==modal)return;const rect=modal.getBoundingClientRect();if(event.clientX<rect.left||event.clientX>rect.right||event.clientY<rect.top||event.clientY>rect.bottom)close();});
modal.addEventListener('close',()=>{document.body.classList.remove('user-modal-open');opener.focus();});
if(modal.dataset.reopen==='true')open();
})();

(()=>{document.querySelectorAll('.delete-user-form').forEach(form=>form.addEventListener('submit',event=>{if(!confirm('Delete '+form.dataset.userEmail+'? This permanently removes the account and stops its login access.'))event.preventDefault();}));document.querySelectorAll('.notification-close').forEach(button=>button.addEventListener('click',()=>button.closest('.user-notification').remove()));})();
