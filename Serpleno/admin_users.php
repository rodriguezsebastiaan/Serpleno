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

render_header('Admin · Gestión de Usuarios');

/** ===========================
 *  Helpers
 *  =========================== */
function users_fetch_all(): array {
  try {
    if (db_ready()) {
      $rows = pdo()->query("SELECT id, name, email, role, plan FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
      return $rows ?: [];
    }
  } catch (Throwable $e) {}
  return $_SESSION['users_demo'] ?? [];
}

function user_fetch(int $id): ?array {
  try {
    if (db_ready()) {
      $st = pdo()->prepare("SELECT id, name, email, role, plan FROM users WHERE id=?");
      $st->execute([$id]);
      $u = $st->fetch(PDO::FETCH_ASSOC);
      return $u ?: null;
    }
  } catch (Throwable $e) {}
  foreach ($_SESSION['users_demo'] ?? [] as $u) if ($u['id']===$id) return $u;
  return null;
}

function user_create(string $name, string $email, string $pass, string $role, string $plan, ?string &$err=null): bool {
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $err='Email inválido'; return false; }
  if (strlen($pass) < 6) { $err='Contraseña mínima 6 caracteres'; return false; }
  $role = ($role === 'pro') ? 'profesional' : $role;
  if (!in_array($role, ['cliente','profesional','admin'], true)) { $err='Rol inválido'; return false; }
  $plan = strtolower(trim($plan ?? ''));
  if ($plan === 'ninguno' || $plan === '') { $plan = null; }
  if (in_array($role, ['admin','profesional'], true)) {
    $plan_db = null;
  } else {
    $valid_plans = ['gratuito','silver','estudiantil','premium'];
    if ($plan !== null && !in_array($plan, $valid_plans, true)) { $err='Plan inválido'; return false; }
    $plan_db = $plan;
  }

  $hash = password_hash($pass, PASSWORD_DEFAULT);
  try {
    if (db_ready()) {
      // único por email
      $chk = pdo()->prepare("SELECT id FROM users WHERE email=?");
      $chk->execute([$email]);
      if ($chk->fetch()) { $err='Email ya existe'; return false; }

      $st = pdo()->prepare("INSERT INTO users (name,email,password_hash,role,plan) VALUES (?,?,?,?,?)");
      $st->execute([$name,$email,$hash,$role,$plan_db]);
      return true;
    }
  } catch (Throwable $e) {
    $err = 'Error BD: '.$e->getMessage();
    return false;
  }

  // Fallback sesión
  $list = $_SESSION['users_demo'] ?? [];
  foreach ($list as $u) if ($u['email']===$email){ $err='Email ya existe'; return false; }
  $list[] = [
    'id'   => count($list)+1,
    'name' => $name,
    'email'=> $email,
    'role' => $role,
    'plan' => $plan,
    'password_hash' => $hash,
  ];
  $_SESSION['users_demo'] = $list;
  return true;
}

