(() => {
  'use strict';
  const root = document.querySelector('.agents-page');
  if (!root) return;
  const dialogs = [...root.querySelectorAll('dialog.agent-profile')];
  let previousFocus = null;
  let bodyOverflow = '';
  const openProfile = (dialog, trigger) => {
    if (!dialog || dialog.open || typeof dialog.showModal !== 'function') return false;
    dialogs.forEach(item => { if (item.open) item.close(); });
    previousFocus = trigger || document.activeElement;
    bodyOverflow = document.body.style.overflow;
    dialog.showModal();
    document.body.style.overflow = 'hidden';
    dialog.querySelector('[data-agent-close]')?.focus({ preventScroll: true });
    return true;
  };
  root.querySelectorAll('[data-agent-open]').forEach(trigger => {
    trigger.addEventListener('click', event => {
      const dialog = document.getElementById(trigger.dataset.agentOpen);
      if (openProfile(dialog, trigger)) event.preventDefault();
    });
  });
  dialogs.forEach(dialog => {
    dialog.querySelector('[data-agent-close]')?.addEventListener('click', () => dialog.close());
    let startedOnBackdrop = false;
    const onBackdrop = event => {
      const bounds = dialog.getBoundingClientRect();
      return event.target === dialog && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom);
    };
    dialog.addEventListener('pointerdown', event => { startedOnBackdrop = onBackdrop(event); });
    dialog.addEventListener('pointerup', event => {
      if (startedOnBackdrop && onBackdrop(event)) dialog.close();
      startedOnBackdrop = false;
    });
    dialog.addEventListener('close', () => {
      document.body.style.overflow = bodyOverflow;
      if (previousFocus?.isConnected) previousFocus.focus({ preventScroll: true });
      previousFocus = null;
    });
  });
  // The list remains visible if motion is disabled or JavaScript is unavailable.
  if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('agent-enter');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.08 });
    root.querySelectorAll('.agent-card').forEach(card => observer.observe(card));
  }
  if (window.location.hash.startsWith('#agent-')) {
    const card = document.getElementById(window.location.hash.slice(1));
    const trigger = card?.querySelector('[data-agent-open]');
    if (trigger) openProfile(document.getElementById(trigger.dataset.agentOpen), trigger);
  }
})();
