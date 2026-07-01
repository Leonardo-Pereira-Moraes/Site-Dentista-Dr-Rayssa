<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Verificar se está autenticado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Você precisa estar logado para acessar esta página', 'sucesso' => false]);
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
        http_response_code(403);
        echo json_encode(['erro' => 'Acesso negado. Apenas administradores podem acessar esta página.', 'sucesso' => false]);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao verificar permissões', 'sucesso' => false]);
    exit;
}

// Obter lista de agendamentos do banco
try {
    $stmt = $pdo->query("SELECT id, usuario_id, timestamp, nome, email, telefone, nomeCrianca, idade, servico, dataAgendada, horaAgendada, observacoes, status FROM agendamentos ORDER BY dataAgendada DESC, horaAgendada DESC");
    $todosAgendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Se não houver agendamentos, retorna array vazio
    if (!$todosAgendamentos) {
        $todosAgendamentos = [];
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao buscar agendamentos: ' . $e->getMessage(), 'sucesso' => false]);
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

// Resetar chaves do array após filtragem
$agendamentosFiltrados = array_values($agendamentosFiltrados);

// Obter lista de serviços únicos para filtro
$servicos = array_unique(array_column($todosAgendamentos, 'servico'));
sort($servicos);

echo json_encode([
    'sucesso' => true,
    'total' => count($todosAgendamentos),
    'filtrados' => count($agendamentosFiltrados),
    'agendamentos' => $agendamentosFiltrados,
    'servicos' => array_values($servicos),
    'filtros' => [
        'data' => $filtroData,
        'servico' => $filtroServico,
        'status' => $filtroStatus
    ]
]);
?>
