<?php
require_once __DIR__.'/layout.php';

$user = current_user();
if (!$user) { header('Location: index.php?r=login'); exit; }

$plan = $user['plan'] ?? 'gratuito';
render_header('Portal');

if (!in_array($plan, ['estudiantil','premium'], true)) {
    echo '<div class="card" style="max-width:720px;margin:0 auto;">
            <p>El portal es exclusivo para planes Estudiantil y Premium.</p>
            <p><a class="btn primary" href="index.php?r=plans">Ver planes</a></p>
          </div>';
    render_footer(); exit;
}
?>
<h2>Tu portal — <?= htmlspecialchars(ucfirst($plan)) ?></h2>

<div class="grid center-grid" style="grid-template-columns:repeat(1,minmax(260px,1fr));">
  <div class="card">
    <h3>Entrenamiento / Cita</h3>
    <p>Agenda o reprograma tus sesiones.</p>
    <a class="btn primary" href="index.php?r=schedule">Ir a entrenamiento/cita</a>
  </div>

  <div class="card">
    <h3>Notificaciones</h3>
    <p>Próximos pagos y enlaces para entrar a la sala.</p>
    <a class="btn" href="index.php?r=notifications">Ver notificaciones</a>
  </div>

  <div class="card">
    <h3>Contenido</h3>
    <p>Acceso al contenido gratuito disponible.</p>
    <a class="btn" href="index.php?r=content">Ver contenido</a>
  </div>

  <div class="card">
    <h3>Método de pago</h3>
    <p>Actualiza tarjeta o periodo de pago.</p>
    <a class="btn" href="index.php?r=pay">Ir a pagar</a>
  </div>
</div>

<?php render_footer(); ?>







