<?php
// ARQUIVO PARA RETORNAR AGENDAMENTOS DO USUÁRIO LOGADO
// Usuários comuns veem apenas seus próprios agendamentos

session_start();

// Verificar se está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['erro' => 'Você precisa estar logado para acessar esta página']);
    exit;
}

// ===== INCLUIR CONEXÃO COM BANCO =====
require_once '../config/db.php';

// Obter agendamentos do usuário logado
try {
    $stmt = $pdo->prepare("SELECT * FROM agendamentos WHERE usuario_id = ? ORDER BY dataAgendada DESC, horaAgendada DESC");
    $stmt->execute([$_SESSION['usuario_id']]);
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode([
        'sucesso' => true,
        'agendamentos' => $agendamentos
    ]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno do servidor']);
}
?>