(() => {
    'use strict';
    const footer = document.querySelector('.rm-company-footer');
    const shortcuts = document.querySelector('.rm-contact-shortcuts');
    if (!footer || !shortcuts || !('IntersectionObserver' in window)) return;

    // The footer provides contact links; keep floating buttons off its mobile text.
    const observer = new IntersectionObserver(entries => {
        shortcuts.classList.toggle('is-footer-visible', entries[0].isIntersecting);
    });
    observer.observe(footer);
})();
