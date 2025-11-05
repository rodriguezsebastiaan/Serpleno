<?php
// pro_content.php
require_once __DIR__.'/layout.php';
require_once __DIR__.'/guard.php';

require_profesional(); // Solo profesionales
$user = current_user();
$proId = (int)($user['id'] ?? 0);

// ============================
//  Esquema BD (si hay BD)
// ============================
try {
    if (function_exists('db_ready') && db_ready()) {
        pdo()->exec("
            CREATE TABLE IF NOT EXISTS pro_contents (
                id INT AUTO_INCREMENT PRIMARY KEY,
                pro_id INT NOT NULL,
                category VARCHAR(30) NOT NULL,
                type VARCHAR(20) NOT NULL,
                title VARCHAR(200) NOT NULL,
                description TEXT NULL,
                url VARCHAR(400) NOT NULL,
                is_public TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (pro_id, category),
                INDEX (is_public)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
} catch (Throwable $e) {
    // fallback a SESSION
}

$SESSION_KEY = 'pro_contents';
$PRO_KEY = $proId > 0 ? 'id:'.$proId : 'email:'.($user['email'] ?? '');

$CATEGORIES = ['entrenamientos','psicologia','nutricion','coach'];
$ALLOWED_EXT = [
    'mp4','mov','m4v','webm',    // video
    'mp3','wav',                 // audio
    'jpg','jpeg','png','gif',    // imagen
    'pdf','doc','docx','ppt','pptx','xls','xlsx' // docs
];

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function isYoutube($url){ return (bool)preg_match('~(youtube\.com|youtu\.be)~i', (string)$url); }
function inferTypeFromExt($ext){
    $ext = strtolower($ext);
    if (in_array($ext, ['mp4','mov','m4v','webm'])) return 'video';
    if (in_array($ext, ['mp3','wav'])) return 'audio';
    if (in_array($ext, ['jpg','jpeg','png','gif'])) return 'image';
    return 'file';
}

// ============================
//  Acciones (crear/editar/toggle/borrar)
// ============================
$notice = null;
$error  = null;

// Alta por enlace
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['__create_link'])) {
    $title = trim($_POST['title'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $cat   = trim($_POST['category'] ?? '');
    $url   = trim($_POST['url'] ?? '');

    if ($title === '' || $url === '' || !in_array($cat, $CATEGORIES, true)) {
        $error = 'Título, URL y categoría son obligatorios.';
    } else {
        $type = isYoutube($url) ? 'link_video' : 'link';
        try {
            if (function_exists('db_ready') && db_ready()) {
                $ins = pdo()->prepare("INSERT INTO pro_contents (pro_id, category, type, title, description, url) VALUES (?,?,?,?,?,?)");
                $ins->execute([$proId, $cat, $type, $title, $desc ?: null, $url]);
            } else {
                if (!isset($_SESSION[$SESSION_KEY])) $_SESSION[$SESSION_KEY] = [];
                if (!isset($_SESSION[$SESSION_KEY][$PRO_KEY])) $_SESSION[$SESSION_KEY][$PRO_KEY] = [];
                $list = &$_SESSION[$SESSION_KEY][$PRO_KEY];
                $nextId = 1 + (int)max(array_column($list ?: [['id'=>0]], 'id'));
                $list[] = [
                    'id'=>$nextId,'pro_id'=>$proId,'category'=>$cat,'type'=>$type,'title'=>$title,
                    'description'=>$desc ?: null,'url'=>$url,'is_public'=>1,'created_at'=>date('Y-m-d H:i:s')
                ];
            }
            $notice = 'Contenido por enlace agregado.';
        } catch (Throwable $e) { $error = 'No se pudo guardar el contenido.'; }
    }
}

// Subida de archivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['__upload_file'])) {
    $title = trim($_POST['title'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $cat   = trim($_POST['category'] ?? '');

    if ($title === '' || !isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK || !in_array($cat, $CATEGORIES, true)) {
        $error = 'Título, archivo y categoría son obligatorios.';
    } else {
        $fn   = $_FILES['file']['name'];
        $tmp  = $_FILES['file']['tmp_name'];
        $ext  = strtolower(pathinfo($fn, PATHINFO_EXTENSION));

        if (!in_array($ext, $ALLOWED_EXT, true)) {
            $error = 'Tipo de archivo no permitido.';
        } else {
            $upDir = __DIR__.'/uploads';
            if (!is_dir($upDir)) @mkdir($upDir, 0775, true);
            $safeBase = preg_replace('~[^a-zA-Z0-9._-]+~','-', pathinfo($fn, PATHINFO_FILENAME));
            $unique   = $safeBase.'-'.date('YmdHis').'-'.substr(sha1(mt_rand()),0,6).'.'.$ext;
            $destAbs  = $upDir.DIRECTORY_SEPARATOR.$unique;
            $destRel  = 'uploads/'.$unique;

            if (@move_uploaded_file($tmp, $destAbs)) {
                $type = inferTypeFromExt($ext);
                try {
                    if (function_exists('db_ready') && db_ready()) {
                        $ins = pdo()->prepare("INSERT INTO pro_contents (pro_id, category, type, title, description, url) VALUES (?,?,?,?,?,?)");
                        $ins->execute([$proId, $cat, $type, $title, $desc ?: null, $destRel]);
                    } else {
                        if (!isset($_SESSION[$SESSION_KEY])) $_SESSION[$SESSION_KEY] = [];
                        if (!isset($_SESSION[$SESSION_KEY][$PRO_KEY])) $_SESSION[$SESSION_KEY][$PRO_KEY] = [];
                        $list = &$_SESSION[$SESSION_KEY][$PRO_KEY];
                        $nextId = 1 + (int)max(array_column($list ?: [['id'=>0]], 'id'));
                        $list[] = [
                            'id'=>$nextId,'pro_id'=>$proId,'category'=>$cat,'type'=>$type,'title'=>$title,
                            'description'=>$desc ?: null,'url'=>$destRel,'is_public'=>1,'created_at'=>date('Y-m-d H:i:s')
                        ];
                    }
                    $notice = 'Archivo subido correctamente.';
                } catch (Throwable $e) { $error = 'No se pudo registrar el archivo.'; }
            } else {
                $error = 'No se pudo guardar el archivo en el servidor.';
            }
        }
    }
}

// Eliminar
if (isset($_GET['del'])) {
    $id = (int)$_GET['del'];
    try {
        if (function_exists('db_ready') && db_ready()) {
            // Recuperar URL por si hay que borrar el archivo físico
            $q = pdo()->prepare("SELECT url FROM pro_contents WHERE id=? AND pro_id=?");
            $q->execute([$id, $proId]);
            $row = $q->fetch();
            if ($row) {
                $url = (string)$row['url'];
                $d = pdo()->prepare("DELETE FROM pro_contents WHERE id=? AND pro_id=?");
                $d->execute([$id, $proId]);
                if (str_starts_with($url, 'uploads/')) {
                    $abs = __DIR__.DIRECTORY_SEPARATOR.$url;
                    if (is_file($abs)) @unlink($abs);
                }
            }
        } else {
            $list = $_SESSION[$SESSION_KEY][$PRO_KEY] ?? [];
            $new  = [];
            foreach ($list as $it) {
                if ((int)$it['id'] === $id) {
                    if (!empty($it['url']) && str_starts_with($it['url'], 'uploads/')) {
                        $abs = __DIR__.DIRECTORY_SEPARATOR.$it['url'];
                        if (is_file($abs)) @unlink($abs);
                    }
                    continue;
                }
                $new[] = $it;
            }
            $_SESSION[$SESSION_KEY][$PRO_KEY] = $new;
        }
        $notice = 'Contenido eliminado.';
    } catch (Throwable $e) { $error = 'No se pudo eliminar.'; }
}

// Toggle visibilidad
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    try {
        if (function_exists('db_ready') && db_ready()) {
            pdo()->prepare("UPDATE pro_contents SET is_public = 1 - is_public WHERE id=? AND pro_id=?")
                ->execute([$id, $proId]);
        } else {
            $list = $_SESSION[$SESSION_KEY][$PRO_KEY] ?? [];
            foreach ($list as &$it) {
                if ((int)$it['id'] === $id) { $it['is_public'] = empty($it['is_public']) ? 1 : 0; break; }
            }
            $_SESSION[$SESSION_KEY][$PRO_KEY] = $list;
        }
        $notice = 'Visibilidad actualizada.';
    } catch (Throwable $e) { $error = 'No se pudo actualizar.'; }
}

// Editar (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['__edit'])) {
    $id    = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    if ($id <= 0 || $title === '') {
        $error = 'Faltan datos para editar.';
    } else {
        try {
            if (function_exists('db_ready') && db_ready()) {
                pdo()->prepare("UPDATE pro_contents SET title=?, description=? WHERE id=? AND pro_id=?")
                    ->execute([$title, $desc ?: null, $id, $proId]);
            } else {
                $list = $_SESSION[$SESSION_KEY][$PRO_KEY] ?? [];
                foreach ($list as &$it) {
                    if ((int)$it['id'] === $id) { $it['title']=$title; $it['description']=$desc ?: null; break; }
                }
                $_SESSION[$SESSION_KEY][$PRO_KEY] = $list;
            }
            $notice = 'Contenido actualizado.';
        } catch (Throwable $e) { $error = 'No se pudo editar.'; }
    }
}

// ============================
//  Filtro + carga de listado
// ============================
$filterCat = $_GET['cat'] ?? '';
$items = [];
try {
    if (function_exists('db_ready') && db_ready()) {
        if ($filterCat && in_array($filterCat, $CATEGORIES, true)) {
            $q = pdo()->prepare("SELECT * FROM pro_contents WHERE pro_id=? AND category=? ORDER BY created_at DESC");
            $q->execute([$proId, $filterCat]);
        } else {
            $q = pdo()->prepare("SELECT * FROM pro_contents WHERE pro_id=? ORDER BY created_at DESC");
            $q->execute([$proId]);
        }
        $items = $q->fetchAll();
    } else {
        $list = $_SESSION[$SESSION_KEY][$PRO_KEY] ?? [];
        if ($filterCat && in_array($filterCat, $CATEGORIES, true)) {
            $list = array_values(array_filter($list, fn($it)=>($it['category'] ?? '') === $filterCat));
        }
        usort($list, fn($a,$b)=> strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
        $items = $list;
    }
} catch (Throwable $e) {
    $items = [];
}

// ============================
//  Render
// ============================
render_header('Contenido del profesional');
?>
<div class="home-hero">
  <h2>Gestión de contenido</h2>
  <p style="margin:6px 0 14px;color:#556">Sube o enlaza material para tus clientes (todo centrado).</p>

  <?php if ($notice): ?><div class="alert success" style="max-width:820px;margin:0 auto"><?= h($notice) ?></div><?php endif; ?>
  <?php if ($error):  ?><div class="alert danger"  style="max-width:820px;margin:0 auto"><?= h($error)  ?></div><?php endif; ?>

  <p style="margin:10px 0;">
    <a class="btn outline" href="index.php?r=pro_dashboard">Volver al panel</a>
  </p>

  <!-- Filtro -->
  <section class="card pay-wrap" style="text-align:center">
    <h3 style="margin-top:0">Filtrar por categoría</h3>
    <form method="get" style="display:inline-block">
      <input type="hidden" name="r" value="pro_content">
      <select name="cat" style="padding:6px;border-radius:8px;border:1px solid #ddd">
        <option value="">Todas</option>
        <?php foreach ($CATEGORIES as $c): ?>
          <option value="<?= h($c) ?>" <?= $filterCat===$c?'selected':'' ?>><?= ucfirst($c) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn primary" type="submit">Aplicar</button>
    </form>
  </section>

  <!-- Alta por enlace -->
  <section class="card pay-wrap" style="text-align:center; margin-top:12px">
    <h3 style="margin-top:0">Agregar por enlace (YouTube, web, etc.)</h3>
    <form method="post" class="auth-form" style="align-items:center">
      <input type="hidden" name="__create_link" value="1">
      <div class="form-row">
        <label>Título
          <input type="text" name="title" required placeholder="Ej: Rutina HIIT para principiantes">
        </label>
        <label>Categoría
          <select name="category" required>
            <option value="" disabled selected>Selecciona…</option>
            <?php foreach ($CATEGORIES as $c): ?>
              <option value="<?= h($c) ?>"><?= ucfirst($c) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>URL
          <input type="url" name="url" required placeholder="https://youtu.be/... o https://tu-sitio/...">
        </label>
      </div>
      <label style="display:block;max-width:760px;text-align:left;margin-top:8px">Descripción (opcional)
        <textarea name="description" rows="2" style="width:100%;padding:9px;border:1px solid #ddd;border-radius:8px" placeholder="Detalles, objetivos, materiales, etc."></textarea>
      </label>
      <button class="btn primary" type="submit" style="margin-top:6px">Agregar enlace</button>
    </form>
  </section>

  <!-- Subida de archivo -->
  <section class="card pay-wrap" style="text-align:center; margin-top:12px">
    <h3 style="margin-top:0">Subir archivo (video/imagen/documento)</h3>
    <form method="post" enctype="multipart/form-data" class="auth-form" style="align-items:center">
      <input type="hidden" name="__upload_file" value="1">
      <div class="form-row">
        <label>Título
          <input type="text" name="title" required placeholder="Ej: Plan nutricional de 7 días">
        </label>
        <label>Categoría
          <select name="category" required>
            <option value="" disabled selected>Selecciona…</option>
            <?php foreach ($CATEGORIES as $c): ?>
              <option value="<?= h($c) ?>"><?= ucfirst($c) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Archivo
          <input type="file" name="file" required>
        </label>
      </div>
      <label style="display:block;max-width:760px;text-align:left;margin-top:8px">Descripción (opcional)
        <textarea name="description" rows="2" style="width:100%;padding:9px;border:1px solid #ddd;border-radius:8px" placeholder="Resumen del contenido…"></textarea>
      </label>
      <p style="color:#777;font-size:13px;margin:6px 0">Tipos permitidos: <?= implode(', ', $ALLOWED_EXT) ?>. Tamaño máximo depende de tu PHP/servidor.</p>
      <button class="btn primary" type="submit" style="margin-top:6px">Subir archivo</button>
    </form>
  </section>

  <!-- Listado -->
  <section class="card" style="text-align:center; margin-top:12px; max-width:1024px">
    <h3 style="margin-top:0">Mis contenidos</h3>

    <?php if (!$items): ?>
      <p style="color:#777;margin:10px 0">Aún no tienes contenidos registrados.</p>
    <?php else: ?>
      <div class="pro-grid">
        <?php foreach ($items as $it): 
          $id   = (int)($it['id'] ?? 0);
          $type = (string)($it['type'] ?? '');
          $cat  = (string)($it['category'] ?? '');
          $pub  = !empty($it['is_public']);
          $url  = (string)($it['url'] ?? '');
          $title= (string)($it['title'] ?? '');
          $desc = (string)($it['description'] ?? '');
          $dt   = (string)($it['created_at'] ?? '');
          $ts   = $dt ? strtotime($dt) : false;
          $nice = $ts ? date('d/m/Y h:i a', $ts) : '';
        ?>
          <div class="pro-card" style="text-align:center">
            <span class="badge" title="Categoría"><?= ucfirst($cat) ?></span>
            <span class="badge" style="margin-left:6px" title="Tipo"><?= strtoupper($type) ?></span>
            <?php if ($pub): ?>
              <span class="badge" style="margin-left:6px;background:#e6f7ea;color:#1b6e33" title="Visibilidad">Público</span>
            <?php else: ?>
              <span class="badge" style="margin-left:6px;background:#fdecea;color:#a33" title="Visibilidad">Oculto</span>
            <?php endif; ?>

            <h4 style="margin:8px 0 4px 0"><?= h($title) ?></h4>
            <?php if ($desc): ?><p style="margin:6px 0;color:#555;white-space:pre-line"><?= h($desc) ?></p><?php endif; ?>

            <p style="margin:6px 0">
              <a class="btn outline" href="<?= h($url) ?>" target="_blank" rel="noopener">Abrir</a>
              <a class="btn outline" href="index.php?r=pro_content&toggle=<?= $id ?><?= $filterCat ? '&cat='.h($filterCat) : '' ?>">
                <?= $pub ? 'Ocultar' : 'Hacer público' ?>
              </a>
              <a class="btn outline" style="color:#a33;border-color:#a33" 
                 href="index.php?r=pro_content&del=<?= $id ?><?= $filterCat ? '&cat='.h($filterCat) : '' ?>"
                 onclick="return confirm('¿Eliminar este contenido?')">Eliminar</a>
            </p>

            <!-- Formulario rápido de edición (título/descripcion) -->
            <form method="post" class="auth-form" style="align-items:center;margin-top:6px">
              <input type="hidden" name="__edit" value="1">
              <input type="hidden" name="id" value="<?= $id ?>">
              <div class="form-row">
                <label>Título
                  <input type="text" name="title" required value="<?= h($title) ?>">
                </label>
                <label>Descripción
                  <input type="text" name="description" value="<?= h($desc) ?>" placeholder="(opcional)">
                </label>
              </div>
              <button class="btn primary" type="submit">Guardar cambios</button>
            </form>

            <p style="margin:6px 0;color:#777;font-size:12px"><?= $nice ? 'Creado: '.$nice : '' ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>
<?php render_footer();
