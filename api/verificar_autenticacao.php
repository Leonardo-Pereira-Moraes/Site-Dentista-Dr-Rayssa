<?php
// VERIFICAR SE USUÁRIO ESTÁ AUTENTICADO
// Retorna JSON com status de autenticação

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

session_start();

// Verificar se usuário está autenticado
if (!empty($_SESSION['usuario_id'])) {
    // Incluir conexão com banco
    require_once '../config/db.php';

    // Obter dados do usuário incluindo is_admin
    try {
        $stmt = $pdo->prepare("SELECT nome, email, is_admin FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['usuario_id']]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }

            http_response_code(200);
            echo json_encode([
                'autenticado' => true,
                'is_admin' => (bool)$usuario['is_admin'],
                'csrf_token' => $_SESSION['csrf_token'],
                'usuario' => [
                    'id' => $_SESSION['usuario_id'],
                    'nome' => $usuario['nome'],
                    'email' => $usuario['email']
                ]
            ]);
        } else {
            // Usuário não encontrado no banco (sessão inválida)
            session_destroy();
            http_response_code(401);
            echo json_encode([
                'autenticado' => false,
                'mensagem' => 'Sessão inválida'
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'autenticado' => false,
            'mensagem' => 'Erro interno do servidor'
        ]);
    }
} else {
    // Usuário não está logado
    http_response_code(401);
    echo json_encode([
        'autenticado' => false,
        'mensagem' => 'Usuário não autenticado'
    ]);
}
exit;
?>
