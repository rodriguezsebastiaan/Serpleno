falta php
// Conexión a la base de datos usando PDO
//function pdo(): PDO {
   // static $pdo = null;
   // if ($pdo === null) {
  //      $host = 'localhost';
    //    $db   = 'serpleno';
    //    $user = 'root';
    //    $pass = '';
    //    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
     //   $pdo = new PDO($dsn, $user, $pass, [
    //        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    //        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
     //   ]);
//    }
//    return $pdo;
//}

/**
 * Devuelve true si la BD está disponible. Cachea el resultado.
 */
//function db_ready(): bool {
//    static $ready = null;
//    if ($ready !== null) return $ready;
 //   try {
 //     pdo(); // si conecta, está lista
   //     $ready = true;
  //  } catch (Throwable $e) {
//        $ready = false;
//    }
//    return $ready;
//}