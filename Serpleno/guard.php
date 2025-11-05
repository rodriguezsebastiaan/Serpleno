<?php
// guard.php
require_once __DIR__.'/auth.php';

/**
 * Redirige al "home" adecuado según el rol.
 */
function redirect_by_role_home(): void {
    $u = current_user();
    if (!$u) {
        header('Location: index.php?r=login');
        exit;
    }
    switch ($u['role'] ?? 'cliente') {
        case 'admin':
            header('Location: index.php?r=admin_dashboard');
            break;
        case 'profesional':
            header('Location: index.php?r=pro_dashboard');
            break;
        default:
            header('Location: index.php?r=home');
            break;
    }
    exit;
}

/**
 * Exige sesión iniciada. Si no hay sesión, envía a login.
 */
function require_login(): void {
    if (!current_user()) {
        header('Location: index.php?r=login');
        exit;
    }
}

/**
 * Exige uno o varios roles. Si no coincide, 403 o redirección al home del rol.
 *
 * @param string|array $roles  Rol o lista de roles válidos (p.ej. 'admin' o ['admin','profesional'])
 * @param bool $redirect       true: redirige al home por rol; false: muestra 403
 */
function require_role($roles, bool $redirect = true): void {
    require_login();

    $u = current_user();
    $userRole = $u['role'] ?? null;

    $allowed = is_array($roles) ? $roles : [$roles];

    if (!in_array($userRole, $allowed, true)) {
        if ($redirect) {
            redirect_by_role_home();
        } else {
            http_response_code(403);
            render_header('Acceso denegado');
            echo '<div class="card" style="max-width:560px"><h2>403</h2><p>No tienes permisos para acceder a esta sección.</p></div>';
            render_footer();
            exit;
        }
    }
}

/**
 * Atajo útil: requiere ser admin.
 */
function require_admin(): void {
    require_role('admin');
}

/**
 * Atajo útil: requiere ser profesional.
 */
function require_profesional(): void {
    require_role('profesional');
}

