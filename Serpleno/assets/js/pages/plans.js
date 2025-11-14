import { supabase } from '../lib/supabaseClient.js';

const planOrder = ['gratuito','silver','premium'];
const planHeaders = document.querySelectorAll('[data-plan-header]');
const tbody = document.querySelector('[data-plan-rows]');
const cardsWrapper = document.querySelector('[data-plan-cards]');

(async function loadPlans(){
  const [plans, features] = await Promise.all([fetchPlans(), fetchFeatures()]);
  renderTable(plans, features);
  renderCards(plans, features);
})();

async function fetchPlans() {
  const { data, error } = await supabase
    .from('planes')
    .select('*');
  if (error || !data?.length) {
    return [
      { slug: 'gratuito', nombre: 'Plan Gratis', precio_mensual: 0, precio_anual: 0 },
      { slug: 'silver', nombre: 'Plan Silver', precio_mensual: 130000, precio_anual: 1200000 },
      { slug: 'premium', nombre: 'Plan Premium', precio_mensual: 200000, precio_anual: 2000000 }
    ];
  }
  return data;
}

async function fetchFeatures() {
  const { data, error } = await supabase
    .from('caracteristicas_plan')
    .select('*');
  if (error || !data?.length) {
    return [
      { nombre: 'Contenido estándar', planes: ['gratuito','silver','premium'] },
      { nombre: 'Eventos abiertos', planes: ['gratuito','silver','premium'] },
      { nombre: 'Comunidad de lectura', planes: ['gratuito','silver','premium'] },
      { nombre: 'Gestión de perfiles', planes: ['silver','premium'] },
      { nombre: 'Recordatorios', planes: ['silver','premium'] },
      { nombre: 'Eventos exclusivos', planes: ['silver','premium'] },
      { nombre: 'Sesiones con expertos', planes: ['silver','premium'] },
      { nombre: 'Plan personalizado', planes: ['silver','premium'] },
      { nombre: 'Coach grupal', planes: ['silver','premium'] },
      { nombre: 'Estadísticas', planes: ['silver','premium'] },
      { nombre: 'Coach personalizado', planes: ['premium'] },
      { nombre: 'Contenido premium', planes: ['premium'] }
    ];
  }
  return data.map((row) => ({ nombre: row.nombre, planes: row.planes || [row.plan_slug] }));
}

function renderTable(plans, features) {
  if (!tbody) return;
  tbody.innerHTML = '';
  planHeaders.forEach((th) => {
    const slug = th.dataset.planHeader;
    const info = plans.find((p) => p.slug === slug);
    if (info) {
      th.innerHTML = `${info.nombre}<br><small>${formatMoney(info.precio_mensual)}/mes · ${formatMoney(info.precio_anual)}/año</small>`;
    }
  });
  features.forEach((feature) => {
    const tr = document.createElement('tr');
    const td = document.createElement('td');
    td.className = 'feat';
    td.textContent = feature.nombre;
    tr.appendChild(td);
    planOrder.forEach((slug) => {
      const cell = document.createElement('td');
      const has = feature.planes?.includes(slug);
      cell.className = has ? 'yes' : 'no';
      cell.textContent = has ? '✔' : '✖';
      tr.appendChild(cell);
    });
    tbody.appendChild(tr);
  });
}

function renderCards(plans, features) {
  if (!cardsWrapper) return;
  cardsWrapper.innerHTML = '';
  planOrder.forEach((slug) => {
    const info = plans.find((p) => p.slug === slug);
    const list = features.filter((f) => f.planes?.includes(slug)).map((f) => f.nombre);
    const card = document.createElement('div');
    card.className = `plan-card plan-${slug}`;
    card.innerHTML = `
      <h3 style="margin-bottom:4px;">${info?.nombre || ''}</h3>
      <div style="margin:2px 0 8px;color:#556;font-size:14px;">
        ${formatMoney(info?.precio_mensual || 0)}/mes · ${formatMoney(info?.precio_anual || 0)}/año
      </div>
      <ul class="features">
        ${list.map((feat) => `<li>✔ ${feat}</li>`).join('')}
      </ul>
      <a class="btn primary" href="plan_detail.html?plan=${encodeURIComponent(slug)}">Ingresar</a>
    `;
    cardsWrapper.appendChild(card);
  });
}

function formatMoney(value) {
  return '$' + Number(value || 0).toLocaleString('es-CO');
}
