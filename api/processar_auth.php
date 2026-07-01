<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__, 2) . '/logs/php_errors.log');

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

require_once '../config/db.php';

function logEvento($tipo, $mensagem) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'DESCONHECIDO';
    $log = "[" . date('Y-m-d H:i:s') . "] [{$tipo}] IP: {$ip} | {$mensagem}\n";
    error_log($log, 3, dirname(__FILE__, 2) . '/logs/auth.log');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    logEvento('REQUEST', 'Método inválido');
    exit;
}

$dados = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['erro' => 'JSON inválido']);
    logEvento('ERRO', 'JSON inválido');
    exit;
}

match ($dados['acao'] ?? '') {
    'login' => processarLogin($dados),
    'cadastro' => processarCadastro($dados),
    'logout' => processarLogout(),
    default => enviareErro('Ação inválida', 400)
};

function enviareErro($mensagem, $codigo) {
    http_response_code($codigo);
    echo json_encode(['erro' => $mensagem]);
    exit;
}

function processarLogin($dados) {
    global $pdo;
    $email = strtolower(htmlspecialchars($dados['email'] ?? '', ENT_QUOTES));
    $senha = $dados['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        enviareErro('Email e senha são obrigatórios', 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        enviareErro('Email inválido', 400);
    }

    try {
        $stmt = $pdo->prepare("SELECT id, nome, email, senha_hash FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();
    } catch (PDOException $e) {
        logEvento('ERRO', 'Erro ao buscar usuário');
        enviareErro('Erro interno do servidor', 500);
    }

    if (!$usuario || !password_verify($senha, $usuario['senha_hash'])) {
        usleep(rand(500000, 1500000));
        logEvento('LOGIN', "Falha: {$email}");
        enviareErro('Email ou senha incorretos', 401);
    }

    session_start();
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['usuario_email'] = $usuario['email'];
    $_SESSION['login_time'] = time();

    http_response_code(200);
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Login realizado com sucesso!',
        'usuario' => [
            'id' => $usuario['id'],
            'nome' => $usuario['nome'],
            'email' => $usuario['email']
        ]
    ]);
    logEvento('LOGIN', "Login bem-sucedido: {$email}");
}

function processarCadastro($dados) {
    global $pdo;
    $nome = htmlspecialchars($dados['nome'] ?? '', ENT_QUOTES);
    $email = strtolower(htmlspecialchars($dados['email'] ?? '', ENT_QUOTES));
    $telefone = htmlspecialchars($dados['telefone'] ?? '', ENT_QUOTES);
    $senha = $dados['senha'] ?? '';

    $validacoes = [];
    if (strlen($nome) < 3 || strlen($nome) > 100) $validacoes[] = 'Nome: 3-100 caracteres';
    if (!preg_match('/^[a-záàâãéèêíïóôõöúçñ\s\'-]+$/i', $nome)) $validacoes[] = 'Nome inválido';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $validacoes[] = 'Email inválido';
    if (!preg_match('/^[\d\s\(\)\-\+]+$/', $telefone) || strlen($telefone) < 10) $validacoes[] = 'Telefone inválido';
    if (strlen($senha) < 6) $validacoes[] = 'Senha: mínimo 6 caracteres';

    if (!empty($validacoes)) {
        http_response_code(400);
        echo json_encode(['erros' => $validacoes]);
        logEvento('CADASTRO', 'Validação falhou');
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            http_response_code(409);
            echo json_encode(['erro' => 'Email já cadastrado']);
            logEvento('CADASTRO', "Email duplicado: {$email}");
            exit;
        }

        $id = uniqid('user_', true);
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (id, nome, email, telefone, senha_hash, data_cadastro, verificado)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$id, $nome, $email, $telefone, password_hash($senha, PASSWORD_BCRYPT), date('Y-m-d H:i:s'), false]);

        http_response_code(201);
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Cadastro realizado! Faça login para continuar.',
            'usuario' => ['id' => $id, 'nome' => $nome, 'email' => $email]
        ]);
        logEvento('CADASTRO', "Novo usuário: {$email}");
    } catch (PDOException $e) {
        logEvento('ERRO', 'Erro ao cadastrar usuário');
        enviareErro('Erro interno do servidor', 500);
    }
}

function processarLogout() {
    session_start();
    $email = $_SESSION['usuario_email'] ?? 'DESCONHECIDO';

    if (isset($_COOKIE['rememberme_token'])) {
        try {
            global $pdo;
            $hash = hash('sha256', $_COOKIE['rememberme_token']);
            $stmt = $pdo->prepare("UPDATE sessoes_rememberme SET ativo = FALSE WHERE token_hash = ?");
            $stmt->execute([$hash]);
        } catch (Exception) {}

        setcookie('rememberme_token', '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Strict']);
    }

    $_SESSION = [];
    session_destroy();

    setcookie('usuario_sessao', '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Strict']);

    http_response_code(200);
    echo json_encode(['sucesso' => true, 'mensagem' => 'Desconectado com sucesso!']);
    logEvento('LOGOUT', $email);
}
?>
