<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__, 2) . '/logs/email_teste.log');

header('Content-Type: application/json; charset=utf-8');

require_once '../config/envio_email.php';

function enviarErro($mensagem, $detalhes = null) {
    echo json_encode([
        'sucesso' => false,
        'erro' => $mensagem,
        'detalhes' => $detalhes
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    enviarErro('Método não permitido');
}

$dados = json_decode(file_get_contents('php://input'), true);

if (!isset($dados['email']) || !isset($dados['nome'])) {
    enviarErro('Email e nome são obrigatórios');
}

$email = filter_var($dados['email'], FILTER_SANITIZE_EMAIL);
$nome = htmlspecialchars($dados['nome'], ENT_QUOTES, 'UTF-8');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    enviarErro('Email inválido');
}

try {
    // Teste 1: Verificar se a função mail() está disponível
    if (!function_exists('mail')) {
        throw new Exception('Função mail() não está disponível no servidor');
    }

    // Teste 2: Gerar código 2FA e enviar
    $envio = new EnvioEmail();
    $codigo = EnvioEmail::gerarCodigo2FA();
    
    error_log("[TESTE EMAIL] Tentando enviar para {$email} com código {$codigo}");

    $resultado = $envio->enviarCodigo2FA($nome, $email, $codigo);

    if (!$resultado) {
        // Coleta informações sobre a falha
        $ini_disable = ini_get('disable_functions');
        $safe_mode = ini_get('safe_mode');
        
        throw new Exception(
            'A função mail() retornou false. ' .
            'Verifique: 1) Configuração SMTP do PHP, 2) Firewall, 3) Antivírus'
        );
    }

    error_log("[TESTE EMAIL] Email enviado com sucesso para {$email}");

    http_response_code(200);
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Email enviado com sucesso',
        'codigo_gerado' => $codigo
    ]);

} catch (Exception $e) {
    error_log("[TESTE EMAIL] Erro: " . $e->getMessage());
    
    http_response_code(500);
    enviarErro(
        'Erro ao enviar email',
        $e->getMessage()
    );
}

?>
