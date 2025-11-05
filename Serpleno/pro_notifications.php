<?php
// pro_notifications.php
require_once __DIR__.'/layout.php';
require_once __DIR__.'/guard.php';

if (function_exists('require_profesional')) {
    require_profesional();
} else {
    // Fallback: si no existe el helper, verifica rol manualmente
    $u = current_user();
    if (!$u || ($u['role'] ?? '') !== 'profesional') {
        header('Location: index.php?r=login');
        exit;
    }
}

$user  = current_user();
$proId = (int)($user['id'] ?? 0);

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function fmt_ampm_es(DateTime $dt): string {
    $h = (int)$dt->format('H');
    $m = (int)$dt->format('i');
    $ampm = ($h < 12) ? 'a.m.' : 'p.m.';
    $h12 = $h % 12; if ($h12 === 0) $h12 = 12;
    return sprintf('%d:%02d %s', $h12, $m, $ampm);
}

// =====================================
//   BD si está disponible, si no SESSION
// =====================================
$useDb = function_exists('db_ready') && db_ready();
if ($useDb) {
    try {
        pdo()->exec("
            CREATE TABLE IF NOT EXISTS pro_notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                pro_id INT NOT NULL,
                type VARCHAR(30) NOT NULL,
                title VARCHAR(140) NOT NULL,
                body TEXT NULL,
                url VARCHAR(255) NULL,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                due_at DATETIME NULL,
                INDEX (pro_id, is_read, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } catch (Throwable $e) { $useDb = false; }
}

$SESS_KEY = 'pro_notif';
$PRO_KEY  = $proId > 0 ? 'id:'.$proId : 'email:'.($user['email'] ?? 'pro');
if (!$useDb && !isset($_SESSION[$SESS_KEY][$PRO_KEY])) $_SESSION[$SESS_KEY][$PRO_KEY] = [];

// ===================
//   Acciones UI
// ===================
$notice = null; $error = null;

// Generar DEMO
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['__demo'])) {
    try {
        $now = new DateTime('now');
        $soon = (clone $now)->modify('+1 hour');
        $meetUrl = 'index.php?r=meeting&room=pro-'.$proId.'-'.rand(1000,9999);

        $demos = [
            ['type'=>'booking','title'=>'Nueva sesión agendada','body'=>'Cliente: Ana Ruiz. Entrenamiento funcional.','url'=>$meetUrl,'due_at'=>$soon],
            ['type'=>'change','title'=>'Reagendado','body'=>'Carlos movió su cita a mañana 10:00.','url'=>null,'due_at'=>null],
            ['type'=>'cancel','title'=>'Cancelación','body'=>'Laura canceló su sesión del viernes.','url'=>null,'due_at'=>null],
            ['type'=>'reminder','title'=>'Recordatorio de clase','body'=>'Clase inicia en 60 minutos.','url'=>$meetUrl,'due_at'=>$soon],
        ];

        if ($useDb) {
            $ins = pdo()->prepare("INSERT INTO pro_notifications (pro_id,type,title,body,url,is_read,due_at) VALUES (?,?,?,?,?,0,?)");
            foreach ($demos as $d) {
                $ins->execute([$proId,$d['type'],$d['title'],$d['body'],$d['url'], $d['due_at']? $d['due_at']->format('Y-m-d H:i:s') : null]);
            }
        } else {
            foreach ($demos as $d) {
                $_SESSION[$SESS_KEY][$PRO_KEY][] = [
                    'id'        => time().rand(100,999),
                    'pro_id'    => $proId,
                    'type'      => $d['type'],
                    'title'     => $d['title'],
                    'body'      => $d['body'],
                    'url'       => $d['url'],
                    'is_read'   => 0,
                    'created_at'=> date('Y-m-d H:i:s'),
                    'due_at'    => $d['due_at']? $d['due_at']->format('Y-m-d H:i:s') : null,
                ];
            }
        }
        $notice = 'Se agregaron notificaciones de demostración.';
    } catch (Throwable $e) { $error = 'No se pudieron crear notificaciones demo.'; }
}

