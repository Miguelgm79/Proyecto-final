<?php
// ============================================
// config/db.php
// Conexión a la base de datos con PDO
// ============================================

$host    = 'localhost';
$dbname  = 'bdReservas';
$usuario = 'root';
$clave   = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $usuario,
        $clave,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
