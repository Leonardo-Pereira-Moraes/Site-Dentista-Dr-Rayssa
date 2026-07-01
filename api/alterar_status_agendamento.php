<?php
// ARQUIVO SEGURO PARA ALTERAR STATUS DE AGENDAMENTOS
// Apenas administradores podem alterar status

session_start();

// Verificar se está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['erro' => 'Você precisa estar logado']);
    exit;
}

// ===== INCLUIR CONEXÃO COM BANCO =====
require_once '../config/db.php';

// Verificar se o usuário é admin
try {
    $stmt = $pdo->prepare("SELECT is_admin FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario || !$usuario['is_admin']) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['erro' => 'Acesso negado. Apenas administradores podem alterar status.']);
        exit;
    }
} catch (PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno do servidor']);
    exit;
}

// ===== VERIFICAR MÉTODO POST =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

// ===== RECEBER E DECODIFICAR DADOS =====
$json = file_get_contents('php://input');
$dados = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['erro' => 'JSON inválido']);
    exit;
}

// ===== VALIDAÇÕES =====
$erros = [];

if (empty($dados['id'])) {
    $erros[] = 'ID do agendamento é obrigatório';
}

$status_validos = ['pendente', 'confirmado', 'cancelado'];
if (empty($dados['status']) || !in_array($dados['status'], $status_validos)) {
    $erros[] = 'Status inválido';
}

if (!empty($erros)) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['erro' => implode(', ', $erros)]);
    exit;
}

// ===== ALTERAR STATUS =====
try {
    $stmt = $pdo->prepare("UPDATE agendamentos SET status = ? WHERE id = ?");
    $stmt->execute([$dados['status'], $dados['id']]);

    if ($stmt->rowCount() > 0) {
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'mensagem' => 'Status alterado com sucesso']);
    } else {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['erro' => 'Agendamento não encontrado']);
    }
} catch (PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno do servidor']);
}
?>