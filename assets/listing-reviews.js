(() => {
    'use strict';
    const pager = document.querySelector('[data-review-pagination]');
    if (!pager) return;
    const total = Number(pager.dataset.total);
    const endpoint = new URL(pager.dataset.url, document.baseURI);
    function wire(container, items, initial, maximum) {
        let loaded = initial;
        const button = container.querySelector('[data-review-more]');
        const status = container.querySelector('[data-review-status]');
        if (!button) return;
        button.addEventListener('click', async () => {
            button.disabled = true;
            button.textContent = 'Loading…';
            status.textContent = 'Loading reviews…';
            try {
                const url = new URL(endpoint);
                url.searchParams.set('reviews_offset', String(loaded));
                url.searchParams.set('reviews_limit', String(Math.min(10, maximum - loaded)));
                const response = await fetch(url, {headers: {Accept: 'application/json'}});
                if (!response.ok) throw new Error('Unable to load reviews. Please try again.');
                const result = await response.json();
                if (typeof result.html !== 'string' || !Number.isInteger(result.loaded)) throw new Error('Unable to load reviews. Please try again.');
                items.insertAdjacentHTML('beforeend', result.html);
                loaded += result.loaded;
                status.textContent = `Showing ${loaded} of ${total} reviews`;
                button.hidden = loaded >= maximum || result.loaded === 0;
            } catch (error) {
                status.textContent = error.message;
            } finally {
                button.disabled = false;
                button.textContent = 'Load more';
            }
        });
    }
    const items = document.querySelector('[data-review-items]');
    wire(pager, items, Number(pager.dataset.loaded), Math.min(50, total));
    const open = pager.querySelector('[data-review-all]');
    if (!open) return;
    let dialog;
    open.addEventListener('click', () => {
        if (!dialog) {
            dialog = document.createElement('dialog');
            dialog.className = 'listing-reviews-drawer';
            dialog.setAttribute('aria-labelledby', 'all-reviews-title');
            dialog.innerHTML = '<header><h2 id="all-reviews-title">All reviews</h2><button type="button" data-review-close aria-label="Close all reviews">&times;</button></header><div class="listing-reviews-drawer-body"><div class="listing-review-list" data-drawer-items></div><div class="listing-review-pagination"><p role="status" data-review-status></p><button type="button" class="button" data-review-more>Load more</button></div></div>';
            document.body.append(dialog);
            const drawerItems = dialog.querySelector('[data-drawer-items]');
            // Reuse the first page without copying the sidebar's current pagination state.
            [...items.children].slice(0, 10).forEach(item => drawerItems.append(item.cloneNode(true)));
            dialog.querySelector('[data-review-status]').textContent = `Showing 10 of ${total} reviews`;
            wire(dialog, drawerItems, 10, total);
            dialog.querySelector('[data-review-close]').addEventListener('click', () => dialog.close());
            dialog.addEventListener('click', event => {if (event.target === dialog) {const rect=dialog.getBoundingClientRect();if(event.clientX<rect.left||event.clientX>rect.right||event.clientY<rect.top||event.clientY>rect.bottom)dialog.close();}});
            dialog.addEventListener('close', () => {document.documentElement.classList.remove('reviews-drawer-open');open.focus({preventScroll:true});});
        }
        dialog.showModal();
        document.documentElement.classList.add('reviews-drawer-open');
    });
})();
