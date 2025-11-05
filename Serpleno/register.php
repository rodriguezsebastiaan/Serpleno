<?php
require_once __DIR__.'/layout.php';

$errors = [];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if (!register_user($name, $email, $pass, $err)) {
        $errors[] = $err ?? 'No se pudo registrar';
    } else {
        $message = 'Registro exitoso. Ahora puedes iniciar sesión.';
    }
}

render_header('Registrarse');
?>
<div class="auth-wrapper">
  <section class="card auth-card">
      <h2 class="auth-title">Registrarse</h2>

      <?php foreach ($errors as $e): ?>
        <div class="alert danger"><?= htmlspecialchars($e) ?></div>
      <?php endforeach; ?>

      <?php if ($message): ?>
        <div class="alert success"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <form method="post" class="auth-form" novalidate>
          <label>Nombre
              <input type="text" name="name" required>
          </label>
          <label>Email
              <input type="email" name="email" required>
          </label>
          <label>Contraseña (mínimo 6)
              <input type="password" name="password" required>
          </label>
          <button class="btn primary" type="submit">Crear cuenta</button>
      </form>

      <div class="auth-links stacked">
        <a class="btn block" href="index.php?r=login">Ya tengo cuenta</a>
        <a class="btn block" href="index.php?r=reset">Recuperar contraseña</a>
      </div>
  </section>
</div>
<?php render_footer(); ?>




