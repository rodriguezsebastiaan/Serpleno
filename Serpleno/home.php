<?php
require_once __DIR__.'/layout.php';

$user = current_user();
render_header('Inicio');

/* =========================
   Diapositivas del carrusel
   ========================= */

/* 1) RUTAS ABSOLUTAS (Windows) que me pasaste */
$sourcePaths = [
  'C:\\xampp\\htdocs\\Serpleno\\Captura de pantalla 2025-09-04 114812.png',
  'C:\\xampp\\htdocs\\Serpleno\\Captura de pantalla 2025-09-04 114835.png',
  'C:\\xampp\\htdocs\\Serpleno\\Captura de pantalla 2025-09-04 114858.png',
  'C:\\xampp\\htdocs\\Serpleno\\Captura de pantalla 2025-09-04 114926.png',
];

/* 2) Copia (una sola vez) a la carpeta del proyecto para poder servirlas por HTTP
      - Deja una copia con el MISMO NOMBRE en Serpleno\
      - Si ya existe, no vuelve a copiar. */
$slides = [];
foreach ($sourcePaths as $absPath) {
    if (is_file($absPath)) {
        $dest = __DIR__ . DIRECTORY_SEPARATOR . basename($absPath);
        if (!is_file($dest)) {
            @copy($absPath, $dest); // silencioso; si no puede copiar, no rompe
        }
        if (is_file($dest)) {
            $slides[] = basename($absPath); // lo servimos por nombre de archivo relativo
        }
    }
}

/* 3) Fallback por si nada se pudo copiar */
if (!$slides) {
    $slides = ['logo empresa.png']; // si tampoco existe, se avisará abajo
}

/* Preparamos objetos {name, src} (SIN captions) */
$slideItems = [];
foreach ($slides as $name) {
    $slideItems[] = [
        'name' => $name,
        'src'  => rawurlencode($name), // maneja espacios y acentos en la URL
    ];
}
?>
<div class="home-hero">
  <h2>Hola <?= htmlspecialchars($user['name'] ?? '') ?>, bienvenido a Serpleno</h2>

  <div id="carouselNotice" class="alert danger" style="display:none;"></div>

  <!-- Carrusel fade SIN captions -->
  <div id="fadeCarousel" class="carousel-fade"
       style="max-width:640px;height:200px;margin:10px auto;">
    <button class="carousel-btn prev" aria-label="Anterior" style="left:8px;">&#10094;</button>
    <button class="carousel-btn next" aria-label="Siguiente" style="right:8px;">&#10095;</button>
    <div class="carousel-dots"></div>
  </div>

  <p style="margin-top:12px;">
    <a class="btn primary" href="index.php?r=plans">Conoce nuestros planes</a>

    <?php if (($user['plan'] ?? '') === 'gratuito'): ?>
      <!-- Gratis: CTA para contenido gratuito -->
      <a class="btn" href="index.php?r=content" style="margin-left:8px;">Iniciar gratis</a>
    <?php else: ?>
      <!-- Estudiantil / Premium: CTA para agenda -->
      <a class="btn" href="index.php?r=schedule" style="margin-left:8px;">Ir a entrenamiento/cita</a>
    <?php endif; ?>
  </p>
</div>

<script>
(function(){
  const items = <?= json_encode($slideItems, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
  const root   = document.getElementById('fadeCarousel');
  const dotsC  = root.querySelector('.carousel-dots');
  const prev   = root.querySelector('.prev');
  const next   = root.querySelector('.next');
  const notice = document.getElementById('carouselNotice');

  const ok=[], bad=[]; let pending = items.length || 1;

  items.forEach(it=>{
    const img = new Image();
    img.onload  = ()=>{ ok.push(it); if(--pending===0) init(); };
    img.onerror = ()=>{ bad.push(it); if(--pending===0) init(); };
    img.src = it.src;
  });

  function init(){
    if (bad.length){
      notice.style.display='block';
      notice.textContent = 'No se pudieron cargar: ' + bad.map(b=>b.name).join(', ');
    }
    const list = ok.length ? ok : [{name:'logo empresa.png', src: encodeURIComponent('logo empresa.png')}];

    list.forEach((it,i)=>{
      const wrap = document.createElement('div');
      wrap.className = 'fade-slide'+(i===0?' active':'');
      const im = document.createElement('img');
      im.className = 'fade-img';
      im.alt = it.name;
      im.src = it.src;
      wrap.appendChild(im);
      root.appendChild(wrap);

      const d=document.createElement('button');
      d.className='dot'+(i===0?' active':'');
      d.addEventListener('click',()=>go(i));
      dotsC.appendChild(d);
    });

    idx=0; restart();
  }

  let idx=0, timer=null, interval=4000;

  function go(n){
    const slides = root.querySelectorAll('.fade-slide');
    if (!slides.length) return;
    const old = idx;
    idx = (n + slides.length) % slides.length;
    if (old === idx) return;
    slides[old].classList.remove('active');
    slides[idx].classList.add('active');
    dotsC.querySelectorAll('.dot').forEach((d,i)=>d.classList.toggle('active', i===idx));
    restart();
  }
  function nextImg(){ go(idx+1); }
  function prevImg(){ go(idx-1); }
  prev.addEventListener('click', prevImg);
  next.addEventListener('click', nextImg);

  function restart(){
    clearInterval(timer);
    const slides = root.querySelectorAll('.fade-slide');
    if (slides.length > 1) timer = setInterval(nextImg, interval);
  }
})();
</script>

<?php render_footer(); ?>














