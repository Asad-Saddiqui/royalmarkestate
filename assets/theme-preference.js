(() => {
    'use strict';

    // Only choices made with this controller count; old template preferences are ignored.
    const key = scope => 'royalmark-theme-v2:' + (scope === 'admin' ? 'admin' : 'site');
    function read(scope = 'site') {
        try {
            return localStorage.getItem(key(scope)) === 'dark' ? 'dark' : 'light';
        } catch {
            return 'light';
        }
    }
    function save(scope, theme) {
        const next = theme === 'dark' ? 'dark' : 'light';
        try {
            localStorage.setItem(key(scope), next);
        } catch {
            // The current page can still change theme if storage is unavailable.
        }
        return next;
    }

    window.RoyalmarkTheme = Object.freeze({read, save});
    const scope = document.currentScript?.dataset.themeScope === 'admin' ? 'admin' : 'site';
    document.documentElement.dataset[scope === 'admin' ? 'theme' : 'siteTheme'] = read(scope);
})();
