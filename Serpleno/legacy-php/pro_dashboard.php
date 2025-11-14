<?php
require_once __DIR__.'/layout.php';
require_once __DIR__.'/auth.php';

$user = current_user();
if (!$user) { header('Location: index.php?r=login'); exit; }
/* En tu app el rol del profesional es "profesional" */
if (($user['role'] ?? '') !== 'profesional') { header('Location: index.php?r=home'); exit; }

render_header('Panel del profesional');
?>
<div class="home-hero" style="max-width:860px;margin:0 auto;text-align:center">
  <h2 style="margin:6px 0 6px;">Bienvenido <?= htmlspecialchars($user['name'] ?? 'Profesional') ?></h2>
  <p style="color:#556;margin:0 0 12px;">Accede a tus herramientas</p>

  <!-- Tarjetas centradas en una columna, con botones primarios -->
  <div class="grid" style="
        display:grid;
        gap:14px;
        grid-template-columns:repeat(1,minmax(280px,360px));
        justify-content:center;
        justify-items:center;
        margin:0 auto;">
    
    <section class="card" style="width:100%;max-width:360px;text-align:center;">
      <h3 style="margin-top:0;">Calendario</h3>
      <p style="color:#666;margin-top:0">Administra tu disponibilidad semanal.</p>
      <a class="btn primary" href="index.php?r=pro_calendar">Abrir calendario</a>
    </section>

    <section class="card" style="width:100%;max-width:360px;text-align:center;">
      <h3 style="margin-top:0;">Notificaciones</h3>
      <p style="color:#666;margin-top:0">Recordatorios y mensajes para profesionales.</p>
      <a class="btn primary" href="index.php?r=pro_notifications">Ver notificaciones</a>
    </section>

    <section class="card" style="width:100%;max-width:360px;text-align:center;">
      <h3 style="margin-top:0;">Subir contenido</h3>
      <p style="color:#666;margin-top:0">Carga videos, imágenes o guías para tus clientes.</p>
      <a class="btn primary" href="index.php?r=pro_upload">Ir a subir contenido</a>
    </section>
  </div>
</div>
<?php render_footer(); ?>




