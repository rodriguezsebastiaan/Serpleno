<?php
require_once __DIR__.'/layout.php';

$errors = [];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $p1 = $_POST['pass1'] ?? '';
    $p2 = $_POST['pass2'] ?? '';

    if (!$email || !$p1 || !$p2) {
        $errors[] = 'Todos los campos son obligatorios';
    } elseif ($p1 !== $p2) {
        $errors[] = 'Las contraseñas no coinciden';
    } elseif (strlen($p1) < 6) {
        $errors[] = 'La contraseña debe tener al menos 6 caracteres';
    } else {
        if (reset_password($email, $p1)) {
            $message = 'Contraseña actualizada. Ya puedes iniciar sesión.';
        } else {
            $errors[] = 'No se pudo actualizar la contraseña. Verifica el email.';
        }
    }
}

render_header('Restablecer contraseña');
?>
<div class="auth-wrapper">
  <section class="card auth-card">
      <h2 class="auth-title">Restablecer contraseña</h2>

      <?php foreach ($errors as $e): ?><div class="alert danger"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
      <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

      <form method="post" class="auth-form" novalidate>
          <label>Email de la cuenta
              <input type="email" name="email" required>
          </label>
          <label>Nueva contraseña
              <input type="password" name="pass1" required>
          </label>
          <label>Repetir contraseña
              <input type="password" name="pass2" required>
          </label>
          <button class="btn primary" type="submit">Actualizar contraseña</button>
      </form>

      <div class="auth-links stacked">
        <a class="btn block" href="index.php?r=login">Volver a Ingresar</a>
        <a class="btn block" href="index.php?r=register">Crear cuenta</a>
      </div>
  </section>
</div>
<?php render_footer(); ?>



