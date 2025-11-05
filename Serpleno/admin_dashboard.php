<?php
// admin_dashboard.php
require_once __DIR__.'/layout.php';
require_once __DIR__.'/guard.php';

if (function_exists('require_admin')) {
    require_admin();
} else {
    $u = current_user();
    if (!$u || ($u['role'] ?? '') !== 'admin') {
        header('Location: index.php?r=login');
        exit;
    }
}

$user = current_user();
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ===============================
// KPIs (usa BD si está disponible)
// ===============================
$total = $gratuito = $estudiantil = $premium = 0;

$useDb = function_exists('db_ready') && db_ready();
if ($useDb) {
    try {
        $total = (int)pdo()->query("SELECT COUNT(*) AS n FROM users")->fetchColumn();

        $q = pdo()->query("
            SELECT plan, COUNT(*) AS n
            FROM users
            GROUP BY plan
        ");
        foreach ($q as $r) {
            $plan = (string)$r['plan'];
            $n    = (int)$r['n'];
            if ($plan === 'gratuito')   $gratuito   = $n;
            if ($plan === 'estudiantil')$estudiantil= $n;
            if ($plan === 'premium')    $premium    = $n;
        }
    } catch (Throwable $e) {
        // si algo falla, dejamos en 0
    }
} else {
    // DEMO (sin BD)
    $total = 3;
    $gratuito = 1; $estudiantil = 1; $premium = 1;
}

render_header('Panel del Administrador');
?>
<div class="home-hero">
  <h2>Hola <?= h($user['name'] ?? 'Admin') ?>, este es tu panel</h2>
  <p style="margin:6px 0 14px;color:#556;max-width:820px;margin-inline:auto">
    Desde aquí puedes revisar estadísticas, gestionar el contenido global y administrar perfiles (usuarios, profesionales, administradores).
  </p>

  <section class="kpis">
    <div class="kpi">
      <h4>Total usuarios</h4>
      <p style="font-size:22px;font-weight:700"><?= $total ?></p>
    </div>
    <div class="kpi">
      <h4>Gratuitos</h4>
      <p style="font-size:22px;font-weight:700"><?= $gratuito ?></p>
    </div>
    <div class="kpi">
      <h4>Estudiantiles</h4>
      <p style="font-size:22px;font-weight:700"><?= $estudiantil ?></p>
    </div>
    <div class="kpi">
      <h4>Premium</h4>
      <p style="font-size:22px;font-weight:700"><?= $premium ?></p>
    </div>
  </section>

  <section class="card" style="max-width:980px;text-align:center;margin-top:12px">
    <h3 style="margin-top:0">Accesos rápidos</h3>
    <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
      <a class="btn primary" href="index.php?r=admin_stats">Estadísticas</a>
      <a class="btn primary" href="index.php?r=admin_content">Gestión de contenidos</a>
      <a class="btn primary" href="index.php?r=admin_users">Gestión de perfiles</a>
    </div>
    <p style="margin:10px 0;color:#667;font-size:13px">
      * Si no ves datos, asegúrate de tener la base de datos creada y la tabla <code>users</code> poblada.
    </p>
  </section>

  <p style="margin:12px 0;">
    <a class="btn outline" href="index.php?r=home">Ir al inicio</a>
    <a class="btn" href="index.php?r=logout">Cerrar sesión</a>
  </p>
</div>
<?php render_footer();
