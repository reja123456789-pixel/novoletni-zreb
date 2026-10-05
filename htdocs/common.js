// Skupne funkcije za obe strani.

const CLOTHES = ['👗', '👔', '🧥', '👖', '👠', '🎩', '🧣', '👒', '🥿', '👚', '🩳', '🕶️', '🧦', '🎀', '👑', '🥳', '🎉', '✨'];

function spawnFloaters(n = 16) {
  const box = document.createElement('div');
  box.className = 'floaters';
  for (let i = 0; i < n; i++) {
    const s = document.createElement('span');
    s.textContent = CLOTHES[i % CLOTHES.length];
    s.style.left = Math.random() * 100 + 'vw';
    s.style.animationDuration = 14 + Math.random() * 18 + 's';
    s.style.animationDelay = -Math.random() * 30 + 's';
    s.style.fontSize = 1.6 + Math.random() * 2.2 + 'rem';
    box.appendChild(s);
  }
  document.body.prepend(box);
}

function confetti(n = 120) {
  const colors = ['#ff3d9a', '#ffd23f', '#2de2e6', '#7b2ff7', '#ff7a18', '#3ddc84'];
  for (let i = 0; i < n; i++) {
    const c = document.createElement('div');
    c.className = 'confetti';
    c.style.left = Math.random() * 100 + 'vw';
    c.style.background = colors[i % colors.length];
    c.style.animationDuration = 2 + Math.random() * 2.5 + 's';
    c.style.animationDelay = Math.random() * 0.6 + 's';
    c.style.borderRadius = Math.random() > 0.5 ? '50%' : '2px';
    document.body.appendChild(c);
    setTimeout(() => c.remove(), 5500);
  }
}

async function api(action, body) {
  const res = await fetch('api.php?a=' + action, {
    credentials: 'same-origin',
    method: body === undefined ? 'GET' : 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: body === undefined ? undefined : JSON.stringify(body),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw Object.assign(new Error(data.error || 'Napaka.'), { status: res.status });
  return data;
}

function fmtDate(s) {
  if (!s) return '—';
  return new Date(s).toLocaleString('sl-SI', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function esc(s) {
  return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

const GENDER_EMOJI = { 'moški': '🙋‍♂️', 'ženska': '🙋‍♀️', 'drugo': '🙋' };
