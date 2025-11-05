<?php
// notifications.php
require_once __DIR__.'/layout.php';
require_once __DIR__.'/auth.php';

$user = current_user();
if (!$user) { header('Location: index.php?r=login'); exit; }

$plan = strtolower($user['plan'] ?? 'gratuito');
$email = $user['email'] ?? 'anon@serpleno.test';
$name  = $user['name']  ?? 'Usuario';

// Normalizar etiqueta visible del plan (soporta "estudiantil" -> "Silver")
function plan_label(string $p): string {
  $p = strtolower($p);
  if ($p === 'estudiantil' || $p === 'silver') return 'Silver';
  if ($p === 'premium') return 'Premium';
  return 'Gratuito';
}

$is_paid = in_array($plan, ['premium','silver','estudiantil'], true);

// ============================
// Próxima renovación (demo)
// ============================
// Si ya la tenemos en sesión, úsala; si no, la calculamos (30 días desde hoy).
if (!isset($_SESSION['billing'][$email]['next_renewal'])) {
  $_SESSION['billing'][$email]['next_renewal'] = date('Y-m-d', strtotime('+30 days'));
}
$nextRenewal = $_SESSION['billing'][$email]['next_renewal'];

// Si quieres que cambie según frecuencia real, podrías guardar en sesión:
// $_SESSION['billing'][$email]['cycle'] = 'mensual'|'semestral'|'anual';
$cycle = $_SESSION['billing'][$email]['cycle'] ?? 'mensual';

// ============================
// Sesiones agendadas (demo)
// ============================
// Fuente: lo que guardes en tu schedule.php (puedes alimentar esta estructura)
$bookings = $_SESSION['bookings'][$email] ?? [
  // de ejemplo:
  // ['title'=>'Entrenamiento funcional', 'when'=>'2025-09-10 08:00', 'pro'=>'Jefferson'],
  // ['title'=>'Sesión con psicóloga', 'when'=>'2025-09-12 18:30', 'pro'=>'Carolina'],
];

// ============================
// Render
// ============================
render_header('Notificaciones');
?>
<div class="home-hero" style="max-width:920px;margin:0 auto;text-align:center;">
  <h2 style="margin:6px 0 10px;">Notificaciones</h2>
  <p style="color:#556;margin:0 auto 12px;max-width:760px">
    Hola <strong><?= htmlspecialchars($name) ?></strong>. Revisa aquí tu próxima renovación y accesos rápidos a tus sesiones.
  </p>

  <?php if (!$is_paid): ?>
    <!-- Gratis -->
    <section class="card" style="max-width:760px;text-align:center;">
      <div class="alert danger" style="margin-top:0">Funcionalidad disponible en planes Silver y Premium.</div>
      <p style="color:#667;margin:8px 0">
        Actualiza tu plan para recibir recordatorios de pagos, unirte a entrenamientos/citas en vivo y más.
      </p>
      <a class="btn primary" href="index.php?r=plans">Ver planes</a>
    </section>
  <?php else: ?>
    <!-- Pagos: Silver/Premium -->
    <section class="card" style="max-width:860px;text-align:center;">
      <h3 style="margin:0 0 8px 0;">Estado de suscripción</h3>
      <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-bottom:8px">
        <span class="badge">Plan: <?= htmlspecialchars(plan_label($plan)) ?></span>
        <span class="badge">Ciclo: <?= htmlspecialchars(ucfirst($cycle)) ?></span>
      </div>

      <p style="font-size:16px;margin:8px 0;color:#334">
        <strong>Próxima renovación:</strong>
        <?= date('d/m/Y', strtotime($nextRenewal)) ?>
      </p>

      <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:8px">
        <a class="btn primary" href="index.php?r=pay">Pagar ahora</a>
        <a class="btn" href="index.php?r=meeting">Entrar a la sala</a>
      </div>
    </section>

    <section class="card" style="max-width:860px;text-align:center;margin-top:12px;">
      <h3 style="margin:0 0 8px 0;">Próximas sesiones</h3>
      <?php if (!$bookings): ?>
        <p style="color:#667;margin:6px 0">Aún no tienes sesiones agendadas.</p>
        <a class="btn outline" href="index.php?r=schedule">Agendar entrenamiento/cita</a>
      <?php else: ?>
        <div class="bookings" style="display:grid;grid-template-columns:repeat(1,minmax(240px,1fr));gap:10px;max-width:760px;margin:0 auto;">
          <?php foreach ($bookings as $b): ?>
            <div class="pro-card" style="text-align:center;">
              <h4 style="margin:0 0 6px 0;"><?= htmlspecialchars($b['title'] ?? 'Sesión') ?></h4>
              <div style="color:#666;margin-bottom:6px;">
                <?= htmlspecialchars(date('d/m/Y g:i a', strtotime($b['when'] ?? 'now'))) ?>
                <?php if (!empty($b['pro'])): ?>
                  • con <?= htmlspecialchars($b['pro']) ?>
                <?php endif; ?>
              </div>
              <a class="btn" href="index.php?r=meeting">Entrar a la sala</a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <p style="margin:12px 0;">
    <a class="btn outline" href="index.php?r=home">Volver al inicio</a>
  </p>
</div>
<?php render_footer(); ?>





