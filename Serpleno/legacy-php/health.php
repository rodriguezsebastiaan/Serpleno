<?php
require_once __DIR__.'/db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>";
echo "Loaded ini: " . php_ini_loaded_file() . "\n";
echo "Drivers PDO: "; print_r(PDO::getAvailableDrivers()); echo "\n";
echo "pdo_pgsql loaded? " . (extension_loaded('pdo_pgsql') ? 'YES' : 'NO') . "\n";
echo "pgsql loaded? " . (extension_loaded('pgsql') ? 'YES' : 'NO') . "\n";

try {
    $pdo = pdo();
    echo "✅ Conectado correctamente a la base de datos.\n";
    $r = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "Usuarios actuales: $r\n";
} catch (Throwable $e) {
    echo "❌ Error conectando: " . $e->getMessage() . "\n";
}
echo "</pre>";
