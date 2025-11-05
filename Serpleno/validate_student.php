<?php
require_once __DIR__.'/layout.php';
require_once __DIR__.'/auth.php';

$user = current_user();
if (!$user) { header('Location: index.php?r=login'); exit; }

$errors = [];
$ok = false;

function is_institutional_email(string $email): bool {
    // Acepta correos que terminen en .edu o .edu.co
    return (bool)preg_match('/@[\w.-]+\.(edu(\.[a-z]{2})?)$/i', $email);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = trim($_POST['name']   ?? '');
    $school = trim($_POST['school'] ?? '');
    $email  = trim($_POST['email']  ?? '');
    $code   = trim($_POST['code']   ?? '');

    if ($name === '' || $school === '' || $email === '' || $code === '') {
        $errors[] = 'Todos los campos son obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email inválido.';
    } elseif (!is_institutional_email($email)) {
        $errors[] = 'Debes usar tu email institucional (.edu o .edu.co).';
    }

    if (!$errors) {
        // Marcamos validación en sesión y vamos a pagar (plan estudiantil)
        $_SESSION['student_verified'] = 1;
        $_SESSION['student_validation'] = [
            'name' => $name,
            'school' => $school,
            'email' => $email,
            'code' => $code,
            'ts'   => time(),
        ];
        header('Location: index.php?r=pay&plan=estudiantil');
        exit;
    }
}

render_header('Validar Estudiante');
?>
<h2>Validación de estudiante</h2>

<?php foreach ($errors as $e): ?>
  <div class="alert danger"><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>

<section class="card" style="max-width:520px;margin:0 auto;text-align:left">
  <form method="post" class="auth-form" novalidate>
    <label>Nombre completo
      <input type="text" name="name" required>
    </label>

    <label>Universidad (nombre)
      <input type="text" name="school" required>
    </label>

    <label>Email institucional (.edu / .edu.co)
      <input type="email" name="email" placeholder="tucorreo@universidad.edu.co" required>
    </label>

    <label>Código / ID estudiantil
      <input type="text" name="code" required>
    </label>

    <button class="btn primary" type="submit">Validar</button>
  </form>
  <p style="margin-top:10px;text-align:center;color:#666;font-size:13px">
    Una vez validado, te llevaremos al método de pago del plan estudiantil.
  </p>
</section>

<?php render_footer(); ?>



