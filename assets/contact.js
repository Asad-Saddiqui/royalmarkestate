(() => {
  'use strict';
  document.addEventListener('submit', async event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.matches('[data-contact-form]') || !window.fetch || !window.FormData) return;
    event.preventDefault();
    if (form.dataset.submitting === 'true' || !form.reportValidity()) return;
    const panel = form.closest('.contact-message-panel');
    const button = form.querySelector('.contact-submit');
    const label = button?.querySelector('span');
    const networkFeedback = form.querySelector('.contact-network-feedback');
    form.dataset.submitting = 'true';
    form.setAttribute('aria-busy', 'true');
    if (button) button.disabled = true;
    if (label) label.textContent = 'Sending message...';
    if (networkFeedback) networkFeedback.hidden = true;
    try {
      const url = new URL(form.action, location.href);
      url.hash = '';
      const response = await fetch(url, {method:'POST', body:new FormData(form), credentials:'same-origin', headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});
      if (!(response.headers.get('Content-Type') || '').includes('application/json')) throw new Error('Your message could not be confirmed. Please try again shortly.');
      const result = await response.json();
      if (typeof result.html !== 'string' || !panel) throw new Error('Your message could not be confirmed. Please refresh the page and try again.');
      const replacement = new DOMParser().parseFromString(result.html, 'text/html').querySelector('.contact-message-panel');
      if (!replacement || replacement.id !== panel.id) throw new Error('Your message could not be confirmed. Please refresh the page and try again.');
      panel.replaceWith(replacement);
      const feedback = replacement.querySelector('.contact-feedback');
      feedback?.focus({preventScroll:true});
      if (feedback) {
        const bounds = feedback.getBoundingClientRect();
        if (bounds.top < 110 || bounds.bottom > innerHeight) feedback.scrollIntoView({block:'center', behavior:matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'});
      }
    } catch (error) {
      if (networkFeedback) {
        networkFeedback.textContent = error instanceof Error ? error.message : 'Unable to save your message. Please try again.';
        networkFeedback.hidden = false;
        networkFeedback.focus({preventScroll:true});
      }
    } finally {
      if (form.isConnected) {
        delete form.dataset.submitting;
        form.removeAttribute('aria-busy');
        if (button) button.disabled = false;
        if (label) label.textContent = 'Send message';
      }
    }
  });
})();
