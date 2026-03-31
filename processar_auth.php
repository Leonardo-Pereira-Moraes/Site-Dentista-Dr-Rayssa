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

// ===== ARQUIVO DE USUÁRIOS =====
$usuarios_dir = dirname(__FILE__) . '/dados_auth';
if (!is_dir($usuarios_dir)) {
    mkdir($usuarios_dir, 0755, true);
}

$arquivo_usuarios = $usuarios_dir . '/usuarios.json';

// ===== FUNÇÃO PARA LER USUÁRIOS =====
function lerUsuarios() {
    global $arquivo_usuarios;
    if (!file_exists($arquivo_usuarios)) {
        return [];
    }
    $conteudo = file_get_contents($arquivo_usuarios);
    return json_decode($conteudo, true) ?? [];
}

// ===== FUNÇÃO PARA SALVAR USUÁRIOS =====
function salvarUsuarios($usuarios) {
    global $arquivo_usuarios;
    $fp = fopen($arquivo_usuarios, 'w');
    if (flock($fp, LOCK_EX)) {
        fwrite($fp, json_encode($usuarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        flock($fp, LOCK_UN);
    }
    fclose($fp);
    chmod($arquivo_usuarios, 0600); // Restrior read-write ao owner
}

// ===== CRIAR USUÁRIO DEMO SE NÃO EXISTIR =====
$usuarios = lerUsuarios();
if (empty($usuarios)) {
    $usuarios = [
        [
            'id' => uniqid('user_', true),
            'nome' => 'Usuário Teste',
            'email' => 'demo@test.com',
            'telefone' => '(64) 99999-9999',
            'senha_hash' => password_hash('123456', PASSWORD_BCRYPT),
            'data_cadastro' => date('Y-m-d H:i:s'),
            'verificado' => true
        ]
    ];
    salvarUsuarios($usuarios);
}

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

    // Buscar usuário
    $usuarios = lerUsuarios();
    $usuario_encontrado = null;

    foreach ($usuarios as $usuario) {
        if (strtolower($usuario['email']) === $email) {
            $usuario_encontrado = $usuario;
            break;
        }
    }

    // Usuário não encontrado
    if (!$usuario_encontrado) {
        // Usar delay para evitar brute force
        usleep(rand(500000, 1500000)); // 0.5-1.5 segundos
        http_response_code(401);
        echo json_encode(['erro' => 'Email ou senha incorretos']);
        log_evento('LOGIN', "Falha: usuário não encontrado - {$email}");
        exit;
    }

    // Verificar senha (password_verify é seguro contra timing attacks)
    if (!password_verify($senha, $usuario_encontrado['senha_hash'])) {
        usleep(rand(500000, 1500000)); // 0.5-1.5 segundos
        http_response_code(401);
        echo json_encode(['erro' => 'Email ou senha incorretos']);
        log_evento('LOGIN', "Falha: senha incorreta - {$email}");
        exit;
    }

    // Sucesso!
    session_start();
    $_SESSION['usuario_id'] = $usuario_encontrado['id'];
    $_SESSION['usuario_nome'] = $usuario_encontrado['nome'];
    $_SESSION['usuario_email'] = $usuario_encontrado['email'];
    $_SESSION['login_time'] = time();

    // Usar Cookie seguro também
    setcookie(
        'usuario_sessao',
        hash('sha256', $usuario_encontrado['id'] . $usuario_encontrado['email']),
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
    $usuarios = lerUsuarios();
    foreach ($usuarios as $usuario) {
        if (strtolower($usuario['email']) === $email) {
            http_response_code(409);
            echo json_encode(['erro' => 'Este email já está cadastrado']);
            log_evento('CADASTRO', "Email duplicado: {$email}");
            exit;
        }
    }

    // Criar novo usuário
    $novo_usuario = [
        'id' => uniqid('user_', true),
        'nome' => $nome,
        'email' => $email,
        'telefone' => $telefone,
        'senha_hash' => password_hash($senha, PASSWORD_BCRYPT),
        'data_cadastro' => date('Y-m-d H:i:s'),
        'verificado' => false // Email não verificado
    ];

    // Salvar
    $usuarios[] = $novo_usuario;
    salvarUsuarios($usuarios);

    http_response_code(201);
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Cadastro realizado com sucesso! Faça login para continuar.',
        'usuario' => [
            'id' => $novo_usuario['id'],
            'nome' => $novo_usuario['nome'],
            'email' => $novo_usuario['email']
        ]
    ]);

    log_evento('CADASTRO', "Novo usuário: {$email}");
}
?>
