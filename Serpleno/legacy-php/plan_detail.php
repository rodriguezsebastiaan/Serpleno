<?php
// plan_detail.php
require_once __DIR__.'/db.php';
require_once __DIR__.'/layout.php';
require_once __DIR__.'/auth.php';

$planKey = $_GET['plan'] ?? 'gratuito';
$validKeys = ['gratuito','silver','premium'];
if (!in_array($planKey, $validKeys, true)) {
  header('Location: index.php?r=plans');
  exit;
}

/* =========================
   Acciones POST
   ========================= */

// Activar plan gratuito (SESSION + BD si existe) y enviar a contenido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['__activate']) && $planKey === 'gratuito') {
  $u = current_user();
  if ($u) {
    if (function_exists('db_ready') && db_ready()) {
      try {
        $upd = pdo()->prepare('UPDATE users SET plan=? WHERE id=?');
        $upd->execute(['gratuito', $u['id']]);
      } catch (Throwable $e) {}
    }
    $_SESSION['user']['plan'] = 'gratuito';
  }
  header('Location: index.php?r=content');
  exit;
}

// Guardar feedback (demo o BD)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['__feedback'])) {
  $rating    = max(1, min(5, intval($_POST['rating'] ?? 5)));
  $recommend = isset($_POST['recommend']) ? 1 : 0;
  $comment   = trim($_POST['comment'] ?? '');

  if ($comment !== '') {
    if (function_exists('db_ready') && db_ready()) {
      try {
        pdo()->exec("
          CREATE TABLE IF NOT EXISTS plan_feedback (
            id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
            plan_key TEXT NOT NULL,
            rating SMALLINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
            recommend SMALLINT NOT NULL CHECK (recommend IN (0,1)),
            comment TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT now()
          );
        ");
        $stmt = pdo()->prepare("INSERT INTO plan_feedback (plan_key, rating, recommend, comment) VALUES (?,?,?,?)");
        $stmt->execute([$planKey, $rating, $recommend, $comment]);
      } catch (Throwable $e) { /* ignore */ }
    } else {
      $_SESSION['feedback'][$planKey][] = [
        'rating'=>$rating,
        'recommend'=>$recommend,
        'comment'=>$comment,
        'created_at'=>date('Y-m-d H:i:s')
      ];
    }
  }
  header("Location: index.php?r=plan_detail&plan=".urlencode($planKey));
  exit;
}

/* =========================
   Datos del plan (demo/BD)
   ========================= */

function demo_plans(){
  // Características según indicaciones
  $gratis  = ['Contenido estándar','Eventos abiertos','Comunidad de lectura'];
  $silver  = array_merge($gratis, [
    'Gestión de perfiles','Recordatorios','Eventos exclusivos','Sesiones con expertos',
    'Plan personalizado','Coach grupal','Estadísticas'
  ]);
  $premium = array_merge($silver, [
    'Coach personalizado','Contenido premium'
  ]);

  return [
    'gratuito' => [ 'name'=>'Plan Gratis',   'features'=>$gratis  ],
    'silver'   => [ 'name'=>'Plan Silver',   'features'=>$silver  ],
    'premium'  => [ 'name'=>'Plan Premium',  'features'=>$premium ],
  ];
}

if (function_exists('db_ready') && db_ready()) {
  // Si tienes tablas 'plans' y 'plan_features' las tomamos; si no, demo
  try {
    $stmt = pdo()->prepare('SELECT * FROM plans WHERE `key` = ?');
    $stmt->execute([$planKey]);
    $plan = $stmt->fetch();
    if ($plan) {
      $fs = pdo()->prepare('SELECT text FROM plan_features WHERE plan_key=?');
      $fs->execute([$planKey]);
      $features = array_map(fn($r)=>$r['text'], $fs->fetchAll());
    } else {
      $demo     = demo_plans();
      $plan     = ['key'=>$planKey, 'name'=>$demo[$planKey]['name']];
      $features = $demo[$planKey]['features'];
    }
  } catch (Throwable $e) {
    $demo     = demo_plans();
    $plan     = ['key'=>$planKey, 'name'=>$demo[$planKey]['name']];
    $features = $demo[$planKey]['features'];
  }

  // Feedback
  try {
    $rows = pdo()->prepare('SELECT rating, recommend, comment, created_at FROM plan_feedback WHERE plan_key=? ORDER BY id DESC');
    $rows->execute([$planKey]);
    $feedback = $rows->fetchAll() ?: [];
  } catch (Throwable $e) {
    $feedback = [];
  }
} else {
  $demo     = demo_plans();
  $plan     = ['key'=>$planKey, 'name'=>$demo[$planKey]['name']];
  $features = $demo[$planKey]['features'];
  $feedback = $_SESSION['feedback'][$planKey] ?? [];
}

/* =========================
   Stats de feedback
   ========================= */
