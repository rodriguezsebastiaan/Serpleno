<?php
try {
  $pdo = new PDO(
    "pgsql:host=" . getenv('DB_HOST') .
    ";port=" . getenv('DB_PORT') .
    ";dbname=" . getenv('DB_NAME'),
    getenv('DB_USER'),
    getenv('DB_PASS')
  );
  echo "✅ Conectado correctamente a PostgreSQL en Render.";
} catch (Throwable $e) {
  echo "❌ Error: " . $e->getMessage();
}
?>
