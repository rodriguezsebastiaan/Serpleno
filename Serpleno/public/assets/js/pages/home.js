import { getCurrentProfile } from '../lib/session.js';
import { supabase } from '../supabaseClient.js';

const SLIDES_BUCKET = 'home-slides';

const nameTarget = document.querySelector('[data-home-username]');
const ctaWrapper = document.querySelector('[data-home-cta]');

(async function initHome(){
  const profile = await getCurrentProfile();
  if (nameTarget) {
    nameTarget.textContent = profile?.nombre || profile?.email || '';
  }
  renderCTA(profile?.plan || 'gratuito');
  const slides = await loadSlides();
  buildCarousel(slides);
})();

function renderCTA(plan) {
  if (!ctaWrapper) return;
  ctaWrapper.innerHTML = '<a class="btn primary" href="plans.html">Conoce nuestros planes</a>';
  if (plan === 'gratuito') {
    ctaWrapper.innerHTML += '<a class="btn" href="content.html" style="margin-left:8px;">Iniciar gratis</a>';
  } else {
    ctaWrapper.innerHTML += '<a class="btn" href="schedule.html" style="margin-left:8px;">Ir a entrenamiento/cita</a>';
  }
}

async function loadSlides() {
  try {
    const { data, error } = await supabase.storage
      .from(SLIDES_BUCKET)
      .list('', { limit: 12, sortBy: { column: 'name', order: 'asc' } });
    if (error) throw error;
    const files = (data || []).filter((f) => /\.(png|jpe?g|gif)$/i.test(f.name));
    if (!files.length) throw new Error('no files');
    return files.map((f) => ({ name: f.name, src: supabase.storage.from(SLIDES_BUCKET).getPublicUrl(f.name).data.publicUrl }));
  } catch (err) {
    console.warn('No se pudieron cargar las imágenes del carrusel', err);
    return [
      { name: 'logo empresa.png', src: 'logo%20empresa.png' }
    ];
  }
}

function buildCarousel(items) {
  const root = document.getElementById('fadeCarousel');
  const dots = root?.querySelector('.carousel-dots');
  const prev = root?.querySelector('.prev');
  const next = root?.querySelector('.next');
  const notice = document.getElementById('carouselNotice');
  if (!root || !dots || !prev || !next) return;

  if (!items.length) {
    notice.style.display = 'block';
    notice.textContent = 'No se encontraron imágenes para el carrusel';
    return;
  }

  items.forEach((item, index) => {
    const wrap = document.createElement('div');
    wrap.className = 'fade-slide' + (index === 0 ? ' active' : '');
    const img = document.createElement('img');
    img.className = 'fade-img';
    img.alt = item.name;
    img.src = item.src;
    wrap.appendChild(img);
    root.appendChild(wrap);

    const dot = document.createElement('button');
    dot.className = 'dot' + (index === 0 ? ' active' : '');
    dot.addEventListener('click', () => go(index));
    dots.appendChild(dot);
  });

  let idx = 0;
  let timer = null;

  function go(n) {
    const slides = root.querySelectorAll('.fade-slide');
    if (!slides.length) return;
    const old = idx;
    idx = (n + slides.length) % slides.length;
    if (old === idx) return;
    slides[old].classList.remove('active');
    slides[idx].classList.add('active');
    dots.querySelectorAll('.dot').forEach((dot, position) => {
      dot.classList.toggle('active', position === idx);
    });
    restart();
  }

  function nextImg(){ go(idx + 1); }
  function prevImg(){ go(idx - 1); }
  prev.addEventListener('click', prevImg);
  next.addEventListener('click', nextImg);

  function restart(){
    clearInterval(timer);
    if (items.length > 1) timer = setInterval(nextImg, 4000);
  }

  restart();
}
