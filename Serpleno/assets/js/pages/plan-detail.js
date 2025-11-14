import { supabase } from '../lib/supabaseClient.js';
import { getCurrentProfile } from '../lib/session.js';

const params = new URLSearchParams(window.location.search);
const planKey = params.get('plan') || 'gratuito';
const titleEl = document.querySelector('[data-plan-name]');
const ctaEl = document.querySelector('[data-plan-cta]');
const featuresEl = document.querySelector('[data-plan-features]');
const averageEl = document.querySelector('[data-plan-average]');
const totalEl = document.querySelector('[data-plan-total]');
const barsEl = document.querySelector('[data-plan-bars]');
const pieEl = document.querySelector('[data-plan-pie]');
const yesEl = document.querySelector('[data-plan-yes]');
const noEl = document.querySelector('[data-plan-no]');
const commentsList = document.querySelector('[data-plan-comments]');
const emptyMsg = document.querySelector('[data-plan-empty]');
const form = document.querySelector('[data-plan-feedback]');
const successBox = document.querySelector('[data-plan-feedback-success]');
const errorBox = document.querySelector('[data-plan-feedback-error]');

(async function init(){
  const profile = await getCurrentProfile();
  const plan = await fetchPlan(planKey);
  if (!plan) {
    window.location.href = 'plans.html';
    return;
  }
  renderHeader(plan);
  renderCTA(plan, profile);
  const [features, feedback] = await Promise.all([
    fetchFeatures(planKey),
    fetchFeedback(planKey)
  ]);
  renderFeatures(features);
  renderFeedback(feedback);
  if (form) {
    form.addEventListener('submit', (event) => handleFeedbackSubmit(event, profile));
  }
})();

async function fetchPlan(slug) {
  const { data } = await supabase.from('planes').select('*').eq('slug', slug).maybeSingle();
  if (data) return data;
  const fallback = {
    gratuito: { slug: 'gratuito', nombre: 'Plan Gratis' },
    silver: { slug: 'silver', nombre: 'Plan Silver' },
    premium: { slug: 'premium', nombre: 'Plan Premium' }
  };
  return fallback[slug];
}

async function fetchFeatures(slug) {
  const { data, error } = await supabase
    .from('caracteristicas_plan')
    .select('*')
    .contains('planes', [slug]);
  if (error || !data?.length) {
    const base = {
      gratuito: ['Contenido estándar','Eventos abiertos','Comunidad de lectura'],
      silver: ['Contenido estándar','Eventos abiertos','Comunidad de lectura','Gestión de perfiles','Recordatorios','Eventos exclusivos','Sesiones con expertos','Plan personalizado','Coach grupal','Estadísticas'],
      premium: ['Contenido estándar','Eventos abiertos','Comunidad de lectura','Gestión de perfiles','Recordatorios','Eventos exclusivos','Sesiones con expertos','Plan personalizado','Coach grupal','Estadísticas','Coach personalizado','Contenido premium']
    };
    return base[slug] || base.gratuito;
  }
  return data.map((row) => row.nombre);
}

async function fetchFeedback(slug) {
  const { data, error } = await supabase
    .from('notificaciones')
    .select('id, datos, created_at')
    .eq('tipo', 'plan_feedback')
    .eq('datos->>plan', slug)
    .order('created_at', { ascending: false });
  if (error || !data) return [];
  return data.map((row) => ({
    rating: Number(row.datos?.rating) || 5,
    recommend: Boolean(row.datos?.recommend),
    comment: row.datos?.comment || '',
    created_at: row.created_at
  }));
}

function renderHeader(plan) {
  if (titleEl) titleEl.textContent = plan.nombre;
}

function renderCTA(plan, profile) {
  if (!ctaEl) return;
  ctaEl.innerHTML = '';
  if (plan.slug === 'gratuito') {
    const activate = document.createElement('button');
    activate.className = 'btn primary';
    activate.textContent = 'Ingresar / Activar';
    activate.addEventListener('click', async () => {
      await activateFreePlan(profile);
      window.location.href = 'content.html';
    });
    ctaEl.appendChild(activate);
    const contentLink = document.createElement('a');
    contentLink.className = 'btn';
    contentLink.href = 'content.html';
    contentLink.textContent = 'Ver contenido';
    ctaEl.appendChild(contentLink);
  } else {
    const pay = document.createElement('a');
    pay.className = 'btn primary';
    pay.href = `pay.html?plan=${encodeURIComponent(plan.slug)}`;
    pay.textContent = 'Ir a pagar';
    ctaEl.appendChild(pay);
    const contentLink = document.createElement('a');
    contentLink.className = 'btn';
    contentLink.href = 'content.html';
    contentLink.textContent = 'Ver contenido';
    ctaEl.appendChild(contentLink);
  }
}

