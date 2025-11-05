<?php
require_once __DIR__.'/layout.php';
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';

$user = current_user();
if (!$user) { header('Location: index.php?r=login'); exit; }
if (($user['role'] ?? '') !== 'profesional') { header('Location: index.php?r=home'); exit; }

$err = '';
$msg = '';

// Carpeta de subidas
$upDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($upDir)) { @mkdir($upDir, 0775, true); }

$allowed = ['video/mp4','image/jpeg','image/png','image/webp','application/pdf'];

function sanitize_filename($name) {
  $name = preg_replace('/[^\w\-. ]+/u', '_', $name);
  return trim($name) ?: ('file_'.time());
}

// Tabla (si hay BD)
if (function_exists('db_ready') && db_ready()) {
  try {
    pdo()->exec("
      CREATE TABLE IF NOT EXISTS pro_uploads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pro_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        category VARCHAR(40) NOT NULL,
        filepath VARCHAR(512) NOT NULL,
        mime VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
  } catch (Throwable $e) { /* ignore */ }
}

// Guardar subida
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['__upload'])) {
  $title = trim($_POST['title'] ?? '');
  $category = $_POST['category'] ?? 'entrenamientos';

  if ($title === '') { $err = 'El título es obligatorio.'; }
  elseif (empty($_FILES['files']['name'][0])) { $err = 'Selecciona al menos un archivo.'; }
  else {
    $saved = 0;
    foreach ($_FILES['files']['name'] as $i => $origName) {
      if (!$_FILES['files']['tmp_name'][$i]) continue;

      $tmp  = $_FILES['files']['tmp_name'][$i];
      $type = mime_content_type($tmp) ?: ($_FILES['files']['type'][$i] ?? 'application/octet-stream');
      if (!in_array($type, $allowed, true)) { $err = 'Tipo de archivo no permitido.'; break; }

      $destName = date('Ymd_His') . '_' . sanitize_filename($origName);
      $dest = $upDir . DIRECTORY_SEPARATOR . $destName;

      if (@move_uploaded_file($tmp, $dest)) {
        $relPath = 'uploads/' . $destName;

        if (function_exists('db_ready') && db_ready()) {
          try {
            $stmt = pdo()->prepare("INSERT INTO pro_uploads (pro_id,title,category,filepath,mime) VALUES (?,?,?,?,?)");
            $stmt->execute([$user['id'] ?? 0, $title, $category, $relPath, $type]);
          } catch (Throwable $e) { /* ignore */ }
        } else {
          $_SESSION['pro_uploads'][$user['email']][] = [
            'title'=>$title, 'category'=>$category, 'filepath'=>$relPath, 'mime'=>$type, 'created_at'=>date('Y-m-d H:i:s')
          ];
        }
        $saved++;
      }
    }
    if (!$err) $msg = $saved > 0 ? "Se cargaron {$saved} archivo(s) correctamente." : "No se pudo cargar ningún archivo.";
  }
}

// Listado
$items = [];
if (function_exists('db_ready') && db_ready()) {
  try {
    $q = pdo()->prepare("SELECT title,category,filepath,mime,created_at FROM pro_uploads WHERE pro_id=? ORDER BY id DESC");
    $q->execute([$user['id'] ?? 0]);
    $items = $q->fetchAll() ?: [];
  } catch (Throwable $e) { $items = []; }
} else {
  $items = $_SESSION['pro_uploads'][$user['email']] ?? [];
}

render_header('Subir contenido');
?>
<div class="home-hero" style="max-width:980px;margin:0 auto;text-align:center">
  <h2 style="margin:6px 0 12px;">Gestión de Contenidos (Profesional)</h2>
  <p style="color:#556;margin:-4px 0 14px">
    Sube videos, imágenes o documentos. Formatos permitidos: MP4, JPG, PNG, WEBP, PDF.
  </p>

  <section class="card" style="max-width:820px;margin:0 auto;text-align:center;">
    <?php if ($err): ?><div class="alert danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>
    <?php if ($msg): ?><div class="alert success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="auth-form" style="align-items:center;">
      <input type="hidden" name="__upload" value="1">

      <div class="form-row" style="width:100%;max-width:720px;">
        <label>Categoria
          <select name="category" required>
            <option value="entrenamientos">Entrenamientos</option>
            <option value="psicologia">Psicología</option>
            <option value="nutricion">Nutrición</option>
            <option value="coach">Coach de vida</option>
          </select>
        </label>
        <label>Título
          <input type="text" name="title" required>
        </label>
      </div>

      <label style="max-width:720px;width:100%;">Archivos (puedes seleccionar varios)
        <input type="file" name="files[]" multiple required>
      </label>

      <div style="margin-top:6px;">
        <button class="btn primary" type="submit">Subir contenido</button>
        <a class="btn" href="index.php?r=pro_dashboard" style="margin-left:8px">Volver al panel</a>
      </div>
    </form>
  </section>

  <?php if ($items): ?>
    <h3 class="section-title" style="margin-top:16px;">Tus contenidos</h3>
    <div class="pro-grid">
      <?php foreach ($items as $it): ?>
        <div class="pro-card" style="text-align:center;">
          <h4 style="margin:0 0 6px 0;"><?= htmlspecialchars($it['title']) ?></h4>
          <div style="color:#666;margin-bottom:8px;">
            <?= htmlspecialchars(ucfirst($it['category'])) ?> • <?= htmlspecialchars($it['created_at'] ?? '') ?>
          </div>
          <?php
            $mime = $it['mime'];
            $path = $it['filepath'];
            if (strpos($mime, 'image/') === 0) {
              echo '<img src="'.htmlspecialchars($path).'" alt="" style="max-width:100%;border-radius:8px;">';
            } elseif ($mime === 'application/pdf') {
              echo '<a class="btn outline" target="_blank" href="'.htmlspecialchars($path).'">Abrir PDF</a>';
            } elseif ($mime === 'video/mp4') {
              echo '<video controls style="width:100%;border-radius:8px;"><source src="'.htmlspecialchars($path).'" type="video/mp4"></video>';
            } else {
              echo '<a class="btn outline" target="_blank" href="'.htmlspecialchars($path).'">Descargar</a>';
            }
          ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p style="text-align:center;color:#666;margin-top:12px;">Aún no has subido contenido.</p>
  <?php endif; ?>
</div>
<?php render_footer(); ?>


