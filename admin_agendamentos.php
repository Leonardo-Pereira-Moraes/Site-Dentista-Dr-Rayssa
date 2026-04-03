<?php
session_start();

// Verificar se está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['erro' => 'Você precisa estar logado para acessar esta página']);
    exit;
}

// Criar diretório de dados se não existir
if (!is_dir('dados')) {
    mkdir('dados', 0755, true);
}

// Obter lista de arquivos de agendamentos
$arquivos = glob('dados/agendamentos_*.json');
sort($arquivos);

$todosAgendamentos = [];

// Ler todos os arquivos
foreach ($arquivos as $arquivo) {
    if (file_exists($arquivo)) {
        $conteudo = file_get_contents($arquivo);
        $dados = json_decode($conteudo, true);
        if (is_array($dados)) {
            $todosAgendamentos = array_merge($todosAgendamentos, $dados);
        }
    }
}

// Ordenar por data e hora (mais recentes primeiro)
usort($todosAgendamentos, function($a, $b) {
    $dataA = strtotime($a['data'] . ' ' . $a['hora']);
    $dataB = strtotime($b['data'] . ' ' . $b['hora']);
    return $dataB - $dataA;
});

// Filtros
$filtroData = $_GET['data'] ?? '';
$filtroServico = $_GET['servico'] ?? '';
$filtroStatus = $_GET['status'] ?? '';

// Aplicar filtros
$agendamentosFiltrados = $todosAgendamentos;

if ($filtroData) {
    $agendamentosFiltrados = array_filter($agendamentosFiltrados, function($a) use ($filtroData) {
        return $a['data'] === $filtroData;
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