function user_update(int $id, string $name, string $email, ?string $pass, string $role, string $plan, ?string &$err=null): bool {
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $err='Email inválido'; return false; }
  $role = ($role === 'pro') ? 'profesional' : $role;
  if (!in_array($role, ['cliente','profesional','admin'], true)) { $err='Rol inválido'; return false; }
  $plan = strtolower(trim($plan ?? ''));
  if ($plan === 'ninguno' || $plan === '') { $plan = null; }
  if (in_array($role, ['admin','profesional'], true)) {
    $plan_db = null;
  } else {
    $valid_plans = ['gratuito','silver','estudiantil','premium'];
    if ($plan !== null && !in_array($plan, $valid_plans, true)) { $err='Plan inválido'; return false; }
    $plan_db = $plan;
  }

  try {
    if (db_ready()) {
      // Chequeo email duplicado en otro usuario
      $chk = pdo()->prepare("SELECT id FROM users WHERE email=? AND id<>?");
      $chk->execute([$email,$id]);
      if ($chk->fetch()) { $err='Email ya está en uso por otro usuario'; return false; }

      if ($pass !== null && $pass !== '') {
        if (strlen($pass)<6) { $err='Contraseña mínima 6'; return false; }
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $st = pdo()->prepare("UPDATE users SET name=?, email=?, password_hash=?, role=?, plan=? WHERE id=?");
        $st->execute([$name,$email,$hash,$role,$plan_db,$id]);
      } else {
        $st = pdo()->prepare("UPDATE users SET name=?, email=?, role=?, plan=? WHERE id=?");
        $st->execute([$name,$email,$role,$plan_db,$id]);
      }
      return true;
    }
  } catch (Throwable $e) {
    $err = 'Error BD: '.$e->getMessage();
    return false;
  }

  // Fallback sesión
  $list = $_SESSION['users_demo'] ?? [];
  $found = false;
  foreach ($list as &$u) {
    if ($u['id']===$id) {
      // email duplicado?
      foreach ($list as $v) if ($v['email']===$email && $v['id']!==$id){ $err='Email en uso'; return false; }
      $u['name']=$name; $u['email']=$email; $u['role']=$role; $u['plan']=$plan;
      if ($pass) $u['password_hash']=password_hash($pass,PASSWORD_DEFAULT);
      $found = true; break;
    }
  }
  $_SESSION['users_demo'] = $list;
  return $found;
}

function user_delete(int $id, ?string &$err=null): bool {
  try {
    if (db_ready()) {
      $st = pdo()->prepare("DELETE FROM users WHERE id=?");
      $st->execute([$id]);
      return $st->rowCount()>0;
    }
  } catch (Throwable $e) {
    $err = 'Error BD: '.$e->getMessage();
    return false;
  }

  // Fallback sesión
  $list = $_SESSION['users_demo'] ?? [];
  $before = count($list);
  $list = array_values(array_filter($list, fn($u)=>$u['id']!==$id));
  $_SESSION['users_demo'] = $list;
  return count($list) < $before;
}

/** ===========================
 *  Acciones POST
 *  =========================== */
$notice = '';
$error  = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $act = $_POST['__act'] ?? '';
  if ($act === 'create') {
    if (user_create(
      trim($_POST['name']??''),
      trim($_POST['email']??''),
      $_POST['password']??'',
      $_POST['role']??'cliente',
      $_POST['plan']??'',
      $err
    )) {
      $notice = 'Usuario creado.';
    } else { $error = $err ?: 'No se pudo crear.'; }
  }
  if ($act === 'update') {
    $id = (int)($_POST['id']??0);
    if ($id>0 && user_update(
      $id,
      trim($_POST['name']??''),
      trim($_POST['email']??''),
      ($_POST['password']??'') === '' ? null : $_POST['password'],
      $_POST['role']??'cliente',
      $_POST['plan']??'',
      $err
    )) {
      $notice = 'Usuario actualizado.';
    } else { $error = $err ?: 'No se pudo actualizar.'; }
  }
  if ($act === 'delete') {
    $id = (int)($_POST['id']??0);
    if ($id === (int)($me['id']??0)) {
      $error = 'No puedes eliminar tu propia cuenta.';
    } else {
      if (user_delete($id, $err)) $notice = 'Usuario eliminado.';
      else $error = $err ?: 'No se pudo eliminar.';
    }
  }
  if ($act === 'export_csv') {
    $rows = users_fetch_all();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=usuarios_'.date('Ymd_His').'.csv');
    $out = fopen('php://output','w');
    fputcsv($out,['id','name','email','role','plan']);
    foreach ($rows as $r) fputcsv($out, [$r['id'],$r['name'],$r['email'],$r['role'],$r['plan']]);
    fclose($out);
    exit;
  }
}

/** ===========================
 *  Datos para render
 *  =========================== */
