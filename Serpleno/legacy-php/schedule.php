<?php
// schedule.php
require_once __DIR__.'/layout.php';
require_once __DIR__.'/auth.php';

$user = current_user();
if (!$user) { header('Location: index.php?r=login'); exit; }

$plan = strtolower($user['plan'] ?? 'gratuito');
$email = $user['email'] ?? 'anon@serpleno.test';

// Solo Silver/Premium (estudiantil se trata como Silver)
$is_paid = in_array($plan, ['premium','silver','estudiantil'], true);

// =========
// Helpers
// =========
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// 08:00 -> 8:00 a.m. / 15:00 -> 3:00 p.m.
function fmt_ampm(string $hhmm): string {
  [$H, $M] = array_map('intval', explode(':', $hhmm));
  $ampm = ($H < 12) ? 'a.m.' : 'p.m.';
  $h12  = $H % 12; if ($h12 === 0) $h12 = 12;
  return sprintf('%d:%02d %s', $h12, $M, $ampm);
}

// Pros demo (puedes llevarlos a BD si lo prefieres)
$pros_entrenamiento = [
  ['id'=>'pro_luis','name'=>'Luis (Baile / Aeróbicos)'],
  ['id'=>'pro_cristian','name'=>'Cristian (Fuerza)'],
  ['id'=>'pro_jefferson','name'=>'Jefferson (Funcional)'],
];
$pros_cita = [
  ['id'=>'pro_carolina','name'=>'Carolina (Psicología)'],
  ['id'=>'pro_felipe','name'=>'Felipe (Psicología)'],
  ['id'=>'pro_james','name'=>'James (Nutrición)'],
  ['id'=>'pro_daniel','name'=>'Daniel (Coach de vida)'],
  ['id'=>'pro_nikol','name'=>'Nikol (Coach de vida)'],
];

// Generar próximos 7 días
$days = [];
$today = new DateTime('today');
for ($i=0; $i<7; $i++) {
  $d = clone $today; $d->modify("+$i day");
  $days[] = $d;
}

// Slots sugeridos (ordenados y “bonitos”)
$slots = ['08:00','09:00','10:00','11:00','14:00','15:00','16:00','17:00'];

// ================================
// Guardar reserva (en sesión DEMO)
// ================================
$msg = '';
$err = '';
if ($is_paid && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['__book'])) {
  $type = $_POST['type'] ?? 'entrenamiento'; // entrenamiento | cita
  $pro  = trim($_POST['pro'] ?? '');
  $day  = $_POST['day'] ?? '';
  $time = $_POST['time'] ?? '';

  // Validaciones simples
  if (!in_array($type, ['entrenamiento','cita'], true)) {
    $err = 'Tipo inválido.';
  } elseif (!$pro) {
    $err = 'Selecciona un profesional.';
  } elseif (!$day || !$time) {
    $err = 'Selecciona día y hora.';
  } else {
    // Construir fecha-hora
    $whenStr = $day.' '.$time.':00';
    $whenTs  = strtotime($whenStr);
    if ($whenTs === false) {
      $err = 'Fecha u hora inválida.';
    } else {
      // Título de la sesión
      $title = ($type === 'entrenamiento') ? 'Entrenamiento' : 'Cita con profesional';
      // Guardar en sesiones
      $_SESSION['bookings'][$email][] = [
        'title' => $title,
        'when'  => date('Y-m-d H:i', $whenTs),
        'pro'   => $pro,
      ];
      $msg = 'Reserva creada correctamente.';
    }
  }
}

