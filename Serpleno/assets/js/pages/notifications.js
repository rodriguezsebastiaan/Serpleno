import { supabase } from '../lib/supabaseClient.js';
import { getCurrentProfile } from '../lib/session.js';

const nameEl = document.querySelector('[data-notifications-name]');
const upgradeCard = document.querySelector('[data-notifications-upgrade]');
const subscriptionCard = document.querySelector('[data-notifications-subscription]');
const planBadge = document.querySelector('[data-notifications-plan]');
const cycleBadge = document.querySelector('[data-notifications-cycle]');
const renewalEl = document.querySelector('[data-notifications-renewal]');
const sessionsCard = document.querySelector('[data-notifications-sessions]');
const sessionsList = document.querySelector('[data-notifications-bookings]');
const sessionsEmpty = document.querySelector('[data-notifications-empty]');
const listEl = document.querySelector('[data-notifications-list]');

(async function initNotifications(){
  const profile = await getCurrentProfile();
  if (!profile) return;
  if (nameEl) nameEl.textContent = profile.nombre || profile.email;
  const isPaid = ['silver','estudiantil','premium'].includes((profile.plan || '').toLowerCase());
  if (!isPaid) {
    if (upgradeCard) upgradeCard.style.display = 'block';
  } else {
    if (subscriptionCard) subscriptionCard.style.display = 'block';
    await renderSubscription(profile);
    await renderSessions(profile);
  }
  await renderNotifications(profile);
})();

async function renderSubscription(profile) {
  const { data } = await supabase.from('suscripciones').select('*').eq('usuario_id', profile.id).maybeSingle();
  const planLabel = normalizePlan(profile.plan);
  if (planBadge) planBadge.textContent = `Plan: ${planLabel}`;
  if (cycleBadge) cycleBadge.textContent = `Ciclo: ${(data?.ciclo || 'mensual').replace(/^./, (c) => c.toUpperCase())}`;
  const next = data?.fecha_fin || data?.fecha_inicio || new Date().toISOString();
  if (renewalEl) renewalEl.textContent = new Date(next).toLocaleDateString('es-CO');
}

async function renderSessions(profile) {
  const { data, error } = await supabase
    .from('reservas')
    .select('id, estado, created_at, nota, link_reunion, disponibilidad_id, profesional_id, sesiones:sesiones_reunion(url), disponibilidad:disponibilidad_pro(fecha, hora_inicio), profesional:usuarios!reservas_profesional_id_fkey(nombre)')
    .eq('cliente_id', profile.id)
    .order('created_at', { ascending: false })
    .limit(5);
  if (error) {
    console.error(error);
    return;
  }
  if (!data?.length) {
    if (sessionsCard) sessionsCard.style.display = 'block';
    if (sessionsEmpty) sessionsEmpty.style.display = 'block';
    return;
  }
  if (sessionsCard) sessionsCard.style.display = 'block';
  sessionsEmpty.style.display = 'none';
  sessionsList.innerHTML = data.map((item) => {
    const when = formatDateTime(item.disponibilidad?.fecha, item.disponibilidad?.hora_inicio);
    const pro = item.profesional?.nombre || '';
    return `
      <div class="pro-card" style="text-align:center;">
        <h4 style="margin:0 0 6px 0;">${item.nota || 'Sesión'}</h4>
        <div style="color:#666;margin-bottom:6px;">${when}${pro ? ` • con ${pro}` : ''}</div>
        <a class="btn" href="${item.sesiones?.url || item.link_reunion || 'meeting.html'}">Entrar a la sala</a>
      </div>
    `;
  }).join('');
}

async function renderNotifications(profile) {
  const { data, error } = await supabase
    .from('notificaciones')
    .select('*')
    .eq('usuario_id', profile.id)
    .order('created_at', { ascending: false });
  if (error || !data) {
    listEl.innerHTML = '<p>No tienes notificaciones.</p>';
    return;
  }
  listEl.innerHTML = data.map((row) => `
    <article class="notification ${row.leida ? 'read' : ''}" data-id="${row.id}">
      <div>
        <h4>${row.titulo || row.tipo || 'Notificación'}</h4>
        <p>${row.mensaje || ''}</p>
        <small>${new Date(row.created_at).toLocaleString('es-CO')}</small>
      </div>
      <button class="btn" data-mark-read="${row.id}">${row.leida ? 'Leída' : 'Marcar como leída'}</button>
    </article>
  `).join('');
  listEl.querySelectorAll('[data-mark-read]').forEach((button) => {
    button.addEventListener('click', async () => {
      const id = button.dataset.markRead;
      await supabase.from('notificaciones').update({ leida: true }).eq('id', id);
      button.textContent = 'Leída';
      button.disabled = true;
      const article = button.closest('.notification');
      if (article) article.classList.add('read');
    });
  });
}

function normalizePlan(plan) {
  const value = (plan || '').toLowerCase();
  if (value === 'estudiantil' || value === 'silver') return 'Silver';
  if (value === 'premium') return 'Premium';
  return 'Gratuito';
}

function formatDateTime(date, time) {
  const d = date ? new Date(`${date}T${time || '00:00'}Z`) : new Date();
  return d.toLocaleString('es-CO');
}
