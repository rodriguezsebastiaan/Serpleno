<?php
// plans.php
require_once __DIR__.'/layout.php';

render_header('Planes');

/* =========================
   Definición de planes y precios
   ========================= */
$planNames = [
  'gratuito' => 'Plan Gratis',
  'silver'   => 'Plan Silver',
  'premium'  => 'Plan Premium',
];

// precios (solo mensual y anual como pediste)
$planPrices = [
  'gratuito' => ['monthly'=>0,      'annual'=>0],
  'silver'   => ['monthly'=>130000, 'annual'=>1200000],
  'premium'  => ['monthly'=>200000, 'annual'=>2000000],
];

// helper formato dinero
function money($n){ return '$'.number_format((float)$n, 0, ',', '.'); }

/* =========================
   Matriz de características (según tu lista)
   =========================
   ✔ Gratis, Silver, Premium:
     - Contenido estándar
     - Eventos abiertos
     - Comunidad de lectura
   ✔ Silver y Premium:
     - Gestión de perfiles
     - Recordatorios
     - Eventos exclusivos
     - Sesiones con expertos
     - Plan personalizado
     - Coach grupal
     - Estadísticas
   ✔ Solo Premium:
     - Coach personalizado
     - Contenido premium
*/
$featuresSpec = [
  'Contenido estándar'    => ['gratuito','silver','premium'],
  'Eventos abiertos'      => ['gratuito','silver','premium'],
  'Comunidad de lectura'  => ['gratuito','silver','premium'],

  'Gestión de perfiles'   => ['silver','premium'],
  'Recordatorios'         => ['silver','premium'],
  'Eventos exclusivos'    => ['silver','premium'],
  'Sesiones con expertos' => ['silver','premium'],
  'Plan personalizado'    => ['silver','premium'],
  'Coach grupal'          => ['silver','premium'],
  'Estadísticas'          => ['silver','premium'],

  'Coach personalizado'   => ['premium'],
  'Contenido premium'     => ['premium'],
];

// orden de columnas
$planKeys = ['gratuito','silver','premium'];

// lista de features por plan (para las tarjetas)
$featuresByPlan = [
  'gratuito' => [],
  'silver'   => [],
  'premium'  => [],
];
foreach ($featuresSpec as $feat => $whoHas) {
  foreach ($whoHas as $k) $featuresByPlan[$k][] = $feat;
}
?>
<h2 class="section-title">Comparativa de Planes</h2>

<div class="compare-card">
  <div class="table-wrapper">
    <table class="compare">
      <thead>
        <tr>
          <th>Características</th>
          <?php foreach ($planKeys as $k): ?>
            <th>
              <?= htmlspecialchars($planNames[$k]) ?><br>
              <small>
                <?= money($planPrices[$k]['monthly']) ?>/mes ·
                <?= money($planPrices[$k]['annual']) ?>/año
              </small>
            </th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($featuresSpec as $feat => $whoHas): ?>
          <tr>
            <td class="feat"><?= htmlspecialchars($feat) ?></td>
            <?php foreach ($planKeys as $k):
                $has = in_array($k, $whoHas, true); ?>
              <td class="<?= $has ? 'yes' : 'no' ?>"><?= $has ? '✔' : '✖' ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<h3 class="section-title" style="margin-top:18px;">Elige tu plan</h3>

<div class="plans-grid">
  <?php foreach ($planKeys as $k): ?>
    <div class="plan-card plan-<?= htmlspecialchars($k) ?>">
      <h3 style="margin-bottom:4px;"><?= htmlspecialchars($planNames[$k]) ?></h3>
      <div style="margin:2px 0 8px;color:#556;font-size:14px;">
        <?= money($planPrices[$k]['monthly']) ?>/mes ·
        <?= money($planPrices[$k]['annual']) ?>/año
      </div>
      <ul class="features">
        <?php foreach ($featuresByPlan[$k] as $f): ?>
          <li>✔ <?= htmlspecialchars($f) ?></li>
        <?php endforeach; ?>
      </ul>
      <a class="btn primary" href="index.php?r=plan_detail&plan=<?= urlencode($k) ?>">Ingresar</a>
    </div>
  <?php endforeach; ?>
</div>

<?php render_footer(); ?>




