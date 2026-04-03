<?php
// Script para criar ou atualizar o usuário admin
// Email: rayssasilveira764@gmail.com
// Senha: rayssa345

require_once 'db.php';

$email = 'rayssasilveira764@gmail.com';
$senha = 'rayssa345';
$nome = 'Dra. Rayssa Silveira';

try {
    // Verificar se o usuário já existe
    $stmt = $pdo->prepare("SELECT id, is_admin FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuarioExistente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuarioExistente) {
        // Usuário existe, atualizar senha e definir como admin
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET senha_hash = ?, is_admin = TRUE, nome = ? WHERE id = ?");
        $stmt->execute([$senhaHash, $nome, $usuarioExistente['id']]);

        echo "✅ Usuário admin atualizado com sucesso!\n";
        echo "Email: $email\n";
        echo "Nome: $nome\n";
        echo "Admin: Sim\n";
    } else {
        // Criar novo usuário admin
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha_hash, is_admin, data_cadastro, verificado) VALUES (?, ?, ?, TRUE, NOW(), TRUE)");
        $stmt->execute([$nome, $email, $senhaHash]);

        echo "✅ Usuário admin criado com sucesso!\n";
        echo "Email: $email\n";
        echo "Nome: $nome\n";
        echo "Admin: Sim\n";
    }

} catch (PDOException $e) {
    echo "❌ Erro ao configurar usuário admin: " . $e->getMessage() . "\n";
}
?>