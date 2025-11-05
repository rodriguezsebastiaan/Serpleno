<?php
session_start();

require_once __DIR__.'/auth.php';
require_once __DIR__.'/guard.php';
require_once __DIR__.'/layout.php';

// -------------------------------
// Definición de rutas (vistas)
// -------------------------------
$routes = [
    ''                   => 'splash',
    'splash'             => 'splash',
    'login'              => 'login',
    'register'           => 'register',
    'reset'              => 'reset',

    // Cliente
    'home'               => 'home',
    'plans'              => 'plans',
    'plan_detail'        => 'plan_detail',
    'content'            => 'content',
    'portal'             => 'portal',
    'pay'                => 'pay',
    'pay_result'         => 'pay_result',
    'validate_student'   => 'validate_student',
    'notifications'      => 'notifications',
    'schedule'           => 'schedule',
    'meeting'            => 'meeting',

    // Admin
    'admin_dashboard'    => 'admin_dashboard',
    'admin_content'      => 'admin_content',
    'admin_users'        => 'admin_users',
    'admin_stats'        => 'admin_stats',   // <-- lo usa tu admin_dashboard

    // Profesional
    'pro_dashboard'      => 'pro_dashboard',
    'pro_calendar'       => 'pro_calendar',  // <-- lo usa tu pro_dashboard
    'pro_notifications'  => 'pro_notifications',
    'pro_upload'         => 'pro_upload',

    'logout'             => 'logout',
];

// Helper: ¿existe físicamente la vista asociada a una ruta?
function route_exists_key(string $key): bool {
    global $routes;
    if (!isset($routes[$key])) return false;
    $file = __DIR__ . '/' . $routes[$key] . '.php';
    return is_file($file);
}

// -------------------------------
// Ruta solicitada
// -------------------------------
$r = $_GET['r'] ?? 'splash';
if ($r === '') $r = 'splash';

// -------------------------------
/* Info de sesión/rol */
$user  = current_user();
$role  = $user['role'] ?? null;
$plan  = $user['plan'] ?? null;

$is_admin  = in_array($role, ['admin','administrator'], true);
$is_pro    = in_array($role, ['pro','profesional','professional'], true);
$is_client = in_array($role, ['cliente','client'], true) || (!$role && !$user);

// -------------------------------
// Logout
// -------------------------------
if ($r === 'logout') {
    session_destroy();
    header('Location: index.php?r=login');
    exit;
}

// -------------------------------
// Conjuntos de rutas por rol
// -------------------------------
$public_routes = ['splash','login','register','reset'];

$client_only = [
    'home','plans','plan_detail','content','portal',
    'pay','pay_result','validate_student','notifications',
    'schedule','meeting'
];

$admin_only = ['admin_dashboard','admin_content','admin_users','admin_stats'];

$pro_only   = ['pro_dashboard','pro_calendar','pro_notifications','pro_upload'];

// -------------------------------
// Protección básica (requiere sesión)
// -------------------------------
$protected = array_values(array_diff(array_keys($routes), $public_routes));
if (in_array($r, $protected, true)) {
    require_login();
    // Relee datos por si la sesión acaba de iniciar
    $user  = current_user();
    $role  = $user['role'] ?? null;
    $plan  = $user['plan'] ?? null;
    $is_admin  = in_array($role, ['admin','administrator'], true);
    $is_pro    = in_array($role, ['pro','profesional','professional'], true);
    $is_client = in_array($role, ['cliente','client'], true);
}

// -------------------------------
// Aterrizajes por rol (evita bucles si la vista no existe)
// -------------------------------
if ($r === 'splash' && $user) {
    if ($is_admin && route_exists_key('admin_dashboard')) { header('Location: index.php?r=admin_dashboard'); exit; }
    if ($is_pro   && route_exists_key('pro_dashboard'))   { header('Location: index.php?r=pro_dashboard');   exit; }
    header('Location: index.php?r=home'); exit;
}

// Si alguien pide 'home' y es admin/pro, solo reemplazamos si el panel existe
if ($r === 'home' && $user) {
    if ($is_admin && route_exists_key('admin_dashboard')) { $r = 'admin_dashboard'; }
    elseif ($is_pro && route_exists_key('pro_dashboard')) { $r = 'pro_dashboard'; }
}

// -------------------------------
// Autorización por rol (ACL simple)
// -------------------------------
// Admin-only
if (in_array($r, $admin_only, true) && !$is_admin) {
    if ($is_pro && route_exists_key('pro_dashboard')) { header('Location: index.php?r=pro_dashboard'); exit; }
    header('Location: index.php?r=home'); exit;
}

// Pro-only
if (in_array($r, $pro_only, true) && !$is_pro) {
    if ($is_admin && route_exists_key('admin_dashboard')) { header('Location: index.php?r=admin_dashboard'); exit; }
    header('Location: index.php?r=home'); exit;
}

// Cliente-only
if (in_array($r, $client_only, true) && !$is_client) {
    if ($is_admin && route_exists_key('admin_dashboard')) { header('Location: index.php?r=admin_dashboard'); exit; }
    if ($is_pro   && route_exists_key('pro_dashboard'))   { header('Location: index.php?r=pro_dashboard');   exit; }
    // Si no hay panel, dejamos pasar para evitar bucles.
}

// -------------------------------
// Resolver y cargar vista
// -------------------------------
$view = $routes[$r] ?? 'splash';
$file = __DIR__ . '/' . $view . '.php';

if (!is_file($file)) {
    http_response_code(404);
    exit('Archivo no encontrado');
}

require $file;











