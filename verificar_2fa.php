<?php

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once 'db.php';
require_once 'envio_email.php';

function logEvento($tipo, $mensagem) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'DESCONHECIDO';
    $log = "[" . date('Y-m-d H:i:s') . "] [{$tipo}] IP: {$ip} | {$mensagem}\n";
    error_log($log, 3, dirname(__FILE__) . '/logs/2fa.log');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$dados = json_decode(file_get_contents('php://input'), true);
$codigo = htmlspecialchars($dados['codigo'] ?? '', ENT_QUOTES, 'UTF-8');
$email = strtolower(htmlspecialchars($dados['email'] ?? '', ENT_QUOTES, 'UTF-8'));
$manterLogin = $dados['manter_login'] ?? false;

if (empty($codigo) || empty($email)) {
    http_response_code(400);
    echo json_encode(['erro' => 'Código e email são obrigatórios']);
    logEvento('2FA', 'Tentativa com campos vazios');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, nome, email FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        http_response_code(401);
        echo json_encode(['erro' => 'Email não encontrado']);
        logEvento('2FA', "Email não encontrado: {$email}");
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT id, codigo FROM autenticacao_2fa 
        WHERE usuario_id = ? AND codigo = ? AND expirado_em > NOW() 
        AND verificado = FALSE ORDER BY criado_em DESC LIMIT 1
    ");
    $stmt->execute([$usuario['id'], $codigo]);
    $registro2fa = $stmt->fetch();

    if (!$registro2fa) {
        $stmt = $pdo->prepare("
            UPDATE autenticacao_2fa SET tentativas = tentativas + 1 
            WHERE usuario_id = ? AND codigo = ? AND expirado_em > NOW()
        ");
        $stmt->execute([$usuario['id'], $codigo]);

        http_response_code(401);
        echo json_encode(['erro' => 'Código 2FA inválido ou expirado']);
        logEvento('2FA', "Código inválido para: {$email}");
        exit;
    }

    if ($registro2fa['tentativas'] >= 5) {
        http_response_code(401);
        echo json_encode(['erro' => 'Muitas tentativas. Código expirado.']);
        logEvento('2FA', "Muitas tentativas para: {$email}");
        exit;
    }

    $stmt = $pdo->prepare("UPDATE autenticacao_2fa SET verificado = TRUE WHERE id = ?");
    $stmt->execute([$registro2fa['id']]);

    session_start();
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['usuario_email'] = $usuario['email'];
    $_SESSION['login_time'] = time();
    $_SESSION['2fa_verificado'] = true;

    new EnvioEmail();
    $envio = new EnvioEmail();
    $envio->enviarConfirmacaoLogin($usuario['nome'], $usuario['email']);

    if ($manterLogin) {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        
        $stmt = $pdo->prepare("
            INSERT INTO sessoes_rememberme (usuario_id, token_hash, ip_origem, user_agent, criado_em, expira_em, ativo)
            VALUES (?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), TRUE)
        ");
        $stmt->execute([
            $usuario['id'],
            $tokenHash,
            $_SERVER['REMOTE_ADDR'] ?? 'DESCONHECIDO',
            $_SERVER['HTTP_USER_AGENT'] ?? 'DESCONHECIDO'
        ]);

        setcookie('rememberme_token', $token, [
            'expires' => time() + (30 * 24 * 60 * 60),
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        $resposta = ['sucesso' => true, 'mensagem' => 'Login com sucesso! Mantido conectado.', 'manter_login' => true];
    } else {
        $resposta = ['sucesso' => true, 'mensagem' => 'Login realizado com sucesso!', 'manter_login' => false];
    }

    $resposta['usuario'] = [
        'id' => $usuario['id'],
        'nome' => $usuario['nome'],
        'email' => $usuario['email']
    ];

    http_response_code(200);
    echo json_encode($resposta);
    logEvento('2FA', "Login bem-sucedido: {$email}");

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao verificar código']);
    logEvento('ERRO', 'Erro BD: ' . $e->getMessage());
}
?>

