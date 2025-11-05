<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function current_role(): string {
    return $_SESSION['user']['role'] ?? 'cliente';
}

function is_admin(): bool { return current_role() === 'admin'; }
function is_profesional(): bool { return current_role() === 'profesional'; }

/**
 * DEMO: solo se usa si no hay conexión con la base de datos.
 */
function demo_login(string $email, string $pass): ?array {
    if ($pass !== '123456') return null;

    $clientes = [
        'demo@serpleno.test'       => ['name'=>'Demo', 'role'=>'cliente', 'plan'=>'gratuito'],
        'silver@serpleno.test'     => ['name'=>'Silver Demo', 'role'=>'cliente', 'plan'=>'silver'],
        'estudiante@serpleno.test' => ['name'=>'Estudiante Demo', 'role'=>'cliente', 'plan'=>'estudiantil'],
        'premium@serpleno.test'    => ['name'=>'Premium Demo', 'role'=>'cliente', 'plan'=>'premium'],
    ];
    if (isset($clientes[$email])) {
        $c = $clientes[$email];
        return ['id'=>-1,'name'=>$c['name'],'email'=>$email,'role'=>$c['role'],'plan'=>$c['plan']];
    }

    if ($email === 'admin@serpleno.test') {
        return ['id'=>-2,'name'=>'Administrador','email'=>$email,'role'=>'admin','plan'=>'admin'];
    }

    $pros = [
        'pro_luis@serpleno.test'      => ['name'=>'Luis','area'=>'entrenamientos','especialidad'=>'baile/aeróbicos'],
        'pro_cristian@serpleno.test'  => ['name'=>'Cristian','area'=>'entrenamientos','especialidad'=>'fuerza'],
        'pro_jefferson@serpleno.test' => ['name'=>'Jefferson','area'=>'entrenamientos','especialidad'=>'funcional'],
        'pro_carolina@serpleno.test'  => ['name'=>'Carolina','area'=>'psicologia','especialidad'=>'psicóloga'],
        'pro_felipe@serpleno.test'    => ['name'=>'Felipe','area'=>'psicologia','especialidad'=>'psicólogo'],
        'pro_james@serpleno.test'     => ['name'=>'James','area'=>'nutricion','especialidad'=>'nutricionista'],
        'pro_daniel@serpleno.test'    => ['name'=>'Daniel','area'=>'coach','especialidad'=>'coach de vida'],
        'pro_nikol@serpleno.test'     => ['name'=>'Nikol','area'=>'coach','especialidad'=>'coach de vida'],
    ];
    if (isset($pros[$email])) {
        $p = $pros[$email];
        return [
            'id' => -100,
            'name' => $p['name'],
            'email' => $email,
            'role' => 'profesional',
            'plan' => 'pro',
            'area' => $p['area'],
            'especialidad' => $p['especialidad'],
        ];
    }

    return null;
}

/**
 * Inicio de sesión con PostgreSQL
 */
function login_user(string $email, string $pass): bool {
    try {
        $stmt = pdo()->prepare('SELECT id, name, email, role, plan, area, especialidad, password_hash FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if ($u && password_verify($pass, $u['password_hash'])) {
            $_SESSION['user'] = [
                'id' => $u['id'],
                'name' => $u['name'],
                'email' => $u['email'],
                'role' => $u['role'] ?? 'cliente',
                'plan' => $u['plan'] ?? 'gratuito',
                'area' => $u['area'] ?? null,
                'especialidad' => $u['especialidad'] ?? null,
            ];
            return true;
        }
    } catch (Throwable $e) {
        // Si falla conexión, cae en modo demo
    }

    // Fallback DEMO (solo si no hay base de datos)
    if (!db_ready()) {
        if ($demo = demo_login($email, $pass)) {
            $_SESSION['user'] = $demo;
            return true;
        }
    }

    return false;
}

/**
 * Registro de usuario real en la base de datos PostgreSQL
 */
function register_user(string $name, string $email, string $pass, ?string &$error = null): bool {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = 'Correo electrónico inválido'; return false; }
    if (strlen($pass) < 6) { $error = 'La contraseña debe tener al menos 6 caracteres'; return false; }

    try {
        // Validar si el correo ya existe
        $stmt = pdo()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Este correo ya está registrado';
            return false;
        }

        // Insertar usuario nuevo
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = pdo()->prepare("INSERT INTO users (name, email, password_hash, role, plan) VALUES (?, ?, ?, 'cliente', 'gratuito')");
        $stmt->execute([$name, $email, $hash]);
        return true;
    } catch (Throwable $e) {
        $error = 'Error BD: ' . $e->getMessage();
        return false;
    }
}

/**
 * Cambio de contraseña
 */
function reset_password(string $email, string $newPass): bool {
    if (strlen($newPass) < 6) return false;
    try {
        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $stmt = pdo()->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
        $stmt->execute([$hash, $email]);
        return $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Cambiar plan del usuario (en sesión y BD)
 */
function set_user_plan(string $plan): void {
    if (!current_user()) return;
    $_SESSION['user']['plan'] = $plan;
    try {
        if (!empty($_SESSION['user']['id']) && $_SESSION['user']['id'] > 0) {
            $stmt = pdo()->prepare('UPDATE users SET plan = ? WHERE id = ?');
            $stmt->execute([$plan, $_SESSION['user']['id']]);
        }
    } catch (Throwable $e) {}
}
?>





