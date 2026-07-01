<?php
// ARQUIVO SEGURO PARA PROCESSAR AGENDAMENTOS
// Implementa medidas de segurança: CSRF, Rate Limiting, Validação robusta, Logging

// ===== CONFIGURAÇÕES DE SEGURANÇA =====
error_reporting(E_ALL);
ini_set('display_errors', 0); // Não expor erros
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__, 2) . '/logs/php_errors.log');

// Criar diretório de logs se não existir
if (!is_dir(dirname(__FILE__, 2) . '/logs')) {
    mkdir(dirname(__FILE__, 2) . '/logs', 0755, true);
}

// ===== HEADERS DE SEGURANÇA =====
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\'');

// ===== INICIAR SESSÃO PARA CSRF =====
session_start();

// ===== INCLUIR CONEXÃO COM BANCO =====
require_once '../config/db.php';

// ===== VERIFICAR AUTENTICAÇÃO =====
if (empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Você precisa estar logado para agendar uma consulta. Por favor, faça login primeiro.']);
    log_evento('SEGURANÇA', 'Tentativa de agendamento sem autenticação');
    exit;
}

// ===== FUNÇÃO DE LOGGING =====
function log_evento($tipo, $mensagem, $ip = '') {
    if (!$ip) $ip = $_SERVER['REMOTE_ADDR'] ?? 'DESCONHECIDO';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] [{$tipo}] IP: {$ip} | {$mensagem}\n";
    error_log($log_message, 3, dirname(__FILE__, 2) . '/logs/agendamentos.log');
}

// ===== VERIFICAR HTTPS EM PRODUÇÃO =====
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    // Rodando em HTTPS - ok
} elseif (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') === false && strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') === false) {
    // Em produção sem HTTPS
    http_response_code(403);
    echo json_encode(['erro' => 'HTTPS requerido']);
    log_evento('SEGURANÇA', 'Requisição sem HTTPS bloqueada');
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'];

// ===== RATE LIMITING =====
$cache_dir = dirname(__FILE__, 2) . '/cache';
if (!is_dir($cache_dir)) {
    mkdir($cache_dir, 0755, true);
}

$rate_limit_file = $cache_dir . '/rate_limit_' . md5($ip) . '.txt';
$max_requisicoes = 10;
$tempo_limite = 60; // 1 minuto

if (file_exists($rate_limit_file)) {
    $data = json_decode(file_get_contents($rate_limit_file), true);
    $tempo_passado = time() - $data['timestamp'];
    
    if ($tempo_passado < $tempo_limite) {
        if ($data['count'] >= $max_requisicoes) {
            http_response_code(429);
            echo json_encode(['erro' => 'Muitas requisições. Tente novamente em 1 minuto.']);
            log_evento('RATE_LIMIT', "IP bloqueado por excesso de requisições: {$ip}");
            exit;
        }
        $data['count']++;
    } else {
        $data['count'] = 1;
        $data['timestamp'] = time();
    }
} else {
    $data = ['count' => 1, 'timestamp' => time()];
}
file_put_contents($rate_limit_file, json_encode($data));

// ===== VERIFICAR MÉTODO POST =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    log_evento('REQUEST', 'Método inválido: ' . $_SERVER['REQUEST_METHOD']);
    exit;
}

// ===== VALIDAR CSRF TOKEN =====
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';

// Para requisições JSON, tentar pegar do header
if (empty($csrf_token) && !empty(getallheaders()['X-CSRF-Token'])) {
    $csrf_token = getallheaders()['X-CSRF-Token'];
}

// Se for ambiente de desenvolvimento, permitir sem CSRF
$is_development = (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false);

if (!$is_development && !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    http_response_code(403);
    echo json_encode(['erro' => 'Token de segurança inválido']);
    log_evento('SEGURANÇA', 'CSRF token inválido rejeitado');
    exit;
}

