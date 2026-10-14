(()=>{'use strict';
const scope=document.getElementById('main-content');if(!scope)return;
scope.querySelectorAll('[data-agent-repeater]').forEach(group=>{
 if(group.dataset.ready==='true')return;group.dataset.ready='true';
 const rows=group.querySelector('.agent-entry-rows'),add=group.querySelector('[data-agent-add]'),count=group.querySelector('.agent-entry-count'),key=group.dataset.agentRepeater,label=group.dataset.label||'Entry';
 function update(){const entries=[...rows.children];entries.forEach((row,index)=>row.querySelector('input').name=key+'['+index+']');add.disabled=entries.length>=12;count.textContent=entries.length+' / 12 entries';}
 add.addEventListener('click',()=>{if(rows.children.length>=12)return;const row=document.createElement('div');row.className='agent-entry-row';const holder=document.createElement('label');holder.append(document.createTextNode(label));const input=document.createElement('input');input.maxLength=80;input.placeholder=group.dataset.placeholder||'';holder.append(input);const remove=document.createElement('button');remove.type='button';remove.className='property-entry-button';remove.dataset.agentRemove='';remove.setAttribute('aria-label','Remove '+label.toLowerCase());remove.textContent='Remove';row.append(holder,remove);rows.append(row);update();input.focus();});
 rows.addEventListener('click',event=>{const button=event.target.closest('[data-agent-remove]');if(!button)return;const row=button.closest('.agent-entry-row'),next=row.nextElementSibling||row.previousElementSibling;row.remove();update();(next?.querySelector('input')||add).focus();});update();
});
const upload=scope.querySelector('[data-agent-photo]');
if(upload&&upload.dataset.ready!=='true'){
 upload.dataset.ready='true';const preview=scope.querySelector('.agent-photo-preview'),message=scope.querySelector('.agent-photo-message');let objectUrl='',original=preview.innerHTML;
 upload.addEventListener('change',()=>{if(objectUrl){URL.revokeObjectURL(objectUrl);objectUrl='';}message.textContent='';const file=upload.files[0];if(!file){preview.innerHTML=original;return;}
 if(!['image/jpeg','image/png','image/webp'].includes(file.type)||file.size>5*1024*1024){message.textContent='Choose a JPEG, PNG or WebP image up to 5 MB.';upload.value='';preview.innerHTML=original;return;}
 objectUrl=URL.createObjectURL(file);const image=document.createElement('img');image.alt='New agent profile photo preview';image.src=objectUrl;image.addEventListener('error',()=>{message.textContent='This image could not be previewed. Select another image.';upload.value='';preview.innerHTML=original;URL.revokeObjectURL(objectUrl);objectUrl='';});preview.replaceChildren(image);const remove=scope.querySelector('[name="remove_photo"]');if(remove)remove.checked=false;message.textContent=file.name;
 });
 const remove=scope.querySelector('[name="remove_photo"]');if(remove)remove.addEventListener('change',()=>{if(upload.files.length)return;if(remove.checked){const text=document.createElement('span');text.textContent='Photo will be removed when you save.';preview.replaceChildren(text);}else preview.innerHTML=original;});
 window.addEventListener('pagehide',()=>{if(objectUrl)URL.revokeObjectURL(objectUrl);},{once:true});
}
const forms=[...scope.querySelectorAll('.delete-agent-form')].filter(form=>form.dataset.ready!=='true');
if(forms.length){
 document.querySelectorAll('.agent-delete-dialog').forEach(dialog=>dialog.remove());
 const dialog=document.createElement('dialog');dialog.className='agent-delete-dialog delete-confirm-modal';dialog.setAttribute('aria-labelledby','delete-agent-title');dialog.setAttribute('aria-describedby','delete-agent-description');dialog.innerHTML='<h2 id="delete-agent-title">Delete this agent?</h2><p id="delete-agent-description">This removes the profile from the dashboard and public website. You can unpublish the agent if you want to keep their information.</p><p class="agent-delete-name"></p><div class="agent-delete-actions"><button type="button" class="agent-action-button" data-cancel-agent-delete>Cancel</button><button type="button" class="primary-button" data-confirm-agent-delete>Delete agent</button></div>';document.body.append(dialog);
 let pending=null,opener=null;const cancel=dialog.querySelector('[data-cancel-agent-delete]');
 forms.forEach(form=>{form.dataset.ready='true';form.addEventListener('submit',event=>{if(form.dataset.confirmed==='true')return;event.preventDefault();pending=form;opener=event.submitter;dialog.querySelector('.agent-delete-name').textContent=form.dataset.agentName||'Agent profile';dialog.showModal();document.body.classList.add('user-modal-open');cancel.focus();});});
 cancel.addEventListener('click',()=>dialog.close());dialog.querySelector('[data-confirm-agent-delete]').addEventListener('click',()=>{if(!pending)return;const form=pending;form.dataset.confirmed='true';form.querySelector('[name="confirm_delete"]').value='1';dialog.close();form.requestSubmit();});
 dialog.addEventListener('close',()=>{pending=null;document.body.classList.remove('user-modal-open');opener?.focus();});dialog.addEventListener('click',event=>{if(event.target!==dialog)return;const rect=dialog.getBoundingClientRect();if(event.clientX<rect.left||event.clientX>rect.right||event.clientY<rect.top||event.clientY>rect.bottom)dialog.close();});
}
})();
