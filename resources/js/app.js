import.meta.glob([
  '../images/**',
  '../fonts/**',
]);

// Open accordion item targeted by URL hash
function openHashTarget() {
  const hash = location.hash;
  if (!hash) return;

  const target = document.querySelector(hash);
  if (target && target.tagName === 'DETAILS' && !target.open) {
    target.open = true;
  }
}

openHashTarget();
window.addEventListener('hashchange', openHashTarget);
