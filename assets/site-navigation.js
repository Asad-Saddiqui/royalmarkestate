(() => {
    'use strict';
    const root = document.getElementById('main');
    if (!root) return;
    let pending = false;
    function updateNavigation() {
        pending = false;
        const header = root.querySelector('.framer-nzj1qu-container');
        if (header) {
            header.querySelectorAll('.framer-Yxz59').forEach(control => {
                const label = [...control.querySelectorAll('p')].map(p => p.textContent.trim()).find(text => text === 'Buy' || text === 'Rent');
                if (!label) return;
                const route = label === 'Buy' ? 'projects' : 'contact';
                const host = control.parentElement;
                control.setAttribute('data-rm-replaced', route);
                if (!host.querySelector(':scope > .rm-menu-link')) {
                    const link = document.createElement('a');
                    const sample = header.querySelector('a[href="./property-listing"]');
                    link.className = (sample ? sample.className : 'framer-text') + ' rm-menu-link';
                    if (sample && sample.dataset.stylesPreset) link.dataset.stylesPreset = sample.dataset.stylesPreset;
                    link.href = route === 'projects' ? '#projects' : '#contact-us';
                    link.dataset.route = route;
                    link.textContent = route === 'projects' ? 'Projects' : 'Contact Us';
                    host.appendChild(link);
                }
            });
            header.querySelectorAll('.rm-menu-link').forEach(link => {
                if (link.hash === location.hash) link.setAttribute('aria-current', 'location');
                else link.removeAttribute('aria-current');
            });
            if (location.hash === '#projects' || location.hash === '#contact-us') {
                header.querySelectorAll('a[href="./"]').forEach(link => link.removeAttribute('data-framer-page-link-current'));
            } else {
                header.querySelectorAll('a[href="./"]').forEach(link => link.setAttribute('data-framer-page-link-current', 'true'));
            }
        }
        const projects = root.querySelector('[data-framer-name="Recommended Properties Section"]');
        if (projects) projects.id = 'projects';
        const footer = [...root.querySelectorAll('[data-framer-name="Footer1"]')].find(el => el.getBoundingClientRect().width > 0);
        if (footer) {
            root.querySelectorAll('[id="contact-us"]').forEach(el => { if (el !== footer) el.removeAttribute('id'); });
            footer.id = 'contact-us';
        }
    }
    function schedule() {
        if (!pending) { pending = true; requestAnimationFrame(updateNavigation); }
    }
    updateNavigation();
    new MutationObserver(schedule).observe(root, { childList: true, subtree: true });
    window.addEventListener('hashchange', updateNavigation);
    window.addEventListener('resize', schedule);
})();
