<?php
require_once __DIR__.'/layout.php';
require_once __DIR__.'/auth.php';

$user = current_user();
if (!$user) { header('Location: index.php?r=login'); exit; }

$ok     = isset($_GET['ok']) ? (int)$_GET['ok'] : 0;
$plan   = $_GET['plan']   ?? '';
$cycle  = $_GET['cycle']  ?? '';
$amt    = (int)($_GET['amt'] ?? 0);
$next   = $_GET['next']   ?? '';
$method = $_GET['method'] ?? 'tarjeta';

$names = ['silver'=>'Plan Silver', 'premium'=>'Plan Premium'];
$title = $ok ? 'Pago confirmado' : 'Pago no realizado';

render_header($title);
?>
<div class="home-hero">
  <h2><?= htmlspecialchars($title) ?></h2>

  <?php if ($ok): ?>
    <div class="card" style="max-width:520px;margin-inline:auto;text-align:center;">
      <p>¡Gracias! Activamos tu <strong><?= htmlspecialchars($names[$plan] ?? $plan) ?></strong>.</p>
      <p><strong>Método:</strong> <?= htmlspecialchars(ucfirst($method)) ?> •
         <strong>Periodo:</strong> <?= htmlspecialchars($cycle) ?> •
         <strong>Valor:</strong> <?= '$'.number_format($amt,0,',','.') ?></p>
      <p><strong>Próxima renovación:</strong> <?= htmlspecialchars($next) ?></p>
    </div>

    <p style="margin-top:12px;">
      <a class="btn primary" href="index.php?r=content">Ver contenido</a>
      <a class="btn primary" href="index.php?r=schedule" style="margin-left:8px;">Agendar entrenamiento/cita</a>
      <a class="btn" href="index.php?r=notifications" style="margin-left:8px;">Notificaciones</a>
    </p>
  <?php else: ?>
    <div class="card" style="max-width:520px;margin-inline:auto;text-align:center;">
      <p>No pudimos procesar el pago.</p>
      <p><a class="btn" href="index.php?r=pay&plan=<?= htmlspecialchars($plan) ?>">Intentar nuevamente</a></p>
    </div>
  <?php endif; ?>
</div>
<?php render_footer(); ?>



