<?php
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

require_login();
$me = current_user();
if (($me['role'] ?? '') !== 'admin') {
  header('Location: index.php?r=' . ($me ? 'home' : 'login'));
  exit;
}

render_header('Admin · Gestión de Contenidos');

/** ===========================
 *  Auto-crear tabla (si hay BD)
 *  =========================== */
if (db_ready()) {
  try {
    pdo()->exec("
      CREATE TABLE IF NOT EXISTS contents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category VARCHAR(40) NOT NULL,
        title VARCHAR(200) NOT NULL,
        day VARCHAR(16) DEFAULT NULL,
        url TEXT NOT NULL,
        is_free TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
  } catch (Throwable $e) { /* ignore */ }
}

/** ===========================
 *  Helpers
 *  =========================== */
$CATS = [
  'entrenamientos' => 'Entrenamientos',
  'psicologia'     => 'Psicología',
  'nutricion'      => 'Nutrición',
  'coach'          => 'Coach de vida',
];
$DAYS = ['', 'Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];

function contents_fetch_all(?string $cat=null): array {
  try {
    if (db_ready()) {
      if ($cat) {
        $st = pdo()->prepare("SELECT * FROM contents WHERE category=? ORDER BY id DESC");
        $st->execute([$cat]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
      }
      return pdo()->query("SELECT * FROM contents ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
  } catch (Throwable $e) {}
  $list = $_SESSION['contents_demo'] ?? [];
  if ($cat) $list = array_values(array_filter($list, fn($r)=>$r['category']===$cat));
  return $list;
}

function content_fetch(int $id): ?array {
  try {
    if (db_ready()) {
      $st = pdo()->prepare("SELECT * FROM contents WHERE id=?");
      $st->execute([$id]);
      $r = $st->fetch(PDO::FETCH_ASSOC);
      return $r ?: null;
    }
  } catch (Throwable $e) {}
  foreach ($_SESSION['contents_demo'] ?? [] as $r) if ($r['id']===$id) return $r;
  return null;
}

function content_create(array $data, ?string &$err=null): bool {
  $category = $data['category'] ?? '';
  $title    = trim($data['title'] ?? '');
  $day      = $data['day'] ?? '';
  $url      = trim($data['url'] ?? '');
  $is_free  = isset($data['is_free']) ? 1 : 0;

  if (!$category || !$title || !$url) { $err='Completa categoría, título y URL'; return false; }

  try {
    if (db_ready()) {
      $st = pdo()->prepare("INSERT INTO contents (category,title,day,url,is_free) VALUES (?,?,?,?,?)");
      $st->execute([$category,$title,$day,$url,$is_free]);
      return true;
    }
  } catch (Throwable $e) {
    $err = 'Error BD: '.$e->getMessage();
    return false;
  }

  // Fallback sesión
  $list = $_SESSION['contents_demo'] ?? [];
  $list[] = [
    'id' => count($list)+1,
    'category'=>$category, 'title'=>$title, 'day'=>$day, 'url'=>$url, 'is_free'=>$is_free,
    'created_at'=>date('Y-m-d H:i:s'),
  ];
  $_SESSION['contents_demo'] = $list;
  return true;
}

function content_update(int $id, array $data, ?string &$err=null): bool {
  $category = $data['category'] ?? '';
  $title    = trim($data['title'] ?? '');
  $day      = $data['day'] ?? '';
  $url      = trim($data['url'] ?? '');
  $is_free  = isset($data['is_free']) ? 1 : 0;

  if (!$category || !$title || !$url) { $err='Completa categoría, título y URL'; return false; }

  try {
    if (db_ready()) {
      $st = pdo()->prepare("UPDATE contents SET category=?, title=?, day=?, url=?, is_free=? WHERE id=?");
      $st->execute([$category,$title,$day,$url,$is_free,$id]);
      return true;
    }
  } catch (Throwable $e) {
    $err = 'Error BD: '.$e->getMessage();
    return false;
  }

  // Fallback sesión
  $list = $_SESSION['contents_demo'] ?? [];
  $ok=false;
  foreach ($list as &$r) {
    if ($r['id']===$id) {
      $r['category']=$category; $r['title']=$title; $r['day']=$day; $r['url']=$url; $r['is_free']=$is_free;
      $ok=true; break;
    }
  }
  $_SESSION['contents_demo'] = $list;
  return $ok;
}

function content_delete(int $id, ?string &$err=null): bool {
  try {
    if (db_ready()) {
      $st = pdo()->prepare("DELETE FROM contents WHERE id=?");
      $st->execute([$id]);
      return $st->rowCount()>0;
    }
  } catch (Throwable $e) {
    $err = 'Error BD: '.$e->getMessage();
    return false;
  }

  $list = $_SESSION['contents_demo'] ?? [];
  $before = count($list);
  $list = array_values(array_filter($list, fn($r)=>$r['id']!==$id));
  $_SESSION['contents_demo'] = $list;
  return count($list) < $before;
}

/** ===========================
 *  Acciones
 *  =========================== */
$notice=''; $error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $act = $_POST['__act'] ?? '';
  if ($act==='create') {
    if (content_create($_POST, $err)) $notice='Contenido creado.';
    else $error = $err ?: 'No se pudo crear.';
  }
  if ($act==='update') {
    $id = (int)($_POST['id']??0);
    if ($id>0 && content_update($id, $_POST, $err)) $notice='Contenido actualizado.';
    else $error = $err ?: 'No se pudo actualizar.';
  }
  if ($act==='delete') {
    $id = (int)($_POST['id']??0);
    if (content_delete($id, $err)) $notice='Contenido eliminado.';
    else $error = $err ?: 'No se pudo eliminar.';
  }
  if ($act==='export_csv') {
    $catf = $_POST['catf'] ?? '';
    $rows = contents_fetch_all($catf ?: null);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=contenidos_'.date('Ymd_His').'.csv');
    $out = fopen('php://output','w');
    fputcsv($out,['id','category','title','day','url','is_free','created_at']);
    foreach ($rows as $r) {
      fputcsv($out, [$r['id'],$r['category'],$r['title'],$r['day'],$r['url'],$r['is_free'],$r['created_at'] ?? '']);
    }
    fclose($out);
    exit;
  }
}

$editRow = null;
if (isset($_GET['edit'])) {
  $editRow = content_fetch((int)$_GET['edit']);
}

$catFilter = $_GET['cat'] ?? '';
$rows = contents_fetch_all($catFilter ?: null);
?>
<div class="card" style="max-width:980px;margin:0 auto;text-align:center">
  <h2 style="margin:6px 0 12px">Gestión de Contenidos</h2>
  <p style="margin:-6px 0 10px;color:#666">Marca “Libre/Gratis” si quieres que aparezca también en el contenido gratuito visible a todos los planes.</p>

  <?php if ($notice): ?><div class="alert success"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
  <?php if ($error):  ?><div class="alert danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <!-- Crear / Editar -->
  <form method="post" class="auth-form" style="max-width:720px;margin:0 auto">
    <input type="hidden" name="__act" value="<?= $editRow ? 'update':'create' ?>">
    <?php if ($editRow): ?><input type="hidden" name="id" value="<?= (int)$editRow['id'] ?>"><?php endif; ?>

    <div class="form-row">
      <label>Categoría
        <select name="category" required>
          <?php
            $catSel = $editRow['category'] ?? 'entrenamientos';
            foreach ($CATS as $k=>$v) {
              echo '<option value="'.$k.'"'.($catSel===$k?' selected':'').'>'.$v.'</option>';
            }
          ?>
        </select>
      </label>
      <label>Día (opcional)
        <select name="day">
          <?php
            $dSel = $editRow['day'] ?? '';
            foreach ($DAYS as $d) {
              $label = $d ?: '—';
              echo '<option value="'.htmlspecialchars($d).'"'.($dSel===$d?' selected':'').'>'.$label.'</option>';
            }
          ?>
        </select>
      </label>
    </div>

    <div class="form-row">
      <label>Título
        <input type="text" name="title" required value="<?= htmlspecialchars($editRow['title'] ?? '') ?>">
      </label>
      <label>URL (YouTube u otra)
        <input type="url" name="url" required value="<?= htmlspecialchars($editRow['url'] ?? '') ?>">
      </label>
    </div>

   <!-- Libre/Gratis centrado -->
<div style="display:flex;justify-content:center;margin:12px 0 16px;">
  <label for="is_free" style="display:inline-flex;gap:8px;align-items:center;">
    <input id="is_free" type="checkbox" name="is_free" <?= !empty($editRow['is_free']) ? 'checked' : '' ?>>
    Libre / Gratis
  </label>
</div>

<!-- Botones centrados y grandes -->
<div style="display:flex;justify-content:center;gap:10px;flex-wrap:wrap;margin-top:8px;">
  <button class="btn primary" type="submit"
          style="display:block;width:100%;max-width:360px;padding:12px 18px;border-radius:10px;font-weight:700;letter-spacing:.2px;">
    <?= !empty($editRow) ? 'Actualizar contenido' : 'Crear contenido' ?>
  </button>

  <?php if (!empty($editRow)): ?>
    <a class="btn outline"
       href="index.php?r=admin_content"
       style="display:block;width:100%;max-width:360px;padding:12px 18px;border-radius:10px;text-align:center;">
      Cancelar
    </a>
  <?php endif; ?>
</div>


<div class="card" style="max-width:980px;margin:12px auto;text-align:center">
  <form method="get" style="display:flex;gap:8px;justify-content:center;align-items:center;flex-wrap:wrap">
    <input type="hidden" name="r" value="admin_content">
    <label>Filtrar por categoría
      <select name="cat" onchange="this.form.submit()">
        <option value="">Todas</option>
        <?php foreach ($CATS as $k=>$v): ?>
          <option value="<?= $k ?>" <?= $catFilter===$k ? 'selected':'' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </form>

  <div style="display:flex;gap:8px;justify-content:center;align-items:center;margin-top:10px;flex-wrap:wrap">
    <form method="post">
      <input type="hidden" name="__act" value="export_csv">
      <input type="hidden" name="catf" value="<?= htmlspecialchars($catFilter) ?>">
      <button class="btn outline" type="submit">Exportar CSV</button>
    </form>
    <a class="btn" href="index.php?r=admin">Volver al Dashboard</a>
  </div>

  <div class="table-wrapper" style="margin-top:12px">
    <table class="compare">
      <thead>
        <tr>
          <th>ID</th><th>Categoría</th><th>Título</th><th>Día</th><th>URL</th><th>Gratis</th><th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="7">No hay contenidos.</td></tr>
        <?php else: foreach ($rows as $r): ?>
          <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><?= htmlspecialchars($r['category']) ?></td>
            <td style="text-align:left"><?= htmlspecialchars($r['title']) ?></td>
            <td><?= htmlspecialchars($r['day'] ?? '') ?></td>
            <td style="text-align:left;max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
              <a href="<?= htmlspecialchars($r['url']) ?>" target="_blank" rel="noopener">Ver</a>
            </td>
            <td><?= !empty($r['is_free']) ? '✔' : '—' ?></td>
            <td>
              <a class="btn" href="index.php?r=admin_content&edit=<?= (int)$r['id'] ?>">Editar</a>
              <form method="post" style="display:inline" onsubmit="return confirm('¿Eliminar este contenido?');">
                <input type="hidden" name="__act" value="delete">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn" type="submit">Eliminar</button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php render_footer(); ?>
