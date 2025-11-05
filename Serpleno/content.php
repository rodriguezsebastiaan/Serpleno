<?php
require_once __DIR__.'/layout.php';

$user = current_user();
if (!$user) { header('Location: index.php?r=login'); exit; }

render_header('Contenido abierto');

if (($user['plan'] ?? '') !== 'gratuito'): ?>
  <div class="alert success">Este es el contenido gratuito disponible para todos los planes.</div>
<?php endif; ?>

<?php
$content = [
  'entrenamientos' => [
    ['title'=>'Cuerpo completo','day'=>'Lunes','url'=>'https://www.youtube.com/watch?v=9rTnTI2Jg78&pp=ygUcZW50cmVuYW1pZW50byBmdWxsIGJvZHkgY2FzYQ%3D%3D'],
    ['title'=>'Cardio','day'=>'Lunes','url'=>'https://www.youtube.com/watch?v=erpAjBqBBnU&pp=ygUZZW50cmVuYW1pZW50byBjYXJkaW8gY2FzYQ%3D%3D'],
    ['title'=>'Cardio','day'=>'Martes','url'=>'https://www.youtube.com/watch?v=erpAjBqBBnU&pp=ygUZZW50cmVuYW1pZW50byBjYXJkaW8gY2FzYQ%3D%3D'],
    ['title'=>'Espalda','day'=>'Martes','url'=>'https://www.youtube.com/watch?v=syL7iztnd8I&pp=ygUaZW50cmVuYW1pZW50byBlc3BhbGRhIGNhc2E%3D'],
    ['title'=>'Cardio','day'=>'Miércoles','url'=>'https://www.youtube.com/watch?v=erpAjBqBBnU&pp=ygUZZW50cmVuYW1pZW50byBjYXJkaW8gY2FzYQ%3D%3D'],
    ['title'=>'Pierna','day'=>'Miércoles','url'=>'https://www.youtube.com/watch?v=4QoDJRYmP3U&pp=ygUZZW50cmVuYW1pZW50byBwaWVybmEgY2FzYQ%3D%3D'],
    ['title'=>'Cardio','day'=>'Jueves','url'=>'https://www.youtube.com/watch?v=erpAjBqBBnU&pp=ygUZZW50cmVuYW1pZW50byBjYXJkaW8gY2FzYQ%3D%3D'],
    ['title'=>'Hombro','day'=>'Jueves','url'=>'https://www.youtube.com/watch?v=gLda1uHFaUg&pp=ygUZZW50cmVuYW1pZW50byBob21icm8gY2FzYQ%3D%3D'],
    ['title'=>'Cardio','day'=>'Viernes','url'=>'https://www.youtube.com/watch?v=erpAjBqBBnU&pp=ygUZZW50cmVuYW1pZW50byBjYXJkaW8gY2FzYQ%3D%3D'],
    ['title'=>'Pecho','day'=>'Viernes','url'=>'https://www.youtube.com/watch?v=x7zLcAWueAc&pp=ygUYZW50cmVuYW1pZW50byBwZWNobyBjYXNh'],
    ['title'=>'Cardio','day'=>'Sábado','url'=>'https://www.youtube.com/watch?v=erpAjBqBBnU&pp=ygUZZW50cmVuYW1pZW50byBjYXJkaW8gY2FzYQ%3D%3D'],
    ['title'=>'Baile','day'=>'Sábado','url'=>'https://www.youtube.com/watch?v=un9inxYgTTA&pp=ygUbZW50cmVuYW1pZW50byB6dW1iYSBlbiBjYXNh'],
  ],
  'psicologia' => [
    ['title'=>'Resiliencia para afrontar la vida cotidiana','day'=>'Lunes','url'=>'https://www.youtube.com/watch?v=-NyrqrE--Tg&pp=ygUVY2hhcmxhcyBkZSBwc2ljb2xvZ2lh'],
    ['title'=>'Cambia tu mente, cambia tu vida','day'=>'Martes','url'=>'https://www.youtube.com/watch?v=uhZzB5hid6M&pp=ygUVY2hhcmxhcyBkZSBwc2ljb2xvZ2lh'],
    ['title'=>'Que nadie te amargue la vida','day'=>'Miércoles','url'=>'https://www.youtube.com/watch?v=7I81zhQnjq4&pp=ygUVY2hhcmxhcyBkZSBwc2ljb2xvZ2lh'],
    ['title'=>'Cómo dejar de ser tu peor enemigo','day'=>'Jueves','url'=>'https://www.youtube.com/watch?v=5YJ_t41Z440&pp=ygUVY2hhcmxhcyBkZSBwc2ljb2xvZ2lh'],
    ['title'=>'Abrazando tu ansiedad','day'=>'Viernes','url'=>'https://www.youtube.com/watch?v=7UySdWIh_Y8&pp=ygUVY2hhcmxhcyBkZSBwc2ljb2xvZ2lh'],
    ['title'=>'¿Cómo es una relación sana?','day'=>'Sábado','url'=>'https://www.youtube.com/watch?v=EB1bJoMGebc&pp=ygUVY2hhcmxhcyBkZSBwc2ljb2xvZ2lh'],
  ],
  'nutricion' => [
    ['title'=>'Así deben ser tus comidas al día','day'=>'Lunes','url'=>'https://www.youtube.com/watch?v=qo2evW8bmmM&pp=ygUNbnV0cmljaW9uaXN0YQ%3D%3D'],
    ['title'=>'Ganar masa muscular','day'=>'Viernes','url'=>'https://www.youtube.com/watch?v=K7dOcwMUy2M&pp=ygUmbnV0cmljaW9uaXN0YSBwYXJhIGdhbmFyIG1hc2EgbXVzY3VsYXI%3D'],
  ],
  'coach' => [
    ['title'=>'Cómo sacar tu mejor versión','day'=>'Lunes','url'=>'https://www.youtube.com/watch?v=MEKKv1qMahs&pp=ygUNY29hY2ggZGUgdmlkYQ%3D%3D'],
    ['title'=>'Cómo te puede servir el coach de vida','day'=>'Martes','url'=>'https://www.youtube.com/watch?v=Rv16_A08D8w&pp=ygUNY29hY2ggZGUgdmlkYQ%3D%3D'],
    ['title'=>'El coach del éxito','day'=>'Miércoles','url'=>'https://www.youtube.com/watch?v=QT6BOl3q76g&pp=ygUNY29hY2ggZGUgdmlkYQ%3D%3D'],
    ['title'=>'Preguntas poderosas','day'=>'Jueves','url'=>'https://www.youtube.com/watch?v=FozJhqwvVi4&pp=ygUWY29hY2ggZGUgdmlkYSBwZXJzb25hbA%3D%3D'],
    ['title'=>'La mentalidad de un ganador','day'=>'Viernes','url'=>'https://www.youtube.com/watch?v=Ftzm8X8JZZQ&pp=ygUWY29hY2ggZGUgdmlkYSBwZXJzb25hbA%3D%3D'],
    ['title'=>'Hábitos para mejorar el desarrollo personal','day'=>'Sábado','url'=>'https://www.youtube.com/watch?v=klRnXmW4PF0&pp=ygUWY29hY2ggZGUgdmlkYSBwZXJzb25hbNIHCQmyCQGHKiGM7w%3D%3D'],
  ],
];
?>

