/* Local interactions. No remote runtime or client-side page replacement. */
(() => {
    'use strict';
    const root = document.getElementById('main');
    if (!root) return;
    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    const reveal = [...root.querySelectorAll('[data-framer-appear-id], [style*="will-change:transform"], .rm-native-heading, .rm-home-gallery-grid > a, .rm-service-card, .rm-home-stories article, .rm-home-counts a, .home-property-inventory .listing-card, .rm-home-project-card, .rm-home-projects-heading, .rm-home-projects-empty, .rm-benefits-points article, .rm-home-journey > img')].filter(el => !el.closest('.framer-nzj1qu-container'));
    if (!reduced.matches && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver(entries => entries.forEach(entry => {
            if (entry.isIntersecting) { entry.target.classList.add('rm-revealed'); observer.unobserve(entry.target); }
        }), {threshold: .06, rootMargin: '0px 0px -20px 0px'});
        reveal.forEach(el => {
            if(el.matches('.listing-card,.rm-home-project-card,.rm-home-gallery-grid > a,.rm-home-stories article,.rm-home-counts a,.rm-benefits-points article')) {
                const order=[...el.parentElement.children].indexOf(el);
                el.style.setProperty('--rm-reveal-delay', ((order % 3) * 85)+'ms');
            }
            el.classList.add('rm-reveal'); observer.observe(el);
        });
        reduced.addEventListener('change', () => {if(reduced.matches){reveal.forEach(el=>el.classList.add('rm-revealed'));observer.disconnect();}});
    }
    // Animate only the visible hero. The original heading remains the accessible label.
    const hero=root.querySelector('[data-rm-hero], [data-framer-name="Hero Section"]');
    const heading=hero?.querySelector('.framer-16xkqm6 h1:last-of-type')||hero?.querySelectorAll('h1')[1];
    let heroVisible=true;
    let timer=null;
    let typingEnabled=false;
    let phraseIndex=0;
    let character=0;
    let deleting=false;
    let output=null;
    let caret=null;
    const phrases=['Feels Like Home','Fits Your Life','Starts Your Story'];
    const canAnimate=()=>heroVisible&&!document.hidden&&!reduced.matches;
    function scheduleTyping(delay){clearTimeout(timer);if(canAnimate()&&typingEnabled)timer=setTimeout(typeStep,delay);}
    function typeStep(){
        if(!canAnimate()||!output)return;
        const phrase=phrases[phraseIndex];
        character+=deleting?-1:1;
        output.firstChild.textContent=phrase.slice(0,Math.max(0,character));
        if(!deleting&&character>=phrase.length){deleting=true;scheduleTyping(2700);}
        else if(deleting&&character<=0){deleting=false;phraseIndex=(phraseIndex+1)%phrases.length;scheduleTyping(400);}
        else scheduleTyping(deleting?38:85);
    }
    function updateHeroMotion(){
        hero?.classList.toggle('rm-motion-paused',!canAnimate());
        clearTimeout(timer);
        if(!output)return;
        if(reduced.matches){output.firstChild.textContent=phrases[0];caret.classList.add('rm-caret-hidden');}
        else{caret.classList.remove('rm-caret-hidden');scheduleTyping(character===0&&!deleting?160:700);}
    }
    if(heading){
        heading.setAttribute('aria-label',heading.textContent.trim());
        const line=document.createElement('span');line.className='rm-typed-line';line.setAttribute('aria-hidden','true');
        const reserve=document.createElement('span');reserve.className='rm-type-reserve';reserve.textContent=phrases[2];
        output=document.createElement('span');output.className='rm-type-output';output.append(document.createTextNode(reduced.matches?phrases[0]:''));
        caret=document.createElement('span');caret.className='rm-type-caret';output.append(caret);
        line.append(reserve,output);heading.replaceChildren(line);
        character=0;deleting=false;typingEnabled=true;scheduleTyping(200);
    }
    if(hero&&'IntersectionObserver' in window){
        new IntersectionObserver(entries=>{heroVisible=entries[0].isIntersecting;updateHeroMotion();},{threshold:0}).observe(hero);
    }
    document.addEventListener('visibilitychange',updateHeroMotion);
    reduced.addEventListener('change',()=>{if(!reduced.matches){phraseIndex=0;character=phrases[0].length;deleting=true;}updateHeroMotion();});
    updateHeroMotion();
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
