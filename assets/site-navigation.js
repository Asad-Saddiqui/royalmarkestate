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
                    const sample = header.querySelector('a[href="./property-listing"],a[href="./properties.php"]');
                    link.className = (sample ? sample.className : 'framer-text') + ' rm-menu-link';
                    if (sample && sample.dataset.stylesPreset) link.dataset.stylesPreset = sample.dataset.stylesPreset;
                    link.href = route === 'projects' ? './projects.php' : './contact.php';
                    link.dataset.route = route;
                    link.textContent = route === 'projects' ? 'Projects' : 'Contact Us';
                    host.appendChild(link);
                }
            });
            const mobileList = header.querySelector('.framer-g36uf7');
            if (mobileList) {
                const properties = mobileList.querySelector('a[href="./property-listing"],a[href="./properties.php"]');
                const template = properties && properties.closest('[data-highlight]');
                let previous = template;
                if (template) ['projects', 'contact'].forEach(route => {
                    let item = mobileList.querySelector('.rm-mobile-item[data-route="' + route + '"]');
                    if (!item) {
                        item = template.cloneNode(true);
                        item.classList.add('rm-mobile-item');
                        item.dataset.route = route;
                        item.removeAttribute('id');
                        item.querySelectorAll('[id]').forEach(el => el.removeAttribute('id'));
                        const link = item.querySelector('a');
                        link.href = route === 'projects' ? './projects.php' : './contact.php';
                        link.textContent = route === 'projects' ? 'Projects' : 'Contact Us';
                        link.classList.add('rm-menu-link');
                        link.dataset.route = route;
                        link.removeAttribute('data-framer-page-link-current');
                        previous.after(item);
                    }
                    previous = item;
                });
            }
            const normalizePath = path => path.replace(/\/index\.php$/, '/').replace(/\/$/, '');
            header.querySelectorAll('.rm-menu-link').forEach(link => {
                const destination = new URL(link.href, location.href);
                const samePage = destination.origin === location.origin && normalizePath(destination.pathname) === normalizePath(location.pathname);
                const active = samePage && (!destination.hash || destination.hash === location.hash);
                if (active) link.setAttribute('aria-current', destination.hash ? 'location' : 'page');
                else link.removeAttribute('aria-current');
            });
            if (location.hash === '#projects' || location.hash === '#contact-us') {
                header.querySelectorAll('a[href="./"]').forEach(link => link.removeAttribute('data-framer-page-link-current'));
            } else {
                header.querySelectorAll('a[href="./"]').forEach(link => link.setAttribute('data-framer-page-link-current', 'true'));
            }
        }
        const pageRoutes = {'./properties.php':'./property-listing','./about-us':'./about.php','./blog':'./blog.php'};
        root.querySelectorAll('a[href]').forEach(link => {const replacement=pageRoutes[link.getAttribute('href')];if(replacement)link.setAttribute('href',replacement);const href=link.getAttribute('href');const detail=href.match(/^\.\/property-listing\/([a-z0-9-]+)\/?$/);if(detail)link.setAttribute('href','./property.php?slug='+encodeURIComponent(detail[1]));});
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