// Marcar leída
if (isset($_GET['read'])) {
    $id = (int)$_GET['read'];
    try {
        if ($useDb) {
            pdo()->prepare("UPDATE pro_notifications SET is_read=1 WHERE id=? AND pro_id=?")->execute([$id,$proId]);
        } else {
            foreach (($_SESSION[$SESS_KEY][$PRO_KEY] ?? []) as &$n) {
                if ((int)($n['id']??0) === $id) { $n['is_read']=1; break; }
            }
        }
        $notice = 'Notificación marcada como leída.';
    } catch (Throwable $e) { $error = 'No se pudo marcar como leída.'; }
}

// Marcar no leída
if (isset($_GET['unread'])) {
    $id = (int)$_GET['unread'];
    try {
        if ($useDb) {
            pdo()->prepare("UPDATE pro_notifications SET is_read=0 WHERE id=? AND pro_id=?")->execute([$id,$proId]);
        } else {
            foreach (($_SESSION[$SESS_KEY][$PRO_KEY] ?? []) as &$n) {
                if ((int)($n['id']??0) === $id) { $n['is_read']=0; break; }
            }
        }
        $notice = 'Notificación marcada como no leída.';
    } catch (Throwable $e) { $error = 'No se pudo marcar como no leída.'; }
}

// Marcar todas como leídas
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['__read_all'])) {
    try {
        if ($useDb) {
            pdo()->prepare("UPDATE pro_notifications SET is_read=1 WHERE pro_id=?")->execute([$proId]);
        } else {
            foreach (($_SESSION[$SESS_KEY][$PRO_KEY] ?? []) as &$n) $n['is_read']=1;
        }
        $notice = 'Todas las notificaciones se marcaron como leídas.';
    } catch (Throwable $e) { $error = 'No se pudo completar la acción.'; }
}

// Eliminar leídas
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['__clear_read'])) {
    try {
        if ($useDb) {
            pdo()->prepare("DELETE FROM pro_notifications WHERE pro_id=? AND is_read=1")->execute([$proId]);
        } else {
            $list = $_SESSION[$SESS_KEY][$PRO_KEY] ?? [];
            $_SESSION[$SESS_KEY][$PRO_KEY] = array_values(array_filter($list, fn($n)=> (int)($n['is_read']??0) !== 1));
        }
        $notice = 'Notificaciones leídas eliminadas.';
    } catch (Throwable $e) { $error = 'No se pudo eliminar.'; }
}

// ===================
//   Filtro y carga
// ===================
$onlyUnread = (($_GET['filter'] ?? '') === 'unread');
$rows = [];
try {
    if ($useDb) {
        if ($onlyUnread) {
            $q = pdo()->prepare("SELECT * FROM pro_notifications WHERE pro_id=? AND is_read=0 ORDER BY created_at DESC");
            $q->execute([$proId]);
        } else {
            $q = pdo()->prepare("SELECT * FROM pro_notifications WHERE pro_id=? ORDER BY created_at DESC");
            $q->execute([$proId]);
        }
        $rows = $q->fetchAll() ?: [];
    } else {
        $all = $_SESSION[$SESS_KEY][$PRO_KEY] ?? [];
        if ($onlyUnread) {
            $rows = array_values(array_filter($all, fn($n)=> (int)($n['is_read']??0)===0));
        } else {
            $rows = $all;
        }
        usort($rows, fn($a,$b)=> strcmp(($b['created_at']??''), ($a['created_at']??'')));
    }
} catch (Throwable $e) { $rows = []; }