async function activateFreePlan(profile) {
  if (!profile) return;
  await supabase.from('suscripciones').upsert({
    usuario_id: profile.id,
    plan_slug: 'gratuito',
    estado: 'activa'
  }, { onConflict: 'usuario_id' });
  await supabase.from('usuarios').update({ plan: 'gratuito' }).eq('id', profile.id);
}

function renderFeatures(list) {
  if (!featuresEl) return;
  featuresEl.innerHTML = list.map((item) => `<li>✔ ${item}</li>`).join('');
}

function renderFeedback(items) {
  if (!barsEl || !commentsList) return;
  const counts = { 1:0,2:0,3:0,4:0,5:0 };
  let yes = 0; let no = 0;
  items.forEach((item) => {
    counts[item.rating] = (counts[item.rating] || 0) + 1;
    if (item.recommend) yes++; else no++;
  });
  const total = items.length;
  const average = total ? (Object.entries(counts).reduce((acc, [rating, qty]) => acc + Number(rating) * qty, 0) / total) : 0;
  if (averageEl) averageEl.textContent = average.toFixed(2);
  if (totalEl) totalEl.textContent = total;
  barsEl.innerHTML = '';
  for (let stars = 5; stars >= 1; stars--) {
    const pct = total ? Math.round((counts[stars] / total) * 100) : 0;
    const row = document.createElement('div');
    row.className = 'bar-row';
    row.innerHTML = `
      <span class="bar-label">${stars}★</span>
      <div class="bar"><div class="bar-fill" style="width:${pct}%"></div></div>
      <span class="bar-pct">${pct}%</span>
    `;
    barsEl.appendChild(row);
  }
  const pctYes = total ? Math.round((yes / total) * 100) : 0;
  if (pieEl) pieEl.style.setProperty('--yes', pctYes);
  if (yesEl) yesEl.textContent = pctYes;
  if (noEl) noEl.textContent = 100 - pctYes;
  if (total === 0) {
    emptyMsg.style.display = 'block';
  } else {
    emptyMsg.style.display = 'none';
  }
  commentsList.innerHTML = items.map((item) => `
    <li class="comment">
      <div class="comment-head">
        <span class="stars">${'★'.repeat(item.rating)}${'☆'.repeat(5 - item.rating)}</span>
        <span class="dot"></span>
        <span class="date">${new Date(item.created_at).toLocaleString('es-CO')}</span>
        <span class="reco-tag ${item.recommend ? 'yes' : 'no'}">${item.recommend ? 'Recomienda' : 'No recomienda'}</span>
      </div>
      <p>${item.comment.replace(/</g, '&lt;')}</p>
    </li>
  `).join('');
}

async function handleFeedbackSubmit(event, profile) {
  event.preventDefault();
  if (!profile) {
    showError('Debes iniciar sesión.');
    return;
  }
  const data = new FormData(form);
  const rating = Number(data.get('rating') || 5);
  const recommend = Boolean(data.get('recommend'));
  const comment = String(data.get('comment') || '').trim();
  if (!comment) {
    showError('Escribe un comentario');
    return;
  }
  form.querySelector('button[type="submit"]').disabled = true;
  const { error } = await supabase.from('notificaciones').insert({
    usuario_id: profile.id,
    tipo: 'plan_feedback',
    mensaje: 'Nuevo feedback de plan',
    datos: { rating, recommend, comment, plan: planKey }
  });
  form.querySelector('button[type="submit"]').disabled = false;
  if (error) {
    showError('No se pudo registrar tu feedback');
    return;
  }
  showSuccess('Comentario enviado. ¡Gracias!');
  form.reset();
  const feedback = await fetchFeedback(planKey);
  renderFeedback(feedback);
}

function showSuccess(message) {
  if (!successBox) return;
  successBox.textContent = message;
  successBox.style.display = 'block';
  if (errorBox) errorBox.style.display = 'none';
}

function showError(message) {
  if (!errorBox) return;
  errorBox.textContent = message;
  errorBox.style.display = 'block';
  if (successBox) successBox.style.display = 'none';
}
