(() => {
    'use strict';
    const section=document.getElementById('home-map');
    if(!section)return;
    const status=section.querySelector('[data-map-status]');
    if(!window.L){status.textContent='The map could not load. You can still explore the listings below.';return;}
    const items=JSON.parse(document.getElementById('rm-map-data').textContent);
    const map=L.map('rm-listings-map',{scrollWheelZoom:false}).setView([29.8,70],5);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'}).addTo(map);
    const layer=L.layerGroup().addTo(map),markers=new Map();
    let kind='all';
    const city=section.querySelector('[data-map-city]');
    function render(){
        layer.clearLayers();markers.clear();
        const visible=items.filter(item=>(kind==='all'||item.kind===kind)&&(!city.value||item.city===city.value));
        const keys=new Set(visible.map(item=>item.kind+':'+item.slug));
        section.querySelectorAll('[data-map-result]').forEach(row=>row.hidden=!keys.has(row.dataset.mapResult));
        const groups=new Map();
        visible.forEach(item=>{const key=item.lat+','+item.lng;if(!groups.has(key))groups.set(key,[]);groups.get(key).push(item);});
        const bounds=[];
        groups.forEach(group=>{
            const first=group[0],types=new Set(group.map(item=>item.kind));
            const color=types.size>1?'mixed':first.kind;
            const icon=L.divIcon({className:'rm-map-pin '+color,html:'<span>'+group.length+'</span>',iconSize:[34,34],iconAnchor:[17,17]});
            const popup=document.createElement('div');popup.className='rm-map-popup';
            const heading=document.createElement('div');heading.className='rm-map-popup-heading';
            const title=document.createElement('strong');title.textContent=first.city;
            const count=document.createElement('span');count.textContent=group.length+' '+(group.length===1?'listing':'listings');heading.append(title,count);popup.append(heading);
            const cards=document.createElement('div');cards.className='rm-map-popup-cards';
            group.forEach(item=>{
                const card=document.createElement('article');card.className='rm-map-popup-card';
                if(item.image){const photo=document.createElement('a');photo.href=item.url;photo.className='rm-map-popup-photo';photo.setAttribute('aria-label','View '+item.title);const img=document.createElement('img');img.src=item.image;img.alt='';img.width=96;img.height=106;img.loading='lazy';img.addEventListener('error',()=>photo.remove(),{once:true});photo.append(img);card.append(photo);}
                const body=document.createElement('div');body.className='rm-map-popup-card-body';
                const type=document.createElement('span');type.className='rm-map-kind '+item.kind;type.textContent=item.kind==='project'?'PROJECT':'PROPERTY';
                const name=document.createElement('h3');const link=document.createElement('a');link.href=item.url;link.textContent=item.title;name.append(link);
                const location=document.createElement('p');location.textContent=item.city;
                const price=document.createElement('strong');price.className='rm-map-popup-price';price.textContent=item.price;
                const action=document.createElement('a');action.href=item.url;action.className='rm-map-popup-action';action.textContent='View details →';
                body.append(type,name,location,price,action);card.append(body);cards.append(card);
            });popup.append(cards);
            if(group.some(item=>item.approximate)){const note=document.createElement('small');note.textContent='Approximate city location';popup.append(note);}
            const marker=L.marker([first.lat,first.lng],{icon,title:group.length+' listings in '+first.city}).bindPopup(popup,{className:'rm-listing-popup',maxWidth:360,minWidth:230,autoPanPadding:[16,16]}).addTo(layer);
            group.forEach(item=>markers.set(item.kind+':'+item.slug,marker));bounds.push([first.lat,first.lng]);
        });
        if(bounds.length)map.fitBounds(bounds,{padding:[40,40],maxZoom:bounds.length===1?12:10,animate:false});
        status.textContent=visible.length+' '+(visible.length===1?'listing':'listings')+' across '+groups.size+' map locations';
    }
    section.querySelectorAll('[data-map-filter]').forEach(button=>button.addEventListener('click',()=>{kind=button.dataset.mapFilter;section.querySelectorAll('[data-map-filter]').forEach(other=>other.setAttribute('aria-pressed',String(other===button)));render();}));
    city.addEventListener('change',render);
    section.querySelector('[data-map-reset]').addEventListener('click',()=>{city.value='';section.querySelector('[data-map-filter="all"]').click();});
    section.querySelectorAll('[data-map-locate]').forEach(button=>button.addEventListener('click',()=>{const marker=markers.get(button.dataset.mapLocate);if(marker){map.setView(marker.getLatLng(),12,{animate:!matchMedia('(prefers-reduced-motion: reduce)').matches});marker.openPopup();}}));
    if('ResizeObserver' in window)new ResizeObserver(()=>map.invalidateSize({pan:false})).observe(section);
    render();
})();
