(() => {
  'use strict';
  const page = document.querySelector('.listing-page');
  const form = document.getElementById('listing-filter-form');
  const filterButton = document.querySelector('.filter-mobile-button');
  const filters = document.getElementById('listing-filter');
  const mobile = window.matchMedia('(max-width: 767.98px)');
  if (page && filterButton && filters) {
    // The GET form stays visible when JavaScript is unavailable.
    page.classList.add('has-filter-toggle');
    const setOpen = (open, focusButton = false) => {
      filterButton.setAttribute('aria-expanded', String(open));
      filters.classList.toggle('is-open', open);
      if (focusButton) filterButton.focus();
    };
    filterButton.addEventListener('click', () => setOpen(filterButton.getAttribute('aria-expanded') !== 'true'));
    filters.addEventListener('keydown', event => {
      if (event.key === 'Escape' && mobile.matches && filters.classList.contains('is-open')) {
        event.preventDefault();
        setOpen(false, true);
      }
    });
    mobile.addEventListener('change', () => setOpen(false));
  }

  const sort = document.getElementById('property-sort');
  if (sort && form) sort.addEventListener('change', () => form.requestSubmit());

  const city = document.getElementById('listing-map-city');
  const frame = document.getElementById('listing-map-frame');
  if (city && frame) city.addEventListener('change', () => {
    // A city query supports every location added through the backend.
    const url = new URL('https://www.google.com/maps');
    url.searchParams.set('q', city.value);
    url.searchParams.set('output', 'embed');
    frame.src = url.href;
    frame.title = city.value + ' city overview map';
  });
})();
