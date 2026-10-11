(() => {
    'use strict';
    const mobile = matchMedia('(max-width:1199.98px)');
    const reducedMotion = matchMedia('(prefers-reduced-motion:reduce)');
    const triggerSelector = '.framer-nzj1qu-container a[data-reset="button"]:not([href]), .rm-header .rm-toggle';
    const paths = {
        home: '<path d="m3 10 9-7 9 7v10H15v-7H9v7H3z"/>',
        properties: '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        projects: '<path d="M3 21V7l6-3v17M9 21V3h8v18M17 9h4v12M2 21h20M12 7h2M12 11h2M12 15h2"/>',
        contact: '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        about: '<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/>',
        blog: '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8h10M7 12h10M7 16h6"/>',
        close: '<path d="m6 6 12 12M18 6 6 18"/>',
        plus: '<path d="M12 5v14M5 12h14"/>',
        login: '<path d="M14 3h6v18h-6M3 12h12m-4-4 4 4-4 4"/>'
    };
    const icon = name => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' + paths[name] + '</svg>';
    const routes = [
        ['home', './', 'Home'], ['properties', './property-listing', 'Properties'],
        ['projects', './projects', 'Projects'], ['contact', './contact-us', 'Contact Us'],
        ['about', './about-us', 'About'], ['blog', './blog', 'Blog']
    ];
    const dialog = document.createElement('dialog');
    dialog.id = 'rm-mobile-drawer';
    dialog.className = 'rm-mobile-drawer';
    dialog.setAttribute('aria-labelledby', 'rm-drawer-title');
    dialog.innerHTML = '<div class="rm-drawer-inner"><div class="rm-drawer-top">' +
        '<a href="./" class="rm-drawer-brand" aria-label="Royalmark Estate home"><img src="./assets/royalmark-logo.png" alt="Royalmark Estate" width="126" height="74"></a>' +
        '<button type="button" class="rm-drawer-close" aria-label="Close menu">' + icon('close') + '</button></div>' +
        '<h2 class="rm-drawer-title" id="rm-drawer-title">Menu</h2><nav class="rm-drawer-nav" aria-label="Mobile navigation">' +
        routes.map(([route, href, label]) => '<a href="' + href + '" data-route="' + route + '">' + icon(route) + '<span>' + label + '</span></a>').join('') + '</nav>' +
        '<div class="rm-drawer-actions"><button type="button" class="rm-drawer-theme" aria-pressed="false"><span class="rm-theme-icon">☀</span><span class="rm-theme-label">Light mode</span></button><a href="./admin/login">' + icon('login') + 'Login</a><a href="./admin/login">' + icon('plus') + 'Add Properties</a></div></div>';
    document.body.appendChild(dialog);
    const account=document.querySelector('.page-account'),logout=document.querySelector('.page-logout');
    if(account&&logout){const actions=dialog.querySelector('.rm-drawer-actions');actions.querySelector('a[href="./admin/login"]')?.remove();actions.append(account.cloneNode(true));const logoutCopy=logout.cloneNode(true);const label=document.createElement('span');label.textContent='Logout';logoutCopy.querySelector('button').append(label);actions.append(logoutCopy);const add=actions.querySelector('a[href="./admin/login"]');if(add)add.href=document.querySelector('.page-add').href;}

    const themeControl = dialog.querySelector('.rm-drawer-theme');
    function sourceThemeButton() {
        const buttons = [...document.querySelectorAll('.framer-nzj1qu-container button[title],.rm-page-theme[title]')].filter(button => /Switch to (dark|light) mode/i.test(button.title));
        return buttons.find(button => {
            const variant = button.closest('.framer-w5B6s');
            return variant && variant.getBoundingClientRect().width > 0;
        }) || buttons[0];
    }
    function syncTheme() {
        const original = sourceThemeButton();
        const dark = !!original && /Switch to light mode/i.test(original.title);
        dialog.dataset.theme = dark ? 'dark' : 'light';
        themeControl.setAttribute('aria-pressed', String(dark));
        themeControl.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
        themeControl.querySelector('.rm-theme-label').textContent = dark ? 'Dark mode' : 'Light mode';
        themeControl.querySelector('.rm-theme-icon').textContent = dark ? '☾' : '☀';
    }
    themeControl.addEventListener('click', () => {
        const original = sourceThemeButton();
        if (original) original.click();
        requestAnimationFrame(syncTheme);
    });
    const themeHeader = document.querySelector('.framer-nzj1qu-container,.rm-page-header');
    if (themeHeader) new MutationObserver(syncTheme).observe(themeHeader, { childList: true, subtree: true, attributes: true, attributeFilter: ['title'] });
    syncTheme();
    let opener = null;
    let closeTimer = null;
    let previousScroll = null;
    let openFrame = null;
    function syncTriggers() {
        document.querySelectorAll(triggerSelector).forEach(trigger => {
            trigger.setAttribute('role', 'button');
            trigger.setAttribute('aria-label', 'Open menu');
            trigger.setAttribute('aria-haspopup', 'dialog');
            trigger.setAttribute('aria-controls', dialog.id);
            trigger.setAttribute('aria-expanded', String(dialog.open));
        });
    }
    function currentRoute() {
        if (location.hash === '#projects') return 'projects';
        if (location.hash === '#contact-us') return 'contact';
        const path = location.pathname.replace(/\/$/, '');
        if (path.endsWith('/projects') || path.endsWith('/projects.php')) return 'projects';
        if (path.endsWith('/contact-us') || path.endsWith('/contact.php')) return 'contact';
        if ((path.includes('/property-listing') || path.endsWith('/properties.php'))) return 'properties';
        if ((path.endsWith('/about-us') || path.endsWith('/about.php'))) return 'about';
        if ((path.endsWith('/blog') || path.endsWith('/blog.php'))) return 'blog';
        return 'home';
    }
    function updateActive() {
        const route = currentRoute();
        dialog.querySelectorAll('[data-route]').forEach(link => {
            if (link.dataset.route === route) link.setAttribute('aria-current', route === 'projects' || route === 'contact' ? 'location' : 'page');
            else link.removeAttribute('aria-current');
        });
    }
    function openDrawer(trigger) {
        if (!mobile.matches || dialog.open) return;
        clearTimeout(closeTimer);
        opener = trigger;
        previousScroll = { html: document.documentElement.style.overflow, body: document.body.style.overflow };
        document.documentElement.style.overflow = 'hidden';
        document.body.style.overflow = 'hidden';
        updateActive();
        syncTheme();
        dialog.showModal();
        syncTriggers();
        dialog.querySelector('.rm-drawer-close').focus({ preventScroll: true });
        openFrame = requestAnimationFrame(() => dialog.classList.add('rm-drawer-open'));
    }
    function finishClose(restoreFocus) {
        if (dialog.open) dialog.close();
        if (previousScroll) {
            document.documentElement.style.overflow = previousScroll.html;
            document.body.style.overflow = previousScroll.body;
            previousScroll = null;
        }
        syncTriggers();
        if (restoreFocus) {
            const trigger = opener && opener.isConnected && opener.getBoundingClientRect().width > 0 ? opener :
                [...document.querySelectorAll(triggerSelector)].find(el => el.getBoundingClientRect().width > 0);
            if (trigger) trigger.focus({ preventScroll: true });
        }
        opener = null;
    }
    function closeDrawer(immediate = false, restoreFocus = true) {
        if (!dialog.open) return;
        cancelAnimationFrame(openFrame);
        clearTimeout(closeTimer);
        dialog.classList.remove('rm-drawer-open');
        if (immediate || reducedMotion.matches) finishClose(restoreFocus);
        else closeTimer = setTimeout(() => finishClose(restoreFocus), 260);
    }
    function getTrigger(event) {
        return mobile.matches && event.target instanceof Element ? event.target.closest(triggerSelector) : null;
    }
    // Stop Framer's pointer gesture before its existing accordion can open.
    ['pointerdown', 'pointerup'].forEach(type => document.addEventListener(type, event => {
        if (getTrigger(event)) event.stopImmediatePropagation();
    }, true));
    document.addEventListener('click', event => {
        const trigger = getTrigger(event);
        if (!trigger) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        openDrawer(trigger);
    }, true);
    ['keydown', 'keyup'].forEach(type => document.addEventListener(type, event => {
        const trigger = getTrigger(event);
        if (!trigger || (event.key !== 'Enter' && event.key !== ' ')) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        if (type === 'keydown' && !event.repeat) openDrawer(trigger);
    }, true));
    dialog.querySelector('.rm-drawer-close').addEventListener('click', () => closeDrawer());
    dialog.addEventListener('cancel', event => { event.preventDefault(); closeDrawer(); });
    dialog.addEventListener('keydown', event => {
        if (event.key === 'Escape') { event.preventDefault(); event.stopPropagation(); closeDrawer(); }
    });
    dialog.addEventListener('click', event => {
        if (event.target.closest('a')) { closeDrawer(true, false); return; }
        if (event.target !== dialog) return;
        const box = dialog.getBoundingClientRect();
        if (event.clientX < box.left || event.clientX > box.right || event.clientY < box.top || event.clientY > box.bottom) closeDrawer();
    });
    mobile.addEventListener('change', () => { if (!mobile.matches) closeDrawer(true, false); syncTriggers(); });
    window.addEventListener('hashchange', updateActive);
    let syncPending = false;
    const headerRoot = document.getElementById('main') || document.querySelector('.rm-header');
    if (headerRoot) new MutationObserver(() => {
        if (!syncPending) {
            syncPending = true;
            requestAnimationFrame(() => { syncPending = false; syncTriggers(); });
        }
    }).observe(headerRoot, { childList: true, subtree: true });
    syncTriggers();
})();
