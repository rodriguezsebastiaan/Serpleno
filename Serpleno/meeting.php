<?php
// meeting.php
require_once __DIR__.'/layout.php';
require_once __DIR__.'/auth.php';

$user = current_user();
if (!$user) { header('Location: index.php?r=login'); exit; }

$plan  = strtolower($user['plan'] ?? 'gratuito');
$email = $user['email'] ?? 'anon@serpleno.test';

// Solo Silver/Premium (incluye alias "estudiantil" como Silver)
$is_paid = in_array($plan, ['premium','silver','estudiantil'], true);

// =========== Helpers ===========
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function pick_booking(array $bookings): ?array {
  if (!$bookings) return null;
  $now = time();

  // 1) Dentro de ventana útil (desde 15 min antes hasta 6 h después)
  usort($bookings, fn($a,$b)=>strcmp($a['when'],$b['when']));
  foreach ($bookings as $b) {
    $ts = strtotime($b['when'] ?? '');
    if ($ts === false) continue;
    if ($ts >= $now - 15*60 && $ts <= $now + 6*3600) return $b;
  }
  // 2) Siguiente futura
  foreach ($bookings as $b) {
    $ts = strtotime($b['when'] ?? '');
    if ($ts !== false && $ts >= $now) return $b;
  }
  // 3) Si no hay futuras, la última pasada
  return end($bookings);
}

function meeting_status(int $ts): string {
  $now = time();
  if ($ts >= $now - 15*60 && $ts <= $now + 6*3600) return 'live';
  if ($ts > $now) return 'upcoming';
  return 'ended';
}

function countdown_label(int $ts): string {
  $diff = $ts - time();
  if ($diff <= 0) return 'En breve';
  $mins = floor($diff/60);
  if ($mins < 60) return "Comienza en {$mins} min";
  $hrs  = floor($mins/60);
  $rem  = $mins % 60;
  return "Comienza en {$hrs} h ".($rem ? "{$rem} min" : '');
}

// =========== Selección de reserva ===========
$bookings = $_SESSION['bookings'][$email] ?? [];
$selected = null;

// Permitir seleccionar por querystring: ?when=YYYY-mm-dd HH:ii
if (!empty($_GET['when'])) {
  $qWhen = $_GET['when'];
  foreach ($bookings as $b) {
    if (($b['when'] ?? '') === $qWhen) { $selected = $b; break; }
  }
}
if (!$selected) $selected = pick_booking($bookings);

// URL de reunión (Jitsi) si hay reserva
$meetUrl = '';
$whenTs  = 0;
$status  = '';
if ($selected) {
  $whenTs = strtotime($selected['when']);
  if ($whenTs !== false) {
    $status = meeting_status($whenTs);
    // Sala única por user+when (hash corto)
    $slug = 'Serpleno-'.substr(sha1(($email ?? '') . '|' . $selected['when']), 0, 12);
    $meetUrl = 'https://meet.jit.si/'.rawurlencode($slug)
             . '#config.prejoinPageEnabled=false';
  }
}

render_header('Sala de reunión');
?>
<div class="home-hero" style="max-width:980px;margin:0 auto;text-align:center;">
  <h2 style="margin:6px 0 10px;">Sala de entrenamiento / cita</h2>

  <?php if (!$is_paid): ?>
    <section class="card" style="max-width:780px;text-align:center;">
      <div class="alert danger" style="margin-top:0">
        Esta función está disponible en los planes Silver y Premium.
      </div>
      <p style="color:#667;margin:8px 0">
        Actualiza tu plan para entrar a las salas de entrenamiento o citas.
      </p>
      <a class="btn primary" href="index.php?r=plans">Ver planes</a>
    </section>

  <?php elseif (!$selected): ?>
    <section class="card" style="max-width:780px;text-align:center;">
      <div class="alert danger" style="margin-top:0">
        No encontramos una reserva activa.
      </div>
      <p style="color:#667;margin:8px 0">
        Agenda una sesión para poder ingresar a la sala.
      </p>
      <a class="btn primary" href="index.php?r=schedule">Agendar entrenamiento / cita</a>
    </section>

  <?php else: ?>
    <section class="card" style="max-width:900px;text-align:center;">
      <h3 style="margin:0 0 8px 0;"><?= h($selected['title'] ?? 'Sesión') ?></h3>
      <div style="color:#556;margin-bottom:10px;">
        <?= h(date('d/m/Y g:i a', $whenTs)) ?>
        <?php if (!empty($selected['pro'])): ?>
          • con <?= h($selected['pro']) ?>
        <?php endif; ?>
      </div>

      <?php if ($status === 'upcoming'): ?>
        <div class="badge" id="countdown"><?= h(countdown_label($whenTs)) ?></div>
        <script>
        (function(){
          const el = document.getElementById('countdown');
          const ts = <?= (int)$whenTs ?> * 1000;
          function tick(){
            const now = Date.now();
            let diff = Math.floor((ts - now)/1000);
            if (diff <= 0){ el.textContent = 'En breve'; return; }
            const mins = Math.floor(diff/60);
            if (mins < 60){
              el.textContent = 'Comienza en ' + mins + ' min';
            } else {
              const hrs = Math.floor(mins/60), rem = mins%60;
              el.textContent = 'Comienza en ' + hrs + ' h' + (rem?(' '+rem+' min'):'');
            }
            requestAnimationFrame(tick);
          }
          tick();
        })();
        </script>
      <?php elseif ($status === 'live'): ?>
        <div class="badge">En vivo</div>
      <?php else: ?>
        <div class="badge" style="background:#f8e6e6;color:#a33;">Finalizada</div>
      <?php endif; ?>

      <div class="meeting-video" style="margin:10px auto;">
        <?php if ($meetUrl): ?>
          <iframe
            src="<?= h($meetUrl) ?>"
            allow="camera; microphone; fullscreen; display-capture; clipboard-write"
            style="width:100%;height:100%;border:0;border-radius:12px;"></iframe>
        <?php else: ?>
          <p style="color:#667;margin:10px 0">Preparando la sala…</p>
        <?php endif; ?>
      </div>

      <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:10px;">
        <?php if ($meetUrl): ?>
          <a class="btn primary" target="_blank" href="<?= h($meetUrl) ?>">Abrir en nueva pestaña</a>
        <?php endif; ?>
        <button class="btn outline" id="testAudioBtn" type="button">Probar audio</button>
        <a class="btn" href="index.php?r=schedule">Volver a mis reservas</a>
      </div>

      <audio id="testTone">
        <source src="data:audio/wav;base64,UklGRiQAAABXQVZFZm10IBAAAAABAAEAESsAACJWAAACABAAZGF0YQAAAAA=" type="audio/wav">
      </audio>
      <script>
        (function(){
          const btn = document.getElementById('testAudioBtn');
          const audio = document.getElementById('testTone');
          if (btn && audio) {
            btn.addEventListener('click', ()=> {
              try { audio.currentTime = 0; audio.play(); } catch(e){}
              btn.textContent = '¿Escuchaste el sonido?';
              setTimeout(()=>btn.textContent='Probar audio', 2500);
            });
          }
        })();
      </script>
    </section>

    <p style="margin:12px 0;">
      <a class="btn outline" href="index.php?r=notifications">Ir a notificaciones</a>
    </p>
  <?php endif; ?>
</div>
<?php render_footer(); ?>