// ================================
// Render
// ================================
render_header('Agendar entrenamiento / cita');
?>
<div class="home-hero" style="max-width:980px;margin:0 auto;text-align:center;">
  <h2 style="margin:6px 0 10px;">Agendar entrenamiento / cita</h2>

  <?php if (!$is_paid): ?>
    <section class="card" style="max-width:760px;text-align:center;">
      <div class="alert danger" style="margin-top:0">
        Esta función está disponible en los planes Silver y Premium.
      </div>
      <p style="color:#667;margin:8px 0">
        Actualiza tu plan para agendar entrenamientos y citas con profesionales.
      </p>
      <a class="btn primary" href="index.php?r=plans">Ver planes</a>
    </section>
  <?php else: ?>

    <section class="card pay-wrap" style="text-align:center;max-width:860px;">
      <?php if ($err): ?><div class="alert danger"><?= h($err) ?></div><?php endif; ?>
      <?php if ($msg): ?><div class="alert success"><?= h($msg) ?></div><?php endif; ?>

      <form method="post" class="auth-form" style="align-items:center;">
        <input type="hidden" name="__book" value="1">

        <label style="max-width:520px;width:100%;">Tipo
          <select name="type" id="typeSel" required>
            <option value="entrenamiento">Entrenamiento</option>
            <option value="cita">Cita con profesional</option>
          </select>
        </label>

        <label style="max-width:520px;width:100%;">Profesional
          <select name="pro" id="proSel" required>
            <?php foreach ($pros_entrenamiento as $p): ?>
              <option value="<?= h($p['name']) ?>" data-kind="entrenamiento"><?= h($p['name']) ?></option>
            <?php endforeach; ?>
            <?php foreach ($pros_cita as $p): ?>
              <option value="<?= h($p['name']) ?>" data-kind="cita" style="display:none"><?= h($p['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>

        <div class="form-row" style="justify-content:center;">
          <label> Día
            <select name="day" required>
              <?php foreach ($days as $d): ?>
                <option value="<?= $d->format('Y-m-d') ?>">
                  <?= $d->format('d/m/Y (D)') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>

          <label> Hora
            <select name="time" required>
              <?php foreach ($slots as $s): ?>
                <option value="<?= $s ?>"><?= fmt_ampm($s) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        </div>

        <button class="btn primary" type="submit">Confirmar reserva</button>
      </form>
    </section>

    <section class="card" style="max-width:860px;text-align:center;margin-top:12px;">
      <h3 style="margin:0 0 8px 0;">Tus próximas reservas</h3>
      <?php $bookings = $_SESSION['bookings'][$email] ?? []; ?>
      <?php if (!$bookings): ?>
        <p style="color:#667;margin:6px 0">Aún no tienes reservas.</p>
      <?php else: ?>
        <div class="bookings" style="display:grid;grid-template-columns:repeat(1,minmax(260px,1fr));gap:10px;max-width:760px;margin:0 auto;">
          <?php foreach ($bookings as $b): ?>
            <div class="pro-card" style="text-align:center;">
              <h4 style="margin:0 0 6px 0;"><?= h($b['title'] ?? 'Sesión') ?></h4>
              <div style="color:#666;margin-bottom:6px;">
                <?= h(date('d/m/Y g:i a', strtotime($b['when'] ?? 'now'))) ?>
                <?php if (!empty($b['pro'])): ?>
                  • con <?= h($b['pro']) ?>
                <?php endif; ?>
              </div>
              <a class="btn" href="index.php?r=meeting">Entrar a la sala</a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <p style="margin:12px 0;">
      <a class="btn outline" href="index.php?r=notifications">Ir a notificaciones</a>
    </p>
  <?php endif; ?>
</div>

<script>
// Cambia la lista de profesionales según el tipo
(function(){
  const typeSel = document.getElementById('typeSel');
  const proSel  = document.getElementById('proSel');
  function updatePros(){
    const kind = typeSel.value; // entrenamiento | cita
    [...proSel.options].forEach(op => {
      const ok = op.getAttribute('data-kind') === kind;
      op.style.display = ok ? '' : 'none';
    });
    // seleccionar el primero visible
    const first = [...proSel.options].find(op => op.style.display !== 'none');
    if (first) proSel.value = first.value;
  }
  typeSel.addEventListener('change', updatePros);
  updatePros();
})();
</script>

<?php render_footer(); ?>