$ratingsCount = [1=>0,2=>0,3=>0,4=>0,5=>0];
$recoYes = 0; $recoNo = 0;
foreach ($feedback as $f) {
  $r = (int)$f['rating'];
  if (isset($ratingsCount[$r])) $ratingsCount[$r]++;
  if (!empty($f['recommend'])) $recoYes++; else $recoNo++;
}
$totalFb = array_sum($ratingsCount);
$avg     = $totalFb ? array_sum(array_map(fn($n,$k)=>$n*$k, $ratingsCount, array_keys($ratingsCount))) / $totalFb : 0;
$pctYes  = $recoYes + $recoNo ? round(($recoYes/($recoYes+$recoNo))*100) : 0;

/* =========================
   Render
   ========================= */
render_header('Detalle del plan');
?>
<h2><?= htmlspecialchars($plan['name']) ?></h2>

<!-- CTA arriba -->
<div style="margin-bottom:12px; display:flex; gap:8px; justify-content:center; flex-wrap:wrap;">
  <?php if ($planKey === 'gratuito'): ?>
    <form method="post" action="index.php?r=plan_detail&plan=gratuito" style="display:inline-block;">
      <input type="hidden" name="__activate" value="1">
      <button class="btn primary" type="submit">Ingresar / Activar</button>
    </form>
    <a class="btn" href="index.php?r=content">Ver contenido</a>
  <?php elseif ($planKey === 'silver'): ?>
    <!-- Silver va DIRECTO a método de pago (sin validar estudiante) -->
    <a class="btn primary" href="index.php?r=pay&plan=silver">Ir a pagar</a>
    <a class="btn" href="index.php?r=content">Ver contenido</a>
  <?php elseif ($planKey === 'premium'): ?>
    <a class="btn primary" href="index.php?r=pay&plan=premium">Ir a pagar</a>
    <a class="btn" href="index.php?r=content">Ver contenido</a>
  <?php endif; ?>
</div>

<div class="plan-detail">
  <section class="card">
    <h3>Características</h3>
    <ul class="centered-list">
      <?php foreach ($features as $f): ?>
        <li>✔ <?= htmlspecialchars($f) ?></li>
      <?php endforeach; ?>
    </ul>
  </section>

  <section class="card">
    <h3>Calificaciones</h3>
    <p><strong>Promedio:</strong> <?= number_format($avg, 2) ?> / 5 (<?= $totalFb ?> votos)</p>
    <div class="bars">
      <?php for ($i=5;$i>=1;$i--): $pct = $totalFb ? round(($ratingsCount[$i]/$totalFb)*100) : 0; ?>
        <div class="bar-row">
          <span class="bar-label"><?= $i ?>★</span>
          <div class="bar">
            <div class="bar-fill" style="width: <?= $pct ?>%;"></div>
          </div>
          <span class="bar-pct"><?= $pct ?>%</span>
        </div>
      <?php endfor; ?>
    </div>

    <h4 style="margin-top:14px;">¿Recomendarían la app?</h4>
    <div class="pie-wrap">
      <div class="pie" style="--yes: <?= $pctYes ?>;"></div>
      <div class="legend">
        <span class="yes-box"></span> Sí (<?= $pctYes ?>%)
        <span class="no-box" style="margin-left:12px;"></span> No (<?= 100-$pctYes ?>%)
      </div>
    </div>
  </section>

  <section class="card">
    <h3>Deja tu comentario</h3>
    <form method="post" class="feedback-form">
      <input type="hidden" name="__feedback" value="1">
      <label>Calificación
        <select name="rating" required>
          <option value="5">5 ★★★★★</option>
          <option value="4">4 ★★★★</option>
          <option value="3">3 ★★★</option>
          <option value="2">2 ★★</option>
          <option value="1">1 ★</option>
        </select>
      </label>
      <label><input type="checkbox" name="recommend"> Recomiendo esta app</label>
      <label>Comentario
        <textarea name="comment" rows="3" placeholder="Escribe tu experiencia…" required></textarea>
      </label>
      <button class="btn primary" type="submit">Enviar</button>
    </form>
  </section>

  <section class="card">
    <h3>Comentarios recientes</h3>
    <?php if (!$feedback): ?>
      <p>Aún no hay comentarios. ¡Sé el primero!</p>
    <?php else: ?>
      <ul class="comments centered-list">
        <?php foreach ($feedback as $f): ?>
          <li class="comment">
            <div class="comment-head">
              <span class="stars"><?= str_repeat('★', (int)$f['rating']) . str_repeat('☆', 5-(int)$f['rating']) ?></span>
              <span class="dot"></span>
              <span class="date"><?= htmlspecialchars($f['created_at'] ?? '') ?></span>
              <span class="reco-tag <?= !empty($f['recommend'])?'yes':'no' ?>">
                <?= !empty($f['recommend'])?'Recomienda':'No recomienda' ?>
              </span>
            </div>
            <p><?= nl2br(htmlspecialchars($f['comment'])) ?></p>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</div>

<?php render_footer(); ?>









