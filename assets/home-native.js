/* Local interactions. No remote runtime or client-side page replacement. */
(() => {
    'use strict';
    const root = document.getElementById('main');
    if (!root) return;
    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    const reveal = [...root.querySelectorAll('[data-framer-appear-id], [style*="will-change:transform"], .rm-native-heading, .rm-home-gallery-grid > a, .rm-service-card, .rm-home-stories article, .rm-home-counts a')].filter(el => !el.closest('.framer-nzj1qu-container'));
    if (!reduced.matches && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver(entries => entries.forEach(entry => {
            if (entry.isIntersecting) { entry.target.classList.add('rm-revealed'); observer.unobserve(entry.target); }
        }), {threshold: .06, rootMargin: '0px 0px -20px 0px'});
        reveal.forEach(el => {el.classList.add('rm-reveal'); observer.observe(el);});
        reduced.addEventListener('change', () => {if(reduced.matches){reveal.forEach(el=>el.classList.add('rm-revealed'));observer.disconnect();}});
    }
    // Pause decorative video off screen; keep a poster visible while the video loads.
    if ('IntersectionObserver' in window) {
        const videos = new IntersectionObserver(entries => entries.forEach(entry => {
            const video=entry.target;
            if(entry.isIntersecting&&!reduced.matches&&!navigator.connection?.saveData)video.play().catch(()=>{});
            else video.pause();
        }), {threshold:.2});
        root.querySelectorAll('video').forEach(video=>{video.controls=false;video.removeAttribute('controls');video.muted=true;if(reduced.matches||navigator.connection?.saveData){video.removeAttribute('autoplay');video.pause();}videos.observe(video);});
        document.addEventListener('visibilitychange',()=>{if(document.hidden)root.querySelectorAll('video').forEach(video=>video.pause());});
    }
    // All navigation uses standard links, including the old clean detail routes.
    root.querySelectorAll('a[href]').forEach(link => {
        const href=link.getAttribute('href');const detail=href.match(/^\.\/property-listing\/([a-z0-9-]+)\/?$/);
        if(detail)link.href='./property.php?slug='+encodeURIComponent(detail[1]);
        if(href==='./properties.php')link.href='./property-listing';
        if(href==='./sign-in')link.href='./admin/login';
    });
})();
