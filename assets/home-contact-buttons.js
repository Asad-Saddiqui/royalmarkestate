(() => {
    'use strict';
    const template = document.getElementById('rm-hero-contact-template');
    if (!template) return;
    function mount() {
        const primary = document.querySelector('.framer-rii6yc');
        if (!primary || primary.parentElement.querySelector('.rm-hero-contact')) return;
        primary.after(template.content.cloneNode(true));
    }
    mount();
    const root = document.getElementById('main');
    if (root) new MutationObserver(mount).observe(root, {childList: true, subtree: true});
})();
