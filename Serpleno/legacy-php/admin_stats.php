<?php
// admin_stats.php
require_once __DIR__.'/layout.php';
require_once __DIR__.'/auth.php';

// ==== Guardia: solo admin ====
$u = current_user();
if (!$u || ($u['role'] ?? '') !== 'admin') {
  header('Location: index.php?r=login'); exit;
}

// ==== Utilidades ====
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
$useDb = function_exists('db_ready') && db_ready();

$today = new DateTime('today');
$default_from = (clone $today)->modify('-30 days')->format('Y-m-d');
$default_to   = $today->format('Y-m-d');

$from = $_GET['from'] ?? $default_from;
$to   = $_GET['to']   ?? $default_to;

// Normaliza rango (fin del día para consultas con BETWEEN)
$fromTs = strtotime($from.' 00:00:00') ?: strtotime($default_from.' 00:00:00');
$toTs   = strtotime($to.' 23:59:59')   ?: strtotime($default_to.' 23:59:59');

$stats = [
  'total_users'      => 0,
  'plan_counts'      => ['gratuito'=>0,'silver'=>0,'premium'=>0],
  'active_users'     => null, // puede quedar null si no hay datos
  'inactive_users'   => null,
  'new_users'        => null,
  'sessions_by_cat'  => ['entrenamientos'=>0,'psicologia'=>0,'nutricion'=>0,'coach'=>0],
];

