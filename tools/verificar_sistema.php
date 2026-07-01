<?php
// Script de verificação do sistema - testa conexão e funcionalidades básicas

echo "Verificando sistema...\n\n";

// ===== TESTAR CONEXÃO COM BANCO =====
echo "1. Testando conexão com banco de dados...\n";
try {
    require_once '../config/db.php';
    echo "Conexão com banco estabelecida\n";

    // Verificar se tabelas existem
    $tables = ['usuarios', 'agendamentos'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "Tabela '$table' existe\n";
        } else {
            echo "Tabela '$table' não encontrada\n";
        }
    }

    // Verificar estrutura da tabela usuarios
    echo "\n2. Verificando estrutura da tabela usuarios...\n";
    $stmt = $pdo->query("DESCRIBE usuarios");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $required_columns = ['id', 'nome', 'email', 'senha_hash', 'is_admin'];
    foreach ($required_columns as $col) {
        $found = false;
        foreach ($columns as $column) {
            if ($column['Field'] === $col) {
                $found = true;
                break;
            }
        }
        if ($found) {
            echo "Coluna '$col' existe\n";
        } else {
            echo "Coluna '$col' não encontrada\n";
        }
    }

    // Verificar se existe usuário admin
    echo "\n3. Verificando usuário admin...\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE is_admin = TRUE");
    $stmt->execute();
    $admin_count = $stmt->fetchColumn();

    if ($admin_count > 0) {
        echo "Encontrado(s) $admin_count usuário(s) admin\n";

        // Mostrar dados do admin
        $stmt = $pdo->prepare("SELECT nome, email FROM usuarios WHERE is_admin = TRUE LIMIT 1");
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "   Admin: {$admin['nome']} ({$admin['email']})\n";
    } else {
        echo "Nenhum usuário admin encontrado\n";
        echo "   Execute: php criar_admin.php\n";
    }

    // Verificar agendamentos
    echo "\n4. Verificando agendamentos...\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM agendamentos");
    $agendamento_count = $stmt->fetchColumn();
    echo "Total de agendamentos: $agendamento_count\n";

    // Verificar status dos agendamentos
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM agendamentos GROUP BY status");
    $status_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($status_counts as $status) {
        echo "   {$status['status']}: {$status['count']}\n";
    }

} catch (PDOException $e) {
    echo "Erro na conexão com banco: " . $e->getMessage() . "\n";
    echo "   Verifique se o MySQL está rodando e as credenciais estão corretas\n";
}

// ===== TESTAR DIRETÓRIOS =====
echo "\n5. Verificando diretórios...\n";
$dirs = ['../logs', '../cache'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        echo "Diretório '$dir' existe\n";
    } else {
        echo "Diretório '$dir' não existe - será criado automaticamente\n";
    }
}

// ===== TESTAR ARQUIVOS PHP =====
echo "\n6. Verificando arquivos PHP...\n";
$files = [
    '../config/db.php',
    '../api/processar_auth.php',
    '../api/processar_agendamento.php',
    '../api/admin_agendamentos.php',
    '../api/meus_agendamentos.php',
    '../api/alterar_status_agendamento.php',
    '../api/verificar_autenticacao.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "Arquivo '$file' existe\n";
    } else {
        echo "Arquivo '$file' não encontrado\n";
    }
}

// ===== INSTRUÇÕES FINAIS =====
echo "\nStatus do Sistema:\n";
echo "Para testar completamente:\n";
echo "1. Acesse http://localhost/index.html\n";
echo "2. Clique em 'Entrar' e depois em 'Criar Conta'\n";
echo "3. Faça login com suas credenciais\n";
echo "4. Vá para agendamentos.html e faça um agendamento\n";
echo "5. Como admin, acesse admin_dashboard.html\n";
echo "\nCredenciais Admin:\n";
echo "- Email: admin@clinicaodontokids.com.br\n";
?>