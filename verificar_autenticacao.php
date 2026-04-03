<?php
// VERIFICAR SE USUÁRIO ESTÁ AUTENTICADO
// Retorna JSON com status de autenticação

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

session_start();

// Verificar se usuário está autenticado
if (!empty($_SESSION['usuario_id'])) {
    // Usuário está logado
    http_response_code(200);
    echo json_encode([
        'autenticado' => true,
        'usuario' => [
            'id' => $_SESSION['usuario_id'],
            'nome' => $_SESSION['usuario_nome'],
            'email' => $_SESSION['usuario_email']
        ]
    ]);
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
