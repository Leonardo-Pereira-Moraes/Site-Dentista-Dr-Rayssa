<?php
// EXEMPLO DE ARQUIVO PHP PARA PROCESSAR AGENDAMENTOS
// Salve este arquivo como "processar_agendamento.php" na mesma pasta que index.html

header('Content-Type: application/json');

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

// Receber dados JSON
$json = file_get_contents('php://input');
$dados = json_decode($json, true);

// Validar dados
$erros = [];

if (empty($dados['nome'])) $erros[] = 'Nome é obrigatório';
if (empty($dados['email'])) $erros[] = 'Email é obrigatório';
if (empty($dados['telefone'])) $erros[] = 'Telefone é obrigatório';
if (empty($dados['nomeCrianca'])) $erros[] = 'Nome da criança é obrigatório';
if (empty($dados['servico'])) $erros[] = 'Serviço é obrigatório';
if (empty($dados['data'])) $erros[] = 'Data é obrigatória';
if (empty($dados['hora'])) $erros[] = 'Hora é obrigatória';

if (!empty($erros)) {
    http_response_code(400);
    echo json_encode(['erros' => $erros]);
    exit;
}

// Validar email
if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['erro' => 'Email inválido']);
    exit;
}

// Sanitizar dados
$nome = htmlspecialchars($dados['nome']);
$email = htmlspecialchars($dados['email']);
$telefone = htmlspecialchars($dados['telefone']);
$nomeCrianca = htmlspecialchars($dados['nomeCrianca']);
$idade = htmlspecialchars($dados['idade']);
$servico = htmlspecialchars($dados['servico']);
$data = htmlspecialchars($dados['data']);
$hora = htmlspecialchars($dados['hora']);
$observacoes = htmlspecialchars($dados['observacoes']);

// AQUI VOCÊ PODE:
// 1. Salvar em banco de dados
// 2. Enviar email de confirmação
// 3. Integrar com WhatsApp
// 4. Sincronizar com calendário

// EXEMPLO: Salvar em arquivo de texto (desenvolvimento)
$agendamento = "
=== NOVO AGENDAMENTO ===
Data/Hora: " . date('Y-m-d H:i:s') . "
Responsável: $nome
Email: $email
Telefone: $telefone
Criança: $nomeCrianca
Idade: $idade
Serviço: $servico
Data Agendada: $data
Hora: $hora
Observações: $observacoes
========================\n";

file_put_contents('agendamentos.txt', $agendamento, FILE_APPEND);

// EXEMPLO: Enviar email (descomente se configurado)
/*
$para = $email;
$assunto = 'Agendamento Confirmado - Dra. Rayssa';
$mensagem = "Olá $nome,\n\nSeu agendamento foi confirmado!\n\nData: $data\nHora: $hora\nServiço: $servico\n\nAte logo!";
mail($para, $assunto, $mensagem);
*/

// Responder com sucesso
echo json_encode([
    'sucesso' => true,
    'mensagem' => 'Agendamento recebido com sucesso!',
    'dados' => $dados
]);
?>
