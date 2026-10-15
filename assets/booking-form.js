(() => {
  'use strict';
  let toastTimer;
  const notify = (message, isError) => {
    document.querySelector('.booking-toast')?.remove();
    window.clearTimeout(toastTimer);
    const toast = document.createElement('div');
    toast.className = 'booking-toast';
    toast.dataset.error = String(isError);
    toast.setAttribute('role', isError ? 'alert' : 'status');
    const text = document.createElement('span');
    text.textContent = message;
    const close = document.createElement('button');
    close.type = 'button';
    close.setAttribute('aria-label', 'Dismiss notification');
    close.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>';
    close.addEventListener('click', () => toast.remove());
    toast.append(text, close);
    document.body.append(toast);
    toastTimer = window.setTimeout(() => toast.remove(), isError ? 8500 : 6500);
  };
  document.addEventListener('submit', async event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.matches('[data-booking-form]')) return;
    if (!window.fetch || !window.FormData) return;
    event.preventDefault();
    if (form.dataset.submitting === 'true' || !form.reportValidity()) return;
    const panel = form.closest('.booking-card');
    const button = form.querySelector('.booking-submit');
    const label = button?.querySelector('span');
    const initialLabel = label?.textContent || 'Send request';
    form.dataset.submitting = 'true';
    form.setAttribute('aria-busy', 'true');
    if (button) { button.disabled = true; button.setAttribute('aria-busy', 'true'); }
    if (label) label.textContent = 'Saving request…';
    try {
      const url = new URL(form.action, window.location.href);
      url.hash = '';
      const response = await fetch(url.href, {
        method: 'POST', body: new FormData(form), credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
      });
      if (!(response.headers.get('Content-Type') || '').includes('application/json')) throw new Error('The request could not be saved. Please refresh the page and try again.');
      const result = await response.json();
      if (typeof result.html !== 'string' || !panel) throw new Error('Please refresh the page and try again.');
      const parsed = new DOMParser().parseFromString(result.html, 'text/html');
      const replacement = parsed.querySelector('.booking-card');
      if (!replacement || replacement.id !== panel.id) throw new Error('Please refresh the page and try again.');
      panel.replaceWith(replacement);
      replacement.querySelector(result.ok ? '.booking-success' : '.booking-error')?.focus({ preventScroll: true });
      notify(result.message || (result.ok ? 'Your request has been saved.' : 'Please check your request.'), !result.ok);
      document.dispatchEvent(new CustomEvent('booking-form-updated', { detail: { panel: replacement, ok: !!result.ok } }));
    } catch (error) {
      notify(error instanceof Error ? error.message : 'Unable to save your request. Please try again.', true);
    } finally {
      if (form.isConnected) {
        delete form.dataset.submitting;
        form.removeAttribute('aria-busy');
        if (button) { button.disabled = false; button.removeAttribute('aria-busy'); }
        if (label) label.textContent = initialLabel;
      }
    }
  });
})();
