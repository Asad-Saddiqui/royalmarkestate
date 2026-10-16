(() => {
  'use strict';
  const root = document.querySelector('.company-about');
  if (!root || root.dataset.aboutScrollReady) return;
  root.dataset.aboutScrollReady = 'true';
  const sections = Array.from(root.querySelectorAll('.about-reveal'));
  const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
  if (motion.matches || !('IntersectionObserver' in window)) return;
  let observer;
  const revealAll = () => {
    observer?.disconnect();
    sections.forEach(section => section.classList.remove('is-about-pending'));
  };
  try {
    observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        entry.target.classList.remove('is-about-pending');
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.06, rootMargin: '0px 0px -30px 0px' });
    sections.forEach(section => {
      // Keep immediately visible content readable before any observer callback.
      if (section.getBoundingClientRect().top < window.innerHeight - 30) return;
      section.classList.add('is-about-pending');
      observer.observe(section);
    });
    motion.addEventListener('change', event => {
      if (event.matches) revealAll();
    });
    window.addEventListener('pageshow', event => {
      if (event.persisted) revealAll();
    });
  } catch {
    revealAll();
  }
})();
