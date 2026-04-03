<?php

class EnvioEmail {
    private $endereco_admin = 'noreply@seu-dominio.com';
    private $nome_clinica = 'Dra. Rayssa Silveira';

    public static function gerarCodigo2FA() {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function enviarCodigo2FA($nome, $email, $codigo) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $assunto = "[{$this->nome_clinica}] Seu código: {$codigo}";
        $corpo = $this->gerarTemplate2FA($nome, $codigo);
        $headers = $this->gerarHeaders();

        return mail($email, $assunto, $corpo, $headers);
    }

    public function enviarConfirmacaoLogin($nome, $email) {
        $assunto = "[{$this->nome_clinica}] Login confirmado";
        $corpo = $this->gerarTemplateConfirmacao($nome);
        $headers = $this->gerarHeaders();

        return mail($email, $assunto, $corpo, $headers);
    }

    private function gerarHeaders() {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$this->nome_clinica} <{$this->endereco_admin}>\r\n";
        $headers .= "Reply-To: {$this->endereco_admin}\r\n";
        return $headers;
    }

    private function gerarTemplate2FA($nome, $codigo) {
        return <<<HTML
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #4A90E2 0%, #7EC8FF 100%); color: white; padding: 40px 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 28px; font-weight: 700; }
        .content { padding: 40px 30px; color: #333; }
        .content p { font-size: 16px; line-height: 1.6; margin: 15px 0; }
        .codigo-container { background: #f9f9f9; border: 2px solid #4A90E2; border-radius: 8px; padding: 25px; text-align: center; margin: 30px 0; }
        .codigo { font-size: 42px; font-weight: 700; color: #4A90E2; letter-spacing: 8px; font-family: 'Courier New', monospace; margin: 0; }
        .aviso { background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; padding: 15px; margin: 20px 0; color: #856404; font-size: 14px; }
        .footer { background: #f5f5f5; padding: 20px; text-align: center; color: #999; font-size: 12px; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Autenticação em Duas Etapas</h1>
        </div>
        <div class="content">
            <p>Olá <strong>{$nome}</strong>,</p>
            <p>Para continuar, use o código abaixo:</p>
            <div class="codigo-container">
                <p class="codigo">{$codigo}</p>
            </div>
            <p><strong>Este código expira em 10 minutos.</strong></p>
            <div class="aviso">⚠️ Nunca compartilhe este código com ninguém.</div>
            <p>Atenciosamente,<br><strong>{$this->nome_clinica}</strong></p>
        </div>
        <div class="footer">
            <p>&copy; {$this->nome_clinica}. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function gerarTemplateConfirmacao($nome) {
        return <<<HTML
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 40px 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 28px; }
        .content { padding: 40px 30px; color: #333; }
        .content p { font-size: 16px; line-height: 1.6; margin: 15px 0; }
        .footer { background: #f5f5f5; padding: 20px; text-align: center; color: #999; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header"><h1>✅ Login Confirmado</h1></div>
        <div class="content">
            <p>Olá <strong>{$nome}</strong>,</p>
            <p>Seu login foi confirmado com sucesso.</p>
            <p>Atenciosamente,<br><strong>{$this->nome_clinica}</strong></p>
        </div>
        <div class="footer"><p>&copy; {$this->nome_clinica}</p></div>
    </div>
</body>
</html>
HTML;
    }
}
?>

