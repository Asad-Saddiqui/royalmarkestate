(() => {
    'use strict';
    function initialise(root = document) {
        root.querySelectorAll('[data-booking-captcha]').forEach(widget => {
            if (widget.dataset.ready === 'true') return;
            widget.dataset.ready = 'true';
            const button = widget.querySelector('[data-captcha-refresh]');
            const feedback = widget.querySelector('[data-captcha-feedback]');
            const input = widget.querySelector('[data-captcha-answer]');
            const id = widget.querySelector('[name="captcha_id"]');
            const image = widget.querySelector('[data-captcha-image]');
            button.addEventListener('click', async () => {
                if (button.disabled) return;
                button.disabled = true;
                widget.setAttribute('aria-busy', 'true');
                feedback.textContent = 'Getting a new code…';
                try {
                    const body = new FormData();
                    body.set('scope', widget.dataset.scope);
                    body.set('captcha_csrf', widget.dataset.captchaCsrf);
                    body.set('captcha_id', id.value);
                    const response = await fetch(widget.dataset.endpoint, {method:'POST',body,credentials:'same-origin',headers:{'Accept':'application/json'}});
                    const result = await response.json();
                    if (!response.ok || !/^[a-f0-9]{32}$/.test(result.id)) throw new Error(result.error || 'The code could not refresh. Try again.');
                    id.value = result.id;
                    image.src = result.image;
                    input.value = '';
                    feedback.textContent = 'New code ready.';
                    input.focus({preventScroll:true});
                } catch (error) {
                    feedback.textContent = error.message || 'The code could not refresh. Try again.';
                } finally {
                    button.disabled = false;
                    widget.removeAttribute('aria-busy');
                }
            });
            image.addEventListener('error', () => {feedback.textContent = 'Code expired or unavailable. Use the refresh button for a new code.';});
        });
    }
    initialise();
    new MutationObserver(records => {
        if (records.some(record => [...record.addedNodes].some(node => node.nodeType === 1))) initialise();
    }).observe(document.body, {childList:true,subtree:true});
})();
