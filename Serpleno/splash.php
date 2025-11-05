<?php
require_once __DIR__.'/layout.php';
require_once __DIR__.'/auth.php';

$u = current_user();
if ($u) {
    // Si ya está logueado, llevarlo directo a su panel según el rol
    $role = strtolower($u['role'] ?? '');
    if ($role === 'admin') {
        header('Location: index.php?r=admin_dashboard');
        exit;
    } elseif ($role === 'pro' || $role === 'profesional' || $role === 'professional') {
        header('Location: index.php?r=pro_dashboard');
        exit;
    } else {
        header('Location: index.php?r=home');
        exit;
    }
}

render_header('Bienvenido');
?>
<section class="splash">
  <a class="splash-logo-link" href="index.php?r=login">
    <img class="splash-logo" src="logo empresa.png" alt="Serpleno">
  </a>
  <p style="margin-top:12px;">
    <a class="btn primary" href="index.php?r=login">Ingresar</a>
  </p>
</section>
<?php render_footer(); ?>






