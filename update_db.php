<?php
// Script para atualizar o banco de dados - adicionar coluna is_admin

require_once 'db.php';

try {
    // Verificar se a coluna já existe
    $stmt = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'is_admin'");
    $exists = $stmt->fetch();

    if (!$exists) {
        // Adicionar coluna is_admin
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN is_admin BOOLEAN DEFAULT FALSE");

        // Definir o primeiro usuário como admin (se existir)
        $stmt = $pdo->query("SELECT id FROM usuarios ORDER BY data_cadastro ASC LIMIT 1");
        $firstUser = $stmt->fetch();

        if ($firstUser) {
            $stmt = $pdo->prepare("UPDATE usuarios SET is_admin = TRUE WHERE id = ?");
            $stmt->execute([$firstUser['id']]);
            echo "Coluna is_admin adicionada com sucesso. Primeiro usuário definido como admin.\n";
        } else {
            echo "Coluna is_admin adicionada com sucesso. Nenhum usuário encontrado para definir como admin.\n";
        }
    } else {
        echo "Coluna is_admin já existe.\n";
    }

} catch (PDOException $e) {
    echo "Erro ao atualizar banco: " . $e->getMessage() . "\n";
}
?>