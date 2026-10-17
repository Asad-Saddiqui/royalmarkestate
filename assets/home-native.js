/* Local interactions. No remote runtime or client-side page replacement. */
(() => {
    'use strict';
    const root = document.getElementById('main');
    if (!root) return;
    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    const reveal = [...root.querySelectorAll('[data-framer-appear-id], [style*="will-change:transform"], .rm-native-heading, .rm-home-gallery-grid > a, .rm-service-card, .rm-home-stories article, .rm-home-counts a, .home-property-inventory .listing-card, .rm-home-insight-card, .rm-home-insights-heading, .rm-home-insights-empty, .rm-benefits-points article, .rm-home-journey > img')].filter(el => !el.closest('.framer-nzj1qu-container') && !el.closest('.rm-home-projects'));
    if (!reduced.matches && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver(entries => entries.forEach(entry => {
            if (entry.isIntersecting) { entry.target.classList.add('rm-revealed'); observer.unobserve(entry.target); }
        }), {threshold: .06, rootMargin: '0px 0px -20px 0px'});
        reveal.forEach(el => {
            if(el.matches('.listing-card,.rm-home-insight-card,.rm-home-gallery-grid > a,.rm-home-stories article,.rm-home-counts a,.rm-benefits-points article')) {
                const order=[...el.parentElement.children].indexOf(el);
                el.style.setProperty('--rm-reveal-delay', ((order % 3) * 85)+'ms');
            }
            el.classList.add('rm-reveal'); observer.observe(el);
        });
        reduced.addEventListener('change', () => {if(reduced.matches){reveal.forEach(el=>el.classList.add('rm-revealed'));observer.disconnect();}});
    }
    // Animate only the visible hero. The original heading remains the accessible label.
    const hero=document.body.classList.contains('has-featured-project-hero')?null:root.querySelector('[data-rm-hero], [data-framer-name="Hero Section"]');
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

// Featured projects: shared-size slides prevent layout jumps between projects.
(() => {
    'use strict';
    const slider=document.querySelector('[data-rm-featured-slider]');
    if(!slider)return;
    const slides=[...slider.querySelectorAll('[data-rm-featured-slide]')];
    if(slides.length<2)return;
    const dots=[...slider.querySelectorAll('[data-rm-featured-dot]')];
    const toggle=slider.querySelector('[data-rm-featured-toggle]');
    const live=slider.querySelector('.rm-featured-hero-slides');
    const reduced=matchMedia('(prefers-reduced-motion: reduce)');
    let current=0,timer,paused=reduced.matches,visible=true,startX=null,startY=null;
    let changing=false,request=0;
    const imageReady=new Map();
    function prepare(index){
        if(imageReady.has(index))return imageReady.get(index);
        const img=slides[index].querySelector('.rm-featured-hero-image');
        const ready=(async()=>{
            if(!img)return true;
            img.loading='eager';
            try{
                if(img.decode)await img.decode();
                else if(!img.complete)await new Promise((resolve,reject)=>{img.addEventListener('load',resolve,{once:true});img.addEventListener('error',reject,{once:true});});
                return img.naturalWidth>0;
            }catch{return false;}
        })();
        imageReady.set(index,ready);return ready;
    }
    slides.forEach((slide,index)=>{slide.hidden=false;slide.inert=index!==0;});
    function schedule(){
        clearTimeout(timer);
        if(!paused&&!document.hidden&&visible&&!slider.contains(document.activeElement))timer=setTimeout(()=>show(current+1),6000);
    }
    async function show(index){
        if(changing)return;
        const target=(index+slides.length)%slides.length;
        if(target===current){prepare((current+1)%slides.length);schedule();return;}
        clearTimeout(timer);
        const token=++request;
        const ready=await prepare(target);
        if(token!==request)return;
        if(!ready){schedule();return;}
        changing=true;
        const outgoing=slides[current];
        outgoing.classList.add('is-leaving');
        current=target;
        slides.forEach((slide,i)=>{
            slide.classList.toggle('is-active',i===current);
            slide.inert=i!==current;
            slide.setAttribute('aria-hidden',String(i!==current));
        });
        dots.forEach((dot,i)=>dot.setAttribute('aria-current',String(i===current)));
        prepare((current+1)%slides.length);
        setTimeout(()=>{outgoing.classList.remove('is-leaving');changing=false;schedule();},reduced.matches?0:1050);
    }
    function updateToggle(){
        toggle.setAttribute('aria-label',paused?'Play slideshow':'Pause slideshow');
        toggle.setAttribute('aria-pressed',String(paused));
        toggle.firstElementChild.textContent=paused?'\u25B6':'\u2161';
        live.setAttribute('aria-live',paused?'polite':'off');
        schedule();
    }
    slider.querySelector('[data-rm-featured-prev]').addEventListener('click',()=>show(current-1));
    slider.querySelector('[data-rm-featured-next]').addEventListener('click',()=>show(current+1));
    dots.forEach((dot,i)=>dot.addEventListener('click',()=>show(i)));
    toggle.addEventListener('click',()=>{paused=!paused;updateToggle();});
    slider.addEventListener('keydown',event=>{
        if(event.target.matches('input,textarea,select'))return;
        if(event.key==='ArrowRight'||event.key==='ArrowLeft'){event.preventDefault();show(current+(event.key==='ArrowRight'?1:-1));}
    });
    slider.addEventListener('pointerdown',event=>{if(event.isPrimary){startX=event.clientX;startY=event.clientY;}});
    slider.addEventListener('pointerup',event=>{
        if(startX===null)return;
        const dx=event.clientX-startX,dy=event.clientY-startY;
        if(Math.abs(dx)>60&&Math.abs(dx)>Math.abs(dy)*1.4)show(current+(dx<0?1:-1));
        startX=null;
    });
    slider.addEventListener('pointercancel',()=>{startX=null;});
    slider.addEventListener('click',event=>{
        if(startX!==null)startX=null;
    });
    slider.addEventListener('focusin',()=>clearTimeout(timer));
    slider.addEventListener('focusout',()=>setTimeout(schedule,0));
    document.addEventListener('visibilitychange',schedule);
    reduced.addEventListener('change',()=>{paused=reduced.matches;updateToggle();});
    if('IntersectionObserver' in window)new IntersectionObserver(entries=>{visible=entries[0].isIntersecting;schedule();},{threshold:.1}).observe(slider);
    show(0);updateToggle();
})();