<h2 style="margin-bottom:10px;">Contenido abierto</h2>

<div class="filters card" style="margin:0 auto 16px;max-width:980px;">
  <div class="filters-row">
    <label>Día:
      <select id="day">
        <option value="">Todos</option>
        <option>Lunes</option><option>Martes</option><option>Miércoles</option>
        <option>Jueves</option><option>Viernes</option><option>Sábado</option><option>Domingo</option>
      </select>
    </label>
    <div class="chip-group" id="cats">
      <button class="chip active" data-cat="entrenamientos">Entrenamientos</button>
      <button class="chip" data-cat="coach">Coach de vida</button>
      <button class="chip" data-cat="psicologia">Psicología</button>
      <button class="chip" data-cat="nutricion">Nutrición</button>
    </div>
  </div>
</div>

<div id="videos" class="grid center-grid"></div>

<script>
(function(){
  const DATA = <?= json_encode($content, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
  const list = document.getElementById('videos');
  const chips = document.querySelectorAll('.chip');
  const daySel = document.getElementById('day');
  let cat = 'entrenamientos';

  function toEmbed(u){
    try{
      const url = new URL(u);
      if (url.hostname.includes('youtu.be')) return 'https://www.youtube.com/embed/'+url.pathname.slice(1);
      if (url.hostname.includes('youtube.com')) {
        const v = url.searchParams.get('v');
        if (v) return 'https://www.youtube.com/embed/'+v;
      }
    }catch(e){}
    return u;
  }

  function escapeHtml(s){return String(s).replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]));}

  function render(){
    const day = daySel.value;
    const items = (DATA[cat]||[]).filter(v => !day || (v.day||'')===day);
    if (!items.length) { list.innerHTML = '<p>No hay videos para los filtros seleccionados.</p>'; return; }
    list.innerHTML = items.map(v => `
      <div class="card">
        <h3>${escapeHtml(v.title)} ${v.day?`(${escapeHtml(v.day)})`:''}</h3>
        <div class="video-wrap">
          <iframe width="100%" height="215" src="${escapeHtml(toEmbed(v.url))}" title="${escapeHtml(v.title)}"
            frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            referrerpolicy="strict-origin-when-cross-origin" allowfullscreen>
          </iframe>
        </div>
      </div>`).join('');
  }

  chips.forEach(b=>b.addEventListener('click',()=>{
    chips.forEach(x=>x.classList.remove('active'));
    b.classList.add('active');
    cat = b.dataset.cat;
    render();
  }));
  daySel.addEventListener('change', render);
  render();
})();
</script>

<?php render_footer(); ?>










 


