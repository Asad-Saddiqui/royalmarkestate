/* Local interactions. No remote runtime or client-side page replacement. */
(() => {
    'use strict';
    const root = document.getElementById('main');
    if (!root) return;
    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    const reveal = [...root.querySelectorAll('[data-framer-appear-id], [style*="will-change:transform"], .rm-native-heading, .rm-home-gallery-grid > a')].filter(el => !el.closest('.framer-nzj1qu-container'));
    if (!reduced.matches && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver(entries => entries.forEach(entry => {
            if (entry.isIntersecting) { entry.target.classList.add('rm-revealed'); observer.unobserve(entry.target); }
        }), {threshold: .06, rootMargin: '0px 0px -20px 0px'});
        reveal.forEach(el => {el.classList.add('rm-reveal'); observer.observe(el);});
        reduced.addEventListener('change', () => {if(reduced.matches){reveal.forEach(el=>el.classList.add('rm-revealed'));observer.disconnect();}});
    }
    // Static exported ticker content becomes a native, keyboard-scrollable rail.
    root.querySelectorAll('[data-framer-name="Client Story"] ul').forEach(list => {
        const seen = new Set();
        [...list.children].forEach(item => {
            const text = item.textContent.trim();
            if (seen.has(text)) {item.remove();return;}
            seen.add(text);item.removeAttribute('aria-hidden');item.style.cssText='';
        });
        const rail = list.parentElement;
        rail.classList.add('rm-native-story-rail');rail.tabIndex=0;rail.setAttribute('role','region');rail.setAttribute('aria-label','Client stories. Scroll to explore.');
        list.classList.add('rm-native-story-list');list.style.cssText='';
    });
    // All navigation uses standard links, including the old clean detail routes.
    root.querySelectorAll('a[href]').forEach(link => {
        const href=link.getAttribute('href');const detail=href.match(/^\.\/property-listing\/([a-z0-9-]+)\/?$/);
        if(detail)link.href='./property.php?slug='+encodeURIComponent(detail[1]);
        if(href==='./properties.php')link.href='./property-listing';
        if(href==='./sign-in')link.href='./admin/login';
    });
})();
