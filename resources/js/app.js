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

// Local time display (America/Los_Angeles)
const timeEl = document.getElementById('local-time');
if (timeEl) {
  const fmt = new Intl.DateTimeFormat('en-US', {
    timeZone: 'America/Los_Angeles',
    hour: 'numeric',
    minute: '2-digit',
    weekday: 'long',
    timeZoneName: 'short',
  });

  function updateTime() {
    const now = new Date();
    timeEl.textContent = 'Local time at business: ' + fmt.format(now);
    timeEl.dateTime = now.toISOString();
  }

  updateTime();
  setInterval(updateTime, 60000);
}
