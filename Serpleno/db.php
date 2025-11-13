<?php

function pdo(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $host = 'db.hkvzkvkriiguamjbuqyx.supabase.co';
    $port = '5432';
    $database = 'postgres';
    $user = 'postgres';
    $password = 'Jsrodri2005*'; // <-- no la publiques

    $dsn = "pgsql:host=$host;port=$port;dbname=$database;sslmode=require";

    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function db_ready(): bool {
    static $ready = null;
    if ($ready !== null) return $ready;

    try {
        pdo()->query('SELECT 1');
        $ready = true;
    } catch (Throwable $e) {
        $ready = false;
    }

    return $ready;
}

?>