$editing = null;
if (isset($_GET['edit'])) {
  $editing = user_fetch((int)$_GET['edit']);
}

$rows = users_fetch_all();
?>
<div class="card" style="max-width:980px;margin:0 auto;text-align:center">
  <h2 style="margin:6px 0 12px">Gestión de perfiles</h2>

  <?php if ($notice): ?><div class="alert success"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
  <?php if ($error):  ?><div class="alert danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <!-- Crear / Editar -->
<?php
  $role_form = $editing['role'] ?? ($_POST['role'] ?? 'cliente');
  if ($role_form === 'pro') $role_form = 'profesional';
  $plan_form = $editing['plan'] ?? ($_POST['plan'] ?? '');
  if (in_array($role_form, ['admin','profesional'], true)) { $plan_form = 'ninguno'; }
?>

  <form method="post" class="auth-form" style="max-width:680px;margin:0 auto">
    <input type="hidden" name="__act" value="<?= $editing ? 'update':'create' ?>">
    <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int)$editing['id'] ?>"><?php endif; ?>

    <div class="form-row">
      <label>Nombre
        <input type="text" name="name" required value="<?= htmlspecialchars($editing['name'] ?? '') ?>">
      </label>
      <label>Email
        <input type="email" name="email" required value="<?= htmlspecialchars($editing['email'] ?? '') ?>">
      </label>
    </div>

    <div class="form-row">
      <label>Contraseña <?= $editing ? '(deja en blanco para no cambiar)': '' ?>
        <input type="password" name="password" <?= $editing ? '' : 'required' ?>>
      </label>
      <label>Rol
        <select name="role" required>
          <?php
            $role = $editing['role'] ?? 'cliente';
            foreach (['cliente'=>'Cliente','pro'=>'Profesional','admin'=>'Administrador'] as $k=>$v) {
              echo '<option value="'.$k.'"'.($role===$k?' selected':'').'>'.$v.'</option>';
            }
          ?>
        </select>
      </label>
      <label>Plan
        <select name="plan">
          <?php
            $plan = $editing['plan'] ?? 'gratuito';
            foreach (['gratuito'=>'Gratuito','estudiantil'=>'Estudiantil','premium'=>'Premium'] as $k=>$v) {
              echo '<option value="'.$k.'"'.($plan===$k?' selected':'').'>'.$v.'</option>';
            }
          ?>
        </select>
      </label>
    </div>

    <button class="btn primary" type="submit"><?= $editing ? 'Actualizar usuario' : 'Crear usuario' ?></button>
    <?php if ($editing): ?>
      <a class="btn" href="index.php?r=admin_users" style="margin-left:8px">Cancelar</a>
    <?php endif; ?>
  </form>
</div>

<div class="card" style="max-width:980px;margin:12px auto;text-align:center">
  <div style="display:flex;gap:8px;justify-content:center;align-items:center;flex-wrap:wrap">
    <form method="post">
      <input type="hidden" name="__act" value="export_csv">
      <button class="btn outline" type="submit">Exportar CSV</button>
    </form>
    <a class="btn" href="index.php?r=admin">Volver al Dashboard</a>
  </div>

  <div class="table-wrapper" style="margin-top:12px">
    <table class="compare">
      <thead>
        <tr>
          <th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Plan</th><th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="6">No hay usuarios.</td></tr>
        <?php else: foreach ($rows as $u): ?>
          <tr>
            <td><?= (int)$u['id'] ?></td>
            <td><?= htmlspecialchars($u['name']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= htmlspecialchars($u['role']) ?></td>
            <td><?= htmlspecialchars($u['plan']) ?></td>
            <td>
              <a class="btn" href="index.php?r=admin_users&edit=<?= (int)$u['id'] ?>">Editar</a>
              <form method="post" style="display:inline" onsubmit="return confirm('¿Eliminar este usuario?');">
                <input type="hidden" name="__act" value="delete">
                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
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
