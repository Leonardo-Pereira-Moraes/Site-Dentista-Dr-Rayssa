<?php
session_start();

// Verificar se está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['erro' => 'Você precisa estar logado para acessar esta página']);
    exit;
}

// ===== INCLUIR CONEXÃO COM BANCO =====
require_once 'db.php';

// Verificar se o usuário é admin
try {
    $stmt = $pdo->prepare("SELECT is_admin FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario || !$usuario['is_admin']) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['erro' => 'Acesso negado. Apenas administradores podem acessar esta página.']);
        exit;
    }
} catch (PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno do servidor']);
    exit;
}

// Obter lista de agendamentos do banco
try {
    $stmt = $pdo->query("SELECT * FROM agendamentos ORDER BY dataAgendada DESC, horaAgendada DESC");
    $todosAgendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno do servidor']);
    exit;
}

// Filtros
$filtroData = $_GET['data'] ?? '';
$filtroServico = $_GET['servico'] ?? '';
$filtroStatus = $_GET['status'] ?? '';

// Aplicar filtros
$agendamentosFiltrados = $todosAgendamentos;

if ($filtroData) {
    $agendamentosFiltrados = array_filter($agendamentosFiltrados, function($a) use ($filtroData) {
        return $a['dataAgendada'] === $filtroData;
    });
}

if ($filtroServico) {
    $agendamentosFiltrados = array_filter($agendamentosFiltrados, function($a) use ($filtroServico) {
        return strtolower($a['servico']) === strtolower($filtroServico);
    });
}

if ($filtroStatus) {
    $agendamentosFiltrados = array_filter($agendamentosFiltrados, function($a) use ($filtroStatus) {
        $status = $a['status'] ?? 'pendente';
        return $status === $filtroStatus;
    });
}

// Obter lista de serviços únicos para filtro
$servicos = array_unique(array_column($todosAgendamentos, 'servico'));
sort($servicos);

header('Content-Type: application/json');
echo json_encode([
    'sucesso' => true,
    'total' => count($todosAgendamentos),
    'filtrados' => count($agendamentosFiltrados),
    'agendamentos' => array_values($agendamentosFiltrados),
    'servicos' => $servicos,
    'filtros' => [
        'data' => $filtroData,
        'servico' => $filtroServico,
        'status' => $filtroStatus
    ]
]);
?>
