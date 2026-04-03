# Prototipo PHP - Sistema de Agendamento

Sistema de agendamento de consultas odontopediátricas para a **Dra. Rayssa Silveira**.

##  Funcionalidades

-  Página inicial informativa
-  Formulário de agendamento com validação
-  Sistema de login e cadastro
-  Dashboard do usuário (apenas agendamentos próprios)
-  **Dashboard Administrativo** (apenas para admins)
-  Design responsivo com Bootstrap 5
- Segurança implementada (CSRF, Rate Limiting, Bcrypt)

##  Páginas

| Página | Descrição |
|--------|-----------|
| `index.html` | Home com informações e link de login |
| `agendamentos.html` | Formulário para agendar consultas |
| `auth.html` | Login e Cadastro de usuários |
| `dashboard.html` | Área do usuário logado (agendamentos próprios) |
| `admin_dashboard.html` | **Dashboard Administrativo** (todos os agendamentos) |

##  Backend

| Arquivo | Função |
|---------|--------|
| `processar_agendamento.php` | Processa agendamentos com validação |
| `processar_auth.php` | Autenticação (login/cadastro) |
| `admin_agendamentos.php` | **API para admins verem todos os agendamentos** |
| `meus_agendamentos.php` | **API para usuários verem seus agendamentos** |
| `alterar_status_agendamento.php` | **Alterar status de agendamentos (apenas admin)** |

##  Controle de Acesso

- **Usuários comuns**: Veem apenas seus próprios agendamentos
- **Administradores**: Veem todos os agendamentos e podem alterar status

##  Quick Start

1. **Configurar XAMPP:**
   - Inicie o Apache e MySQL no XAMPP
   - Acesse `http://localhost/phpmyadmin`
   - Execute o script `schema.sql` para criar o banco

2. **Configurar o Sistema:**
   ```bash
   # No terminal/prompt de comando, navegue até a pasta do projeto
   cd "caminho/para/Prototipo-PHP"

   # Atualizar estrutura do banco
   php update_db.php

   # Criar usuário admin
   php criar_admin.php

   # Verificar se tudo está funcionando
   php verificar_sistema.php
   ```

3. **Credenciais do Admin:**
   - Email: `rayssasilveira764@gmail.com`
   - Senha: `rayssa345`

4. **Testar o Sistema:**
   - Acesse `http://localhost/Prototipo-PHP/index.html`
   - Clique em "🔓 Login" → "📝 Criar Conta" para cadastrar usuário
   - Faça login e teste agendamentos
   - Como admin, acesse `admin_dashboard.html`

   Para definir um usuário como admin:
   ```sql
   UPDATE usuarios SET is_admin = TRUE WHERE email = 'seu@email.com';
   ```

   Ou use o arquivo `admin_setup.sql` como referência.

##  Segurança

-  Bcrypt para hash de senhas
-  CSRF tokens
-  Rate Limiting (10 req/min)
-  Validação robusta de entrada
-  Sanitização XSS
-  Security Headers
-  Logging de eventos
-  Proteção contra timing attacks
-  **Controle de permissões por role**

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