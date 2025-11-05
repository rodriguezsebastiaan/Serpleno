<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__.'/db.php';


function render_header(string $title = 'Serpleno'): void {
    $user = current_user();
    $homeHref = $user ? 'index.php?r=home' : 'index.php?r=login';

    // Detecta si es una página de autenticación para ocultar el menú
    $route = $_GET['r'] ?? '';
    $is_auth_page = in_array($route, ['login','register','reset'], true);
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title><?= htmlspecialchars($title) ?></title>
        <link rel="stylesheet" href="styles.css?v=mini-5">
        <!-- Opcional: acelera videos de YouTube -->
        <link rel="preconnect" href="https://www.youtube.com">
        <link rel="preconnect" href="https://i.ytimg.com">
        <link rel="preconnect" href="https://img.youtube.com">
    </head>
    <body>
    <header>
        <nav class="navbar">
            <a class="brand" href="<?= $homeHref ?>">
                <img class="brand-logo"
                     src="logo%20empresa.png"
                     alt="Serpleno" width="22" height="22"
                     style="width:22px;height:auto;max-height:24px;object-fit:contain;">
                <span class="brand-text">SERPLENO</span>
            </a>
            <ul>
                <?php if (!$is_auth_page): ?>
                    <?php if ($user): ?>
                        <li><a href="index.php?r=home">Inicio</a></li>
                        <li><a href="index.php?r=plans">Planes</a></li>
                        <li><a href="index.php?r=notifications">Notificaciones</a></li>
                        <li><a href="index.php?r=logout">Salir</a></li>
                    <?php else: ?>
                        <li><a href="index.php?r=login">Ingresar</a></li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main class="container centered">
    <?php
}

function render_footer(): void {
    ?>
    </main>
    <footer>
        <p>Todos los derechos reservados. © <?= date('Y') ?> Serpleno S.A.S — NIT 900.123.456-7. </p>
    </footer>
    </body>
    </html>
    <?php
}




