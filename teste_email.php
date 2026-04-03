<?php

require_once 'envio_email.php';

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Envio de Email</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #D4AFDB 0%, #E6D9F5 100%); min-height: 100vh; padding: 40px 20px; }
        .container { max-width: 500px; margin: 0 auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
        h1 { color: #B78FD6; margin-bottom: 30px; text-align: center; }
        .form-group { margin-bottom: 20px; }
        .btn { background: linear-gradient(135deg, #B78FD6 0%, #E6D9F5 100%); border: none; color: white; font-weight: 600; padding: 12px 25px; }
        .btn:hover { opacity: 0.9; color: white; }
        .alert { margin-top: 20px; }
        .resultado { margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px; display: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Teste de Envio de Email</h1>
        
        <form id="formTeste">
            <div class="form-group">
                <label class="form-label">📧 Email para teste</label>
                <input type="email" class="form-control" id="email" placeholder="seu@email.com" required>
            </div>

            <div class="form-group">
                <label class="form-label">👤 Nome</label>
                <input type="text" class="form-control" id="nome" placeholder="Seu nome" required value="Usuário Teste">
            </div>

            <button type="submit" class="btn w-100">Enviar Email de Teste</button>
        </form>

        <div id="resultado" class="resultado"></div>
    </div>

    <script>
        document.getElementById('formTeste').addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('email').value;
            const nome = document.getElementById('nome').value;
            const div = document.getElementById('resultado');

            div.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Enviando...</span></div> Enviando...';
            div.style.display = 'block';

            try {
                const response = await fetch('teste_email_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, nome })
                });

                const data = await response.json();

                if (data.sucesso) {
                    div.innerHTML = `
                        <div class="alert alert-success">
                            <h5>✅ Email enviado com sucesso!</h5>
                            <p>Verifique a caixa de entrada e spam do email: <strong>${email}</strong></p>
                        </div>
                    `;
                } else {
                    div.innerHTML = `
                        <div class="alert alert-danger">
                            <h5>❌ Erro ao enviar email</h5>
                            <p><strong>Erro:</strong> ${data.erro}</p>
                            <p><strong>Detalhes:</strong> ${data.detalhes || 'N/A'}</p>
                        </div>
                    `;
                }
            } catch (e) {
                div.innerHTML = `
                    <div class="alert alert-danger">
                        <h5>❌ Erro na requisição</h5>
                        <p>${e.message}</p>
                    </div>
                `;
            }
        });
    </script>
</body>
</html>
