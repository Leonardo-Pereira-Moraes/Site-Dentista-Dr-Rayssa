<?php
// Script para configurar o banco de dados MySQL

// Conectar sem especificar DB para criar
try {
    $pdo = new PDO("mysql:host=localhost", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Criar banco se não existir
    $pdo->exec("CREATE DATABASE IF NOT EXISTS prototipo_php CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE prototipo_php");

    // Ler o arquivo schema.sql
    $sql = file_get_contents('schema.sql');

    // Remover a linha CREATE DATABASE
    $sql = preg_replace('/CREATE DATABASE.*;\s*/i', '', $sql);

    // Executar o SQL
    $pdo->exec($sql);

    echo "Banco de dados configurado com sucesso!\n";
    echo "Usuário demo criado automaticamente.\n";

} catch (PDOException $e) {
    echo "Erro ao configurar banco: " . $e->getMessage() . "\n";
}
?>