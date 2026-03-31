# Prototipo PHP - Sistema de Agendamento

Sistema de agendamento de consultas odontopediátricas para a **Dra. Rayssa Silveira**.

##  Funcionalidades

-  Página inicial informativa
-  Formulário de agendamento com validação
-  Sistema de login e cadastro
-  Dashboard do usuário
-  Design responsivo com Bootstrap 5
- Segurança implementada (CSRF, Rate Limiting, Bcrypt)

##  Páginas

| Página | Descrição |
|--------|-----------|
| `index.html` | Home com informações e link de login |
| `agendamentos.html` | Formulário para agendar consultas |
| `auth.html` | Login e Cadastro de usuários |
| `dashboard.html` | Área do usuário logado |

##  Backend

| Arquivo | Função |
|---------|--------|
| `processar_agendamento.php` | Processa agendamentos com validação |
| `processar_auth.php` | Autenticação (login/cadastro) |

##  Quick Start

1. Coloque os arquivos em um servidor PHP
2. Acesse `http://localhost/Prototipo-PHP/index.html`
3. Clique em "🔓 Login"
4. **Demo**: `demo@test.com` / `123456`

##  Segurança

-  Bcrypt para hash de senhas
-  CSRF tokens
-  Rate Limiting (10 req/min)
-  Validação robusta de entrada
-  Sanitização XSS
-  Security Headers
-  Logging de eventos
-  Proteção contra timing attacks

##  Estrutura

```
Prototipo-PHP/
├── index.html
├── agendamentos.html
├── auth.html
├── dashboard.html
├── processar_agendamento.php
├── processar_auth.php
├── css/style.css
├── logs/
├── cache/
├── dados/
└── dados_auth/
```

##  Requisitos

- PHP 7+
- Navegador moderno
- Servidor Apache/Nginx

##  Notas

- Dados armazenados em JSON (pronto para migrar para BD)
- Use HTTPS em produção
- Diretórios `/dados_auth` e `/logs` criados automaticamente
- Rate limiting: 10 requisições por minuto por IP

##  Contato

**Dra. Rayssa Silveira** - Especialista em Odontopediatria

---
*Última atualização: Março 2026*