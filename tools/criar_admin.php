<?php
// Script para criar ou atualizar o usuário admin
// Email: admin@clinicaodontokids.com.br

require_once '../config/db.php';

$email = 'admin@clinicaodontokids.com.br';
$senha = 'admin123';
$nome = 'Administrador';

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

        echo "Usuário admin atualizado com sucesso!\n";
        echo "Email: $email\n";
        echo "Nome: $nome\n";
        echo "Admin: Sim\n";
    } else {
        // Criar novo usuário admin
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (id, nome, email, senha_hash, is_admin, data_cadastro, verificado) VALUES (?, ?, ?, ?, TRUE, NOW(), TRUE)");
        $stmt->execute([uniqid('user_', true), $nome, $email, $senhaHash]);

        echo "Usuário admin criado com sucesso!\n";
        echo "Email: $email\n";
        echo "Nome: $nome\n";
        echo "Admin: Sim\n";
    }

} catch (PDOException $e) {
    echo "Erro ao configurar usuário admin: " . $e->getMessage() . "\n";
}

// Criar usuário demo se não existir
$emailDemo = 'demo@test.com';
$stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
$stmt->execute([$emailDemo]);
if ($stmt->fetchColumn() == 0) {
    $stmt = $pdo->prepare("
        INSERT INTO usuarios (id, nome, email, telefone, senha_hash, data_cadastro, verificado, is_admin)
        VALUES (?, 'Usuário Demo', ?, '', ?, NOW(), TRUE, FALSE)
    ");
    $stmt->execute([uniqid('user_', true), $emailDemo, password_hash('123456', PASSWORD_BCRYPT)]);
    echo "Usuario demo criado: demo@test.com / 123456\n";
}
?>