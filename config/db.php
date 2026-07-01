<?php
// Configuração da conexão com MySQL
define('DB_HOST', 'localhost');
define('DB_NAME', 'prototipo_php');
define('DB_USER', 'root'); // Altere se necessário
define('DB_PASS', ''); // Altere se necessário

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>