// ====== Carga de datos ======
if ($useDb) {
  try {
    // Total y planes (si no existe "silver" en BD usa alias "estudiantil")
    $stats['total_users'] = (int)pdo()->query("SELECT COUNT(*) FROM users")->fetchColumn();

    $byPlan = pdo()->query("SELECT plan, COUNT(*) n FROM users GROUP BY plan");
    foreach ($byPlan as $r) {
      $plan = strtolower((string)$r['plan']);
      $n = (int)$r['n'];
      if ($plan === 'estudiantil') $plan = 'silver';
      if (!isset($stats['plan_counts'][$plan])) $stats['plan_counts'][$plan] = 0;
      $stats['plan_counts'][$plan] += $n;
    }

    // Usuarios activos e inactivos:
    // Si existe tabla sessions con user_id y start_time, la usamos.
    $active = null;
    try {
      $q = pdo()->prepare("
        SELECT COUNT(DISTINCT user_id) AS n
        FROM sessions
        WHERE start_time BETWEEN FROM_UNIXTIME(?) AND FROM_UNIXTIME(?)
      ");
      $q->execute([$fromTs, $toTs]);
      $active = (int)$q->fetchColumn();
    } catch (Throwable $e) { /* tabla no existe */ }

    if ($active !== null) {
      $stats['active_users']   = $active;
      $stats['inactive_users'] = max(0, $stats['total_users'] - $active);
    }

    // Usuarios nuevos (si existe created_at en users)
    $newUsers = null;
    try {
      $q = pdo()->prepare("
  SELECT COUNT(DISTINCT user_id) AS n
  FROM sessions
  WHERE start_time BETWEEN to_timestamp(?) AND to_timestamp(?)
");
$q->execute([$fromTs, $toTs]); // $fromTs/$toTs ya están en segundos UNIX
$active = (int)$q->fetchColumn();
    } catch (Throwable $e) { /* columna no existe */ }
    if ($newUsers !== null) $stats['new_users'] = $newUsers;

    // Sesiones por categoría (si la tabla sessions tiene category)
    try {
      $q = pdo()->prepare("
        SELECT category, COUNT(*) n
        FROM sessions
        WHERE start_time BETWEEN FROM_UNIXTIME(?) AND FROM_UNIXTIME(?)
        GROUP BY category
      ");
      $q->execute([$fromTs, $toTs]);
      $tmp = ['entrenamientos'=>0,'psicologia'=>0,'nutricion'=>0,'coach'=>0];
      foreach ($q as $r) {
        $c = strtolower((string)$r['category']);
        $n = (int)$r['n'];
        if (!isset($tmp[$c])) $tmp[$c] = 0;
        $tmp[$c] += $n;
      }
      $stats['sessions_by_cat'] = $tmp;
    } catch (Throwable $e) { /* sin tabla o sin columna */ }

  } catch (Throwable $e) {
    // Si algo falla, caemos al demo
    $useDb = false;
  }
}

if (!$useDb) {
  // ===== DEMO (sin BD): cifras de ejemplo consistentes con los planes
  $stats['total_users'] = 12;
  $stats['plan_counts'] = ['gratuito'=>5,'silver'=>4,'premium'=>3];
  $stats['active_users'] = 7;
  $stats['inactive_users'] = max(0, $stats['total_users'] - $stats['active_users']);
  $stats['new_users'] = 3;
  $stats['sessions_by_cat'] = ['entrenamientos'=>8,'psicologia'=>3,'nutricion'=>2,'coach'=>4];
}

// ====== Exportación CSV ======
if (isset($_GET['export']) && $_GET['export'] === '1') {
  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="serpleno_stats_'.date('Ymd_His').'.csv"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['Métrica','Valor','Desde','Hasta','Generado']);
  fputcsv($out, ['Usuarios totales',$stats['total_users'],$from,$to,date('Y-m-d H:i:s')]);
  fputcsv($out, ['Usuarios activos',$stats['active_users'] ?? '—',$from,$to,date('Y-m-d H:i:s')]);
  fputcsv($out, ['Usuarios inactivos',$stats['inactive_users'] ?? '—',$from,$to,date('Y-m-d H:i:s')]);
  fputcsv($out, ['Usuarios nuevos',$stats['new_users'] ?? '—',$from,$to,date('Y-m-d H:i:s')]);
  foreach ($stats['plan_counts'] as $k=>$v) {
    fputcsv($out, ['Plan '.$k, $v, $from,$to,date('Y-m-d H:i:s')]);
  }
  foreach ($stats['sessions_by_cat'] as $k=>$v) {
    fputcsv($out, ['Sesiones '.$k, $v, $from,$to,date('Y-m-d H:i:s')]);
  }
  fclose($out);
  exit;
}

render_header('Estadísticas');
?>
<style>
  /* Contenedor centrado y gráficos compactos */
  .stats-wrap{max-width:980px;margin:0 auto;text-align:center}
  .kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;max-width:980px;margin:10px auto}
  @media(max-width:820px){.kpis{grid-template-columns:repeat(2,1fr)}}
  .kpi{background:#fff;border:1px solid #eee;border-radius:12px;padding:12px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
  .kpi h4{margin:0 0 6px 0} .kpi p{margin:0;color:#555;font-size:20px;font-weight:700}

  .charts{display:grid;gap:12px;grid-template-columns:repeat(2,1fr);max-width:980px;margin:10px auto}
  @media(max-width:900px){.charts{grid-template-columns:1fr}}
  .chart-card{background:#fff;border:1px solid #eee;border-radius:12px;padding:12px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
  .chart-title{margin:0 0 8px 0}

  /* Barra comparativa (activos vs inactivos) pequeña */
  .bar-compare{width:100%;max-width:480px;height:18px;border-radius:999px;background:#eee;margin:0 auto;position:relative;overflow:hidden}
  .bar-compare .active{height:100%;background:#2f8d7e}

  /* Pies compactos */
  .pie{--pct:0;width:160px;height:160px;border-radius:50%;
       background:conic-gradient(#2f8d7e calc(var(--pct)*1%), #e0e0e0 0);
       margin:0 auto;box-shadow:0 2px 8px rgba(0,0,0,.06)}
  .legend{display:flex;justify-content:center;gap:10px;flex-wrap:wrap;margin-top:8px}
  .leg{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#556}
  .sw{width:10px;height:10px;border-radius:3px;display:inline-block}
  .sw.p1{background:#2f8d7e}.sw.p2{background:#b0dfd7}.sw.p3{background:#d4af37}.sw.px{background:#e0e0e0}

  /* Barras por plan (mini) */
  .plans-bars{max-width:520px;margin:0 auto}
  .row{display:flex;align-items:center;gap:8px;margin:6px 0;justify-content:center}
  .row label{width:90px;text-align:right;font-size:13px;color:#556}
  .row .bar{flex:1;height:12px;background:#eee;border-radius:999px;overflow:hidden}
  .row .fill{height:100%;background:#2f8d7e}

  /* Filtro y acciones */
  .filters{display:flex;gap:8px;justify-content:center;align-items:center;flex-wrap:wrap;margin:8px 0 12px}
  .filters input{padding:6px;border:1px solid #ddd;border-radius:8px}
</style>

<div class="stats-wrap">
  <h2 style="margin:6px 0 10px;">Estadísticas</h2>

  <form class="filters" method="get" action="index.php">
    <input type="hidden" name="r" value="admin_stats">
    <label>Desde: <input type="date" name="from" value="<?= h(date('Y-m-d',$fromTs)) ?>"></label>
    <label>Hasta: <input type="date" name="to" value="<?= h(date('Y-m-d',$toTs)) ?>"></label>
    <button class="btn primary" type="submit">Aplicar</button>
    <a class="btn outline" href="index.php?r=admin_stats&from=<?= h($default_from) ?>&to=<?= h($default_to) ?>">Últimos 30 días</a>
    <a class="btn" href="index.php?r=admin_stats&from=<?= h(date('Y-01-01')) ?>&to=<?= h(date('Y-m-d')) ?>">Año en curso</a>
    <a class="btn primary" href="index.php?r=admin_stats&from=<?= h(date('Y-m-d',$fromTs)) ?>&to=<?= h(date('Y-m-d',$toTs)) ?>&export=1">Descargar CSV</a>
  </form>

  <!-- KPIs -->
  <section class="kpis">
    <div class="kpi">
      <h4>Total usuarios</h4>
      <p><?= (int)$stats['total_users'] ?></p>
    </div>
    <div class="kpi">
      <h4>Activos</h4>
      <p><?= $stats['active_users'] !== null ? (int)$stats['active_users'] : '—' ?></p>
    </div>
    <div class="kpi">
      <h4>Inactivos</h4>
      <p><?= $stats['inactive_users'] !== null ? (int)$stats['inactive_users'] : '—' ?></p>
    </div>
    <div class="kpi">
      <h4>Nuevos</h4>
      <p><?= $stats['new_users'] !== null ? (int)$stats['new_users'] : '—' ?></p>
    </div>
  </section>

  <section class="charts">
    <!-- Activos vs Inactivos (barra pequeña) -->
    <div class="chart-card">
      <h3 class="chart-title">Usuarios activos vs inactivos</h3>
      <?php
        $a = (int)($stats['active_users'] ?? 0);
        $i = (int)($stats['inactive_users'] ?? 0);
        $tot = max(1, $a + $i);
        $pct = round(($a / $tot) * 100);
      ?>
      <div class="bar-compare" title="Activos: <?= $a ?> / Inactivos: <?= $i ?>">
        <div class="active" style="width: <?= $pct ?>%"></div>
      </div>
      <div class="legend">
        <span class="leg"><span class="sw p1"></span> Activos (<?= $a ?>)</span>
        <span class="leg"><span class="sw px"></span> Inactivos (<?= $i ?>)</span>
      </div>
    </div>

    <!-- Usuarios nuevos (pie pequeño) -->
    <div class="chart-card">
      <h3 class="chart-title">Usuarios nuevos (rango)</h3>
      <?php
        $new = (int)($stats['new_users'] ?? 0);
        $base = max(1, (int)$stats['total_users']);
        $pctNew = round(($new / $base) * 100);
      ?>
      <div class="pie" style="--pct: <?= $pctNew ?>"></div>
      <div class="legend">
        <span class="leg"><span class="sw p1"></span> Nuevos: <?= $new ?></span>
        <span class="leg"><span class="sw px"></span> Resto</span>
      </div>
    </div>

    <!-- Planes vendidos / distribución por plan (mini barras) -->
    <div class="chart-card">
      <h3 class="chart-title">Distribución por plan</h3>
      <?php
        $pc = $stats['plan_counts'];
        $mx = max(1, max($pc));
      ?>
      <div class="plans-bars">
        <?php foreach (['gratuito','silver','premium'] as $k):
          $v = (int)($pc[$k] ?? 0);
          $w = round(($v / $mx) * 100);
        ?>
          <div class="row">
            <label><?= ucfirst($k) ?></label>
            <div class="bar"><div class="fill" style="width: <?= $w ?>%"></div></div>
            <span style="width:36px;text-align:left;font-size:13px;"><?= $v ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Sesiones por categoría (pie pequeño) -->
    <div class="chart-card">
      <h3 class="chart-title">Sesiones por categoría</h3>
      <?php
        $sb = $stats['sessions_by_cat'];
        $sTot = array_sum($sb) ?: 1;
        $p1 = round(($sb['entrenamientos'] / $sTot) * 100);
        $p2 = round(($sb['psicologia']    / $sTot) * 100);
        $p3 = round(($sb['nutricion']     / $sTot) * 100);
        $p4 = 100 - $p1 - $p2 - $p3;
        // Pie multicolor: usamos grados acumulados
      ?>
      <div style="width:160px;height:160px;border-radius:50%;margin:0 auto;
                  background:
                   conic-gradient(#2f8d7e 0 <?= $p1 ?>%,
                                  #b0dfd7 <?= $p1 ?>% <?= $p1+$p2 ?>%,
                                  #d4af37 <?= $p1+$p2 ?>% <?= $p1+$p2+$p3 ?>%,
                                  #e0e0e0 <?= $p1+$p2+$p3 ?>% 100%);
                  box-shadow:0 2px 8px rgba(0,0,0,.06);">
      </div>
      <div class="legend">
        <span class="leg"><span class="sw p1"></span> Entrenamientos (<?= (int)$sb['entrenamientos'] ?>)</span>
        <span class="leg"><span class="sw p2"></span> Psicología (<?= (int)$sb['psicologia'] ?>)</span>
        <span class="leg"><span class="sw p3"></span> Nutrición (<?= (int)$sb['nutricion'] ?>)</span>
        <span class="leg"><span class="sw px"></span> Coach (<?= (int)$sb['coach'] ?>)</span>
      </div>
    </div>
  </section>

  <p style="margin:12px 0;">
    <a class="btn outline" href="index.php?r=admin_dashboard">Volver al panel</a>
  </p>
</div>
<?php render_footer(); ?>

