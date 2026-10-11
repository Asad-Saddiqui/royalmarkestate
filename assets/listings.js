(() => {
 const filterButton=document.querySelector('.filter-mobile-button');
 const filters=document.getElementById('listing-filter');
 if(filterButton&&filters)filterButton.addEventListener('click',()=>{const open=filterButton.getAttribute('aria-expanded')!=='true';filterButton.setAttribute('aria-expanded',String(open));filters.classList.toggle('is-open',open)});
 const city=document.getElementById('listing-map-city'),frame=document.getElementById('listing-map-frame');
 if(city&&frame){
  // City-level overview only; the homepage does not supply exact property coordinates.
  const centers={'Ahmedabad':[23.0225,72.5714],'Mumbai':[19.076,72.8777],'New York':[40.7128,-74.006],'Bengaluru':[12.9716,77.5946],'Delhi':[28.6139,77.209],'Austin':[30.2672,-97.7431],'Miami':[25.7617,-80.1918],'Melbourne':[-37.8136,144.9631]};
  function update(){const center=centers[city.value];if(!center)return;const [lat,lon]=center;const url=new URL('https://www.openstreetmap.org/export/embed.html');url.searchParams.set('bbox',[lon-.09,lat-.06,lon+.09,lat+.06].join(','));url.searchParams.set('layer','mapnik');url.searchParams.set('marker',lat+','+lon);frame.src=url.href;frame.title=city.value+' city overview map';}
  city.addEventListener('change',update);update();
 }
})();