// ===== RECEBER E DECODIFICAR DADOS =====
$json = file_get_contents('php://input');
$dados = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['erro' => 'JSON inválido']);
    log_evento('ERRO', 'JSON inválido recebido');
    exit;
}

// ===== VALIDAÇÕES COMPLETAS =====
$erros = [];

// Validar Nome
if (empty($dados['nome'])) {
    $erros[] = 'Nome é obrigatório';
} elseif (strlen($dados['nome']) < 3 || strlen($dados['nome']) > 100) {
    $erros[] = 'Nome deve ter entre 3 e 100 caracteres';
} elseif (!preg_match('/^[a-záàâãéèêíïóôõöúçñ\s\'-]+$/i', $dados['nome'])) {
    $erros[] = 'Nome contém caracteres inválidos';
}

// Validar Email
if (empty($dados['email'])) {
    $erros[] = 'Email é obrigatório';
} elseif (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
    $erros[] = 'Email inválido';
} elseif (strlen($dados['email']) > 254) {
    $erros[] = 'Email muito longo';
}

// Validar Telefone
if (empty($dados['telefone'])) {
    $erros[] = 'Telefone é obrigatório';
} elseif (!preg_match('/^[\d\s\(\)\-\+]+$/', $dados['telefone']) || strlen($dados['telefone']) < 10) {
    $erros[] = 'Telefone inválido';
}

// Validar Nome da Criança
if (empty($dados['nomeCrianca'])) {
    $erros[] = 'Nome da criança é obrigatório';
} elseif (strlen($dados['nomeCrianca']) < 2 || strlen($dados['nomeCrianca']) > 100) {
    $erros[] = 'Nome da criança deve ter entre 2 e 100 caracteres';
} elseif (!preg_match('/^[a-záàâãéèêíïóôõöúçñ\s\'-]+$/i', $dados['nomeCrianca'])) {
    $erros[] = 'Nome da criança contém caracteres inválidos';
}

// Validar Idade
if (empty($dados['idade']) && $dados['idade'] !== '0') {
    $erros[] = 'Idade é obrigatória';
} elseif (!is_numeric($dados['idade']) || $dados['idade'] < 0 || $dados['idade'] > 18) {
    $erros[] = 'Idade deve estar entre 0 e 18 anos';
}

// Validar Serviço
$servicos_validos = ['limpeza', 'fluoretacao', 'carie', 'selante', 'aparelho', 'higiene', 'avaliacao'];
if (empty($dados['servico'])) {
    $erros[] = 'Serviço é obrigatório';
} elseif (!in_array($dados['servico'], $servicos_validos)) {
    $erros[] = 'Serviço inválido';
} elseif (strlen($dados['servico']) > 100) {
    $erros[] = 'Serviço muito longo';
}

// Validar Data
if (empty($dados['data'])) {
    $erros[] = 'Data é obrigatória';
} else {
    $data_obj = DateTime::createFromFormat('Y-m-d', $dados['data']);
    if (!$data_obj || $dados['data'] !== $data_obj->format('Y-m-d')) {
        $erros[] = 'Data inválida (use formato YYYY-MM-DD)';
    } elseif (strtotime($dados['data']) < strtotime('today')) {
        $erros[] = 'Data não pode ser no passado';
    }
}

// Validar Hora
if (empty($dados['hora'])) {
    $erros[] = 'Hora é obrigatória';
} elseif (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $dados['hora'])) {
    $erros[] = 'Hora inválida (use formato HH:MM)';
}

if (!empty($erros)) {
    http_response_code(400);
    echo json_encode(['erros' => $erros]);
    log_evento('VALIDAÇÃO', 'Erros de validação: ' . implode(', ', $erros));
    exit;
}

