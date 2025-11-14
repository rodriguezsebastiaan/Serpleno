import { supabase } from '../lib/supabaseClient.js';
import { getCurrentProfile } from '../lib/session.js';

const daySelect = document.getElementById('contentDay');
const catButtons = document.querySelectorAll('#contentCats .chip');
const grid = document.getElementById('contentVideos');
const premiumNotice = document.querySelector('[data-content-premium]');
let currentCategory = 'entrenamientos';
let videosByCategory = {};

(async function initContent(){
  const profile = await getCurrentProfile();
  if (premiumNotice && profile && profile.plan !== 'gratuito') {
    premiumNotice.style.display = 'block';
  }
  videosByCategory = await fetchContent();
  render();
})();

catButtons.forEach((button) => {
  button.addEventListener('click', () => {
    catButtons.forEach((b) => b.classList.remove('active'));
    button.classList.add('active');
    currentCategory = button.dataset.cat;
    render();
  });
});

if (daySelect) {
  daySelect.addEventListener('change', render);
}

async function fetchContent() {
  const { data, error } = await supabase
    .from('contenidos')
    .select('categoria, titulo, dia, url')
    .eq('tipo', 'video');
  if (error || !data?.length) {
    return getFallbackContent();
  }
  return data.reduce((acc, item) => {
    const key = item.categoria || 'entrenamientos';
    if (!acc[key]) acc[key] = [];
    acc[key].push({ title: item.titulo, day: item.dia, url: item.url });
    return acc;
  }, {});
}

function render() {
  if (!grid) return;
  const day = daySelect?.value || '';
  const list = (videosByCategory[currentCategory] || []).filter((item) => !day || item.day === day);
  if (!list.length) {
    grid.innerHTML = '<p>No hay videos para los filtros seleccionados.</p>';
    return;
  }
  grid.innerHTML = list.map((item) => `
    <div class="card">
      <h3>${escapeHtml(item.title)} ${item.day ? `(${escapeHtml(item.day)})` : ''}</h3>
      <div class="video-wrap">
        <iframe width="100%" height="215" src="${escapeHtml(toEmbed(item.url))}" title="${escapeHtml(item.title)}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
      </div>
    </div>
  `).join('');
}

function toEmbed(link) {
  try {
    const url = new URL(link);
    if (url.hostname.includes('youtu.be')) {
      return `https://www.youtube.com/embed/${url.pathname.slice(1)}`;
    }
    if (url.hostname.includes('youtube.com')) {
      const v = url.searchParams.get('v');
      if (v) return `https://www.youtube.com/embed/${v}`;
    }
    return link;
  } catch (err) {
    return link;
  }
}

function escapeHtml(value) {
  return String(value).replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[char] || char));
}

function getFallbackContent() {
  return {
    entrenamientos: [
      { title: 'Cuerpo completo', day: 'Lunes', url: 'https://www.youtube.com/watch?v=9rTnTI2Jg78' },
      { title: 'Cardio', day: 'Lunes', url: 'https://www.youtube.com/watch?v=erpAjBqBBnU' },
      { title: 'Cardio', day: 'Martes', url: 'https://www.youtube.com/watch?v=erpAjBqBBnU' },
      { title: 'Espalda', day: 'Martes', url: 'https://www.youtube.com/watch?v=syL7iztnd8I' },
      { title: 'Pierna', day: 'Miércoles', url: 'https://www.youtube.com/watch?v=4QoDJRYmP3U' },
      { title: 'Hombro', day: 'Jueves', url: 'https://www.youtube.com/watch?v=gLda1uHFaUg' },
      { title: 'Pecho', day: 'Viernes', url: 'https://www.youtube.com/watch?v=x7zLcAWueAc' },
      { title: 'Baile', day: 'Sábado', url: 'https://www.youtube.com/watch?v=un9inxYgTTA' }
    ],
    psicologia: [
      { title: 'Resiliencia para afrontar la vida cotidiana', day: 'Lunes', url: 'https://www.youtube.com/watch?v=-NyrqrE--Tg' },
      { title: 'Cambia tu mente, cambia tu vida', day: 'Martes', url: 'https://www.youtube.com/watch?v=uhZzB5hid6M' },
      { title: 'Que nadie te amargue la vida', day: 'Miércoles', url: 'https://www.youtube.com/watch?v=7I81zhQnjq4' }
    ],
    nutricion: [
      { title: 'Así deben ser tus comidas al día', day: 'Lunes', url: 'https://www.youtube.com/watch?v=qo2evW8bmmM' },
      { title: 'Ganar masa muscular', day: 'Viernes', url: 'https://www.youtube.com/watch?v=K7dOcwMUy2M' }
    ],
    coach: [
      { title: 'Cómo sacar tu mejor versión', day: 'Lunes', url: 'https://www.youtube.com/watch?v=MEKKv1qMahs' },
      { title: 'Cómo te puede servir el coach de vida', day: 'Martes', url: 'https://www.youtube.com/watch?v=Rv16_A08D8w' }
    ]
  };
}
