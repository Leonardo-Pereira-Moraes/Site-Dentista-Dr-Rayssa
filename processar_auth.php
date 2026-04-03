<?php
// ARQUIVO SEGURO PARA AUTENTICAÇÃO (LOGIN/CADASTRO)
// Implementa: Hashing de senha, validações, proteção de sessão, logging

// ===== CONFIGURAÇÕES DE SEGURANÇA =====
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/logs/php_errors.log');

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// ===== INCLUIR CONEXÃO COM BANCO =====
require_once 'db.php';

// ===== FUNÇÃO DE LOGGING =====
function log_evento($tipo, $mensagem) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'DESCONHECIDO';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] [{$tipo}] IP: {$ip} | {$mensagem}\n";
    error_log($log_message, 3, dirname(__FILE__) . '/logs/auth.log');
}

// ===== VERIFICAR MÉTODO POST =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    log_evento('REQUEST', 'Método inválido: ' . $_SERVER['REQUEST_METHOD']);
    exit;
}

// ===== RECEBER DADOS JSON =====
$json = file_get_contents('php://input');
$dados = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['erro' => 'JSON inválido']);
    log_evento('ERRO', 'JSON inválido recebido');
    exit;
}

$acao = $dados['acao'] ?? '';

// ===== REMOVIDO: CRIAR USUÁRIO DEMO =====
// O sistema agora não cria usuários demo automaticamente
// Para criar admin, use o script criar_admin.php

// ===== VALIDAR AÇÃO =====
if ($acao === 'login') {
    processarLogin($dados);
} elseif ($acao === 'cadastro') {
    processarCadastro($dados);
} else {
    http_response_code(400);
    echo json_encode(['erro' => 'Ação inválida']);
    exit;
}

// ===== FUNÇÃO LOGIN =====
function processarLogin($dados) {
    global $pdo;
    $email = $dados['email'] ?? '';
    $senha = $dados['senha'] ?? '';

    // Validações
    if (empty($email) || empty($senha)) {
        http_response_code(400);
        echo json_encode(['erro' => 'Email e senha são obrigatórios']);
        log_evento('LOGIN', 'Tentativa com campos vazios');
        exit;
    }

    // Validar formato email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['erro' => 'Email inválido']);
        log_evento('LOGIN', "Email inválido: {$email}");
        exit;
    }

    $email = strtolower(htmlspecialchars($email, ENT_QUOTES, 'UTF-8'));

    // Buscar usuário no banco
    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro interno do servidor']);
        log_evento('ERRO', 'Erro ao buscar usuário: ' . $e->getMessage());
        exit;
    }

    // Usuário não encontrado
    if (!$usuario) {
        // Usar delay para evitar brute force
        usleep(rand(500000, 1500000)); // 0.5-1.5 segundos
        http_response_code(401);
        echo json_encode(['erro' => 'Email ou senha incorretos']);
        log_evento('LOGIN', "Falha: usuário não encontrado - {$email}");
        exit;
    }

    // Verificar senha (password_verify é seguro contra timing attacks)
    if (!password_verify($senha, $usuario['senha_hash'])) {
        usleep(rand(500000, 1500000)); // 0.5-1.5 segundos
        http_response_code(401);
        echo json_encode(['erro' => 'Email ou senha incorretos']);
        log_evento('LOGIN', "Falha: senha incorreta - {$email}");
        exit;
    }

    // Sucesso!
    session_start();
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['usuario_email'] = $usuario['email'];
    $_SESSION['login_time'] = time();

    // Usar Cookie seguro também
    setcookie(
        'usuario_sessao',
        hash('sha256', $usuario['id'] . $usuario['email']),
        [
            'expires' => time() + (30 * 24 * 60 * 60),
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Strict'
        ]
    );

    http_response_code(200);
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Login realizado com sucesso!',
        'usuario' => [
            'id' => $usuario_encontrado['id'],
            'nome' => $usuario_encontrado['nome'],
            'email' => $usuario_encontrado['email']
        ]
    ]);

    log_evento('LOGIN', "Sucesso: {$email}");
}

// ===== FUNÇÃO CADASTRO =====
function processarCadastro($dados) {
    global $pdo;
    $nome = $dados['nome'] ?? '';
    $email = $dados['email'] ?? '';
    $telefone = $dados['telefone'] ?? '';
    $senha = $dados['senha'] ?? '';

    // Validações
    $erros = [];

    if (empty($nome)) $erros[] = 'Nome é obrigatório';
    elseif (strlen($nome) < 3 || strlen($nome) > 100) $erros[] = 'Nome deve ter 3-100 caracteres';
    elseif (!preg_match('/^[a-záàâãéèêíïóôõöúçñ\s\'-]+$/i', $nome)) $erros[] = 'Nome inválido';

    if (empty($email)) $erros[] = 'Email é obrigatório';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'Email inválido';

    if (empty($telefone)) $erros[] = 'Telefone é obrigatório';
    elseif (!preg_match('/^[\d\s\(\)\-\+]+$/', $telefone) || strlen($telefone) < 10) $erros[] = 'Telefone inválido';

    if (empty($senha)) $erros[] = 'Senha é obrigatória';
    elseif (strlen($senha) < 6) $erros[] = 'Senha deve ter no mínimo 6 caracteres';

    if (!empty($erros)) {
        http_response_code(400);
        echo json_encode(['erros' => $erros]);
        log_evento('CADASTRO', 'Erros de validação: ' . implode(', ', $erros));
        exit;
    }

    // Sanitizar
    $nome = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
    $email = strtolower(htmlspecialchars($email, ENT_QUOTES, 'UTF-8'));
    $telefone = htmlspecialchars($telefone, ENT_QUOTES, 'UTF-8');

    // Verificar se email já existe
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            http_response_code(409);
            echo json_encode(['erro' => 'Este email já está cadastrado']);
            log_evento('CADASTRO', "Email duplicado: {$email}");
            exit;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro interno do servidor']);
        log_evento('ERRO', 'Erro ao verificar email: ' . $e->getMessage());
        exit;
    }

    // Criar novo usuário
    $id = uniqid('user_', true);
    try {
        $stmt = $pdo->prepare("INSERT INTO usuarios (id, nome, email, telefone, senha_hash, data_cadastro, verificado) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id, $nome, $email, $telefone, password_hash($senha, PASSWORD_BCRYPT), date('Y-m-d H:i:s'), false]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro interno do servidor']);
        log_evento('ERRO', 'Erro ao cadastrar usuário: ' . $e->getMessage());
        exit;
    }

    http_response_code(201);
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Cadastro realizado com sucesso! Faça login para continuar.',
        'usuario' => [
            'id' => $id,
            'nome' => $nome,
            'email' => $email
        ]
    ]);

    log_evento('CADASTRO', "Novo usuário: {$email}");
}
?>