// ===================
//   Render
// ===================
render_header('Notificaciones (Profesional)');
?>
<div class="home-hero">
  <h2>Notificaciones del profesional</h2>
  <p style="margin:6px 0 14px;color:#556">Aquí verás reservas, cambios, cancelaciones y recordatorios. Todo centrado y listo para trabajar.</p>

  <?php if ($notice): ?><div class="alert success" style="max-width:900px;margin:0 auto"><?= h($notice) ?></div><?php endif; ?>
  <?php if ($error):  ?><div class="alert danger"  style="max-width:900px;margin:0 auto"><?= h($error)  ?></div><?php endif; ?>

  <p style="margin:10px 0;">
    <a class="btn outline" href="index.php?r=pro_dashboard">Volver al panel</a>
  </p>

  <section class="card" style="max-width:980px;text-align:center">
    <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;align-items:center">
      <a class="btn <?= $onlyUnread?'':'primary' ?>" href="index.php?r=pro_notifications">Ver todas</a>
      <a class="btn <?= $onlyUnread?'primary':'' ?>" href="index.php?r=pro_notifications&filter=unread">Solo no leídas</a>

      <form method="post" style="display:inline-block;margin-left:8px">
        <input type="hidden" name="__read_all" value="1">
        <button class="btn" type="submit">Marcar todas como leídas</button>
      </form>

      <form method="post" style="display:inline-block">
        <input type="hidden" name="__clear_read" value="1">
        <button class="btn" type="submit" onclick="return confirm('¿Eliminar todas las leídas?')">Eliminar leídas</button>
      </form>

      <form method="post" style="display:inline-block">
        <input type="hidden" name="__demo" value="1">
        <button class="btn" type="submit">Agregar demo</button>
      </form>
    </div>
  </section>

  <section class="card" style="max-width:980px;text-align:center;margin-top:12px">
    <?php if (!$rows): ?>
      <p style="margin:8px 0;color:#667">No tienes notificaciones.</p>
    <?php else: ?>
      <ul class="centered-list comments">
        <?php foreach ($rows as $n):
          $id    = (int)($n['id'] ?? 0);
          $type  = (string)($n['type'] ?? 'info');
          $title = (string)($n['title'] ?? 'Notificación');
          $body  = (string)($n['body'] ?? '');
          $url   = (string)($n['url'] ?? '');
          $isr   = (int)($n['is_read'] ?? 0) === 1;
          $created = (string)($n['created_at'] ?? '');
          $due     = (string)($n['due_at'] ?? '');

          $icon = '🔔';
          if ($type==='booking')   $icon = '📅';
          if ($type==='change')    $icon = '✏️';
          if ($type==='cancel')    $icon = '❌';
          if ($type==='reminder')  $icon = '⏰';

          $dueTxt = '';
          if ($due) {
              $dt = DateTime::createFromFormat('Y-m-d H:i:s', $due) ?: DateTime::createFromFormat('Y-m-d H:i', $due);
              if ($dt) $dueTxt = $dt->format('d/m/Y').' · '.fmt_ampm_es($dt);
          }
        ?>
          <li class="comment" style="max-width:760px">
            <div class="comment-head" style="justify-content:center">
              <span style="font-size:18px"><?= h($icon) ?></span>
              <strong><?= h($title) ?></strong>
              <span class="dot"></span>
              <span style="color:#777"><?= h($created) ?></span>
              <?php if ($dueTxt): ?>
                <span class="dot"></span>
                <span class="badge">Para: <?= h($dueTxt) ?></span>
              <?php endif; ?>
              <span class="dot"></span>
              <span class="reco-tag <?= $isr?'yes':'no' ?>"><?= $isr?'Leída':'No leída' ?></span>
            </div>
            <?php if ($body): ?><p><?= nl2br(h($body)) ?></p><?php endif; ?>

            <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap">
              <?php if ($url): ?>
                <a class="btn primary" href="<?= h($url) ?>">Abrir</a>
              <?php endif; ?>
              <?php if ($isr): ?>
                <a class="btn outline" href="index.php?r=pro_notifications&<?= $onlyUnread?'filter=unread&':'' ?>unread=<?= $id ?>">Marcar no leída</a>
              <?php else: ?>
                <a class="btn outline" href="index.php?r=pro_notifications&<?= $onlyUnread?'filter=unread&':'' ?>read=<?= $id ?>">Marcar leída</a>
              <?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</div>
<?php render_footer();