// ===== SANITIZAR DADOS =====
$nome = htmlspecialchars($dados['nome'], ENT_QUOTES, 'UTF-8');
$email = htmlspecialchars($dados['email'], ENT_QUOTES, 'UTF-8');
$telefone = htmlspecialchars($dados['telefone'], ENT_QUOTES, 'UTF-8');
$nomeCrianca = htmlspecialchars($dados['nomeCrianca'], ENT_QUOTES, 'UTF-8');
$idade = !empty($dados['idade']) ? intval($dados['idade']) : 0;
$servico = htmlspecialchars($dados['servico'], ENT_QUOTES, 'UTF-8');
$data = htmlspecialchars($dados['data'], ENT_QUOTES, 'UTF-8');
$hora = htmlspecialchars($dados['hora'], ENT_QUOTES, 'UTF-8');
$observacoes = !empty($dados['observacoes']) ? htmlspecialchars($dados['observacoes'], ENT_QUOTES, 'UTF-8') : '';

// ===== SALVAR AGENDAMENTO =====
$dados_agendamento = [
    'id' => uniqid('ag_', true),
    'usuario_id' => $_SESSION['usuario_id'],
    'timestamp' => date('Y-m-d H:i:s'),
    'ip_origem' => $ip,
    'nome' => $nome,
    'email' => $email,
    'telefone' => $telefone,
    'nomeCrianca' => $nomeCrianca,
    'idade' => $idade,
    'servico' => $servico,
    'dataAgendada' => $data,
    'horaAgendada' => $hora,
    'observacoes' => $observacoes,
    'status' => 'pendente'
];

try {
    $stmt = $pdo->prepare("INSERT INTO agendamentos (id, usuario_id, timestamp, ip_origem, nome, email, telefone, nomeCrianca, idade, servico, dataAgendada, horaAgendada, observacoes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $dados_agendamento['id'],
        $dados_agendamento['usuario_id'],
        $dados_agendamento['timestamp'],
        $dados_agendamento['ip_origem'],
        $dados_agendamento['nome'],
        $dados_agendamento['email'],
        $dados_agendamento['telefone'],
        $dados_agendamento['nomeCrianca'],
        $dados_agendamento['idade'],
        $dados_agendamento['servico'],
        $dados_agendamento['dataAgendada'],
        $dados_agendamento['horaAgendada'],
        $dados_agendamento['observacoes'],
        $dados_agendamento['status']
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno do servidor']);
    log_evento('ERRO', 'Erro ao salvar agendamento: ' . $e->getMessage());
    exit;
}

// Registrar sucesso no log
log_evento('AGENDAMENTO', 'Novo agendamento recebido: ' . $dados_agendamento['id'] . ' - ' . $nome);

// ===== RESPONDER COM SUCESSO =====
$resposta = [
    'sucesso' => true,
    'mensagem' => 'Agendamento recebido com sucesso!',
    'id_agendamento' => $dados_agendamento['id'],
    'confirmacao' => [
        'crianca' => $nomeCrianca,
        'data' => $data,
        'hora' => $hora,
        'servico' => $servico
    ]
];

// Usar flags JSON seguras contra XSS
echo json_encode($resposta, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);

// ===== OPCIONAL: ENVIAR EMAIL DE CONFIRMAÇÃO =====
/*
$para = $email;
$assunto = 'Confirmação de Agendamento - Clínica OdontoKids';
$headers = "From: noreply@clinicaodontokids.com.br\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

$corpo_email = "
<html>
    <body>
        <h2>Confirmação de Agendamento</h2>
        <p>Olá <strong>" . $nome . "</strong>,</p>
        <p>Seu agendamento foi recebido com sucesso!</p>
        <h3>Detalhes:</h3>
        <ul>
            <li><strong>Criança:</strong> " . $nomeCrianca . "</li>
            <li><strong>Idade:</strong> " . $idade . " anos</li>
            <li><strong>Serviço:</strong> " . $servico . "</li>
            <li><strong>Data:</strong> " . date('d/m/Y', strtotime($data)) . "</li>
            <li><strong>Hora:</strong> " . $hora . "</li>
        </ul>
        <p>Até logo!</p>
        <p><strong>Clínica OdontoKids</strong></p>
    </body>
</html>
";

mail($para, $assunto, $corpo_email, $headers);
*/
?>

