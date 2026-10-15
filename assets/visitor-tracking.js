(() => {
    'use strict';
    const settings = document.getElementById('rm-visit-config');
    if (!settings || !window.crypto?.getRandomValues || !window.fetch) return;
    const config = JSON.parse(settings.textContent);
    const storageKey = 'royalmark_visitor_id';
    const uuid = () => {
        const b = crypto.getRandomValues(new Uint8Array(16));
        b[6] = b[6] & 15 | 64; b[8] = b[8] & 63 | 128;
        const h = [...b].map(x => x.toString(16).padStart(2, '0')).join('');
        return `${h.slice(0,8)}-${h.slice(8,12)}-${h.slice(12,16)}-${h.slice(16,20)}-${h.slice(20)}`;
    };
    let id;
    try { id = localStorage.getItem(storageKey); } catch {}
    if (!/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/.test(id || '')) id = uuid();
    const eventId = () => [...crypto.getRandomValues(new Uint8Array(16))].map(x => x.toString(16).padStart(2,'0')).join('');
    let sending = false;
    let recorded = false;
    let viewKey = eventId();
    async function record() {
        if (sending || recorded || document.visibilityState === 'hidden') return;
        sending = true;
        try {
            const body = new FormData();
            Object.entries({visitor_id:id,view_key:viewKey,kind:config.kind,key:config.key,csrf:config.csrf}).forEach(([key,value]) => body.set(key,value));
            const endpoint=new URL(config.endpoint,document.baseURI);
            let response = await fetch(endpoint,{method:'POST',body,credentials:'same-origin',keepalive:true});
            if(response.status===403){
                // A restored tab may hold a nonce from before login or logout.
                const tokenUrl=new URL(endpoint);tokenUrl.searchParams.set('action','token');
                const tokenResponse=await fetch(tokenUrl,{credentials:'same-origin',cache:'no-store'});
                if(!tokenResponse.ok)return;
                const token=await tokenResponse.json();
                if(typeof token.csrf!=='string')return;
                config.csrf=token.csrf;body.set('csrf',token.csrf);
                response=await fetch(endpoint,{method:'POST',body,credentials:'same-origin',keepalive:true});
            }
            if (!response.ok) return;
            const result = await response.json();
            if (typeof result.visitor_id !== 'string') return;
            id = result.visitor_id;
            try { localStorage.setItem(storageKey,id); } catch {}
            recorded = true;
            if (config.kind === 'blog') document.querySelectorAll('[data-blog-view-count]').forEach(node => {
                if (!node.dataset.blogSlug || node.dataset.blogSlug === config.key) node.textContent = Number(result.stats.views).toLocaleString();
            });
            document.dispatchEvent(new CustomEvent('royalmark-view-recorded',{detail:{kind:config.kind,key:config.key,stats:result.stats}}));
        } catch {
            // View reporting must never interrupt the visitor's page.
        } finally { sending = false; }
    }
    record();
    document.addEventListener('visibilitychange',record);
    window.addEventListener('online',record);
    window.addEventListener('pageshow',event => {if (event.persisted) {recorded=false;viewKey=eventId();record();}});
})();
