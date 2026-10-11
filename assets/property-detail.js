(()=>{'use strict';const image=document.getElementById('pd-main-photo'),buttons=[...document.querySelectorAll('[data-photo]')];buttons.forEach(button=>button.addEventListener('click',()=>{image.src=button.dataset.photo;buttons.forEach(item=>item.setAttribute('aria-pressed',String(item===button)));}));})();

