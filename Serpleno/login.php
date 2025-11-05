<?php
require_once __DIR__.'/layout.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if (!$email || !$pass) {
        $errors[] = 'Email y contraseña son obligatorios';
    } elseif (!login_user($email, $pass)) {
        $errors[] = 'Email o contraseña incorrectos';
    } else {
        // Redirige según el rol
        $u = current_user();
        $role = strtolower($u['role'] ?? '');

        if ($role === 'admin') {
            header('Location: index.php?r=admin_dashboard');
        } elseif ($role === 'pro' || $role === 'profesional' || $role === 'professional') {
            header('Location: index.php?r=pro_dashboard');
        } else {
            header('Location: index.php?r=home');
        }
        exit;
    }
}

render_header('Ingresar');
?>
<div class="auth-wrapper">
  <section class="card auth-card">
      <h2 class="auth-title">Ingreso</h2>
      <p style="margin:-4px 0 12px;color:#666;font-size:14px">
        Ingresa con tu cuenta para continuar.
      </p>

      <?php foreach ($errors as $e): ?>
        <div class="alert danger"><?= htmlspecialchars($e) ?></div>
      <?php endforeach; ?>

      <form method="post" class="auth-form" novalidate>
          <label>Email
              <input type="email" name="email" required>
          </label>
          <label>Contraseña
              <input type="password" name="password" required>
          </label>
          <button class="btn primary" type="submit">Ingresar</button>
      </form>

      <div class="auth-links stacked">
        <a class="btn block" href="index.php?r=register">Regístrate</a>
        <a class="btn block" href="index.php?r=reset">Recuperar contraseña</a>
      </div>
  </section>
</div>
<?php render_footer(); ?>










