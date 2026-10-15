(() => {
    'use strict';
    document.querySelectorAll('[data-blog-video]').forEach(video => {
        const button = video.querySelector('button');
        if (!button || !/^[a-zA-Z0-9_-]{11}$/.test(video.dataset.blogVideo)) return;
        button.addEventListener('click', () => {
            const frame = document.createElement('iframe');
            frame.src = 'https://www.youtube-nocookie.com/embed/' + video.dataset.blogVideo + '?autoplay=1&rel=0';
            frame.title = button.getAttribute('aria-label') || 'YouTube video';
            frame.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
            frame.allowFullscreen = true;
            frame.referrerPolicy = 'strict-origin-when-cross-origin';
            video.replaceChildren(frame);
            frame.focus({preventScroll:true});
        });
    });
    const form = document.querySelector('[data-blog-comment-form]');
    if (form) {
        let busy = false;
        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (busy || !form.reportValidity()) return;
            busy = true;
            const submit = form.querySelector('button[type="submit"]');
            const feedback = form.querySelector('.journal-comment-feedback');
            const original = submit.innerHTML;
            submit.disabled = true;
            submit.textContent = 'Sending comment...';
            form.setAttribute('aria-busy', 'true');
            feedback.hidden = true;
            try {
                const response = await fetch(form.action, {method:'POST', body:new FormData(form), credentials:'same-origin', headers:{Accept:'application/json'}});
                const result = await response.json();
                if (result.captcha && /^[a-f0-9]{32}$/.test(result.captcha.id)) {
                    form.querySelector('[name="captcha_id"]').value = result.captcha.id;
                    form.querySelector('[data-captcha-image]').src = './booking-captcha.php?action=image&id=' + result.captcha.id;
                    form.querySelector('[data-captcha-answer]').value = '';
                    form.querySelector('[data-captcha-feedback]').textContent = 'A new security code is ready.';
                }
                feedback.textContent = result.message || 'Your comment could not be saved. Please try again.';
                feedback.className = 'journal-comment-feedback ' + (response.ok && result.ok ? 'is-success' : 'is-error');
                if (response.ok && result.ok) form.querySelector('[name="body"]').value = '';
            } catch (_) {
                feedback.textContent = 'We could not confirm your comment was received. Check your connection before trying again.';
                feedback.className = 'journal-comment-feedback is-error';
            } finally {
                busy = false;
                submit.disabled = false;
                submit.innerHTML = original;
                form.removeAttribute('aria-busy');
                feedback.hidden = false;
                feedback.focus({preventScroll:true});
            }
        });
    }
    const dialog = document.querySelector('.journal-photo-dialog');
    if (!dialog || typeof dialog.showModal !== 'function') return;
    const sources = [];
    const triggers = [...document.querySelectorAll('[data-blog-photo]')];
    triggers.forEach(button => {if (!sources.some(photo => photo.src === button.dataset.blogPhoto)) sources.push({src:button.dataset.blogPhoto, label:button.dataset.photoLabel || 'Article photo'});});
    if (!sources.length) return;
    const large = dialog.querySelector('[data-blog-photo-large]');
    const previous = dialog.querySelector('[data-blog-photo-prev]');
    const next = dialog.querySelector('[data-blog-photo-next]');
    const caption = dialog.querySelector('[data-blog-photo-caption]');
    const counter = dialog.querySelector('[data-blog-photo-counter]');
    let current = 0;
    let loading = 0;
    let returnFocus = null;
    let originalOverflow = '';
    function show(index) {
        current = (index + sources.length) % sources.length;
        const request = ++loading;
        const photo = sources[current];
        const image = new Image();
        dialog.setAttribute('aria-busy', 'true');
        caption.textContent = photo.label;
        counter.textContent = (current + 1) + ' / ' + sources.length;
        previous.hidden = next.hidden = sources.length < 2;
        image.onload = () => {
            if (request !== loading) return;
            large.src = image.src;
            large.alt = photo.label;
            dialog.removeAttribute('aria-busy');
        };
        image.onerror = () => {
            if (request !== loading) return;
            large.removeAttribute('src');
            large.alt = 'This photo is currently unavailable.';
            dialog.removeAttribute('aria-busy');
        };
        image.src = photo.src;
    }
    triggers.forEach(button => button.addEventListener('click', () => {
        returnFocus = button;
        originalOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        show(sources.findIndex(photo => photo.src === button.dataset.blogPhoto));
        dialog.showModal();
    }));
    dialog.querySelector('[data-blog-photo-close]').addEventListener('click', () => dialog.close());
    previous.addEventListener('click', () => show(current - 1));
    next.addEventListener('click', () => show(current + 1));
    dialog.addEventListener('keydown', event => {
        if (event.key === 'ArrowLeft') {event.preventDefault();show(current - 1);}
        if (event.key === 'ArrowRight') {event.preventDefault();show(current + 1);}
    });
    dialog.addEventListener('click', event => {if (event.target === dialog) {const rect=dialog.getBoundingClientRect();if(event.clientX<rect.left||event.clientX>rect.right||event.clientY<rect.top||event.clientY>rect.bottom)dialog.close();}});
    dialog.addEventListener('close', () => {
        loading++;
        dialog.removeAttribute('aria-busy');
        document.body.style.overflow = originalOverflow;
        returnFocus?.focus({preventScroll:true});
    });
})();
