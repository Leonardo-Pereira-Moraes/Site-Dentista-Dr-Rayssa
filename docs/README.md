# Prototipo PHP - Sistema de Agendamento

Sistema de agendamento de consultas odontopediátricas para clínicas infantis.

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
| `pages/agendamentos.html` | Formulário para agendar consultas |
| `pages/auth.html` | Login e Cadastro de usuários |
| `pages/dashboard.html` | Área do usuário logado (agendamentos próprios) |
| `pages/admin_dashboard.html` | **Dashboard Administrativo** (todos os agendamentos) |

##  Backend

| Arquivo | Função |
|---------|--------|
| `api/processar_agendamento.php` | Processa agendamentos com validação |
| `api/processar_auth.php` | Autenticação (login/cadastro) |
| `api/admin_agendamentos.php` | **API para admins verem todos os agendamentos** |
| `api/meus_agendamentos.php` | **API para usuários verem seus agendamentos** |
| `api/alterar_status_agendamento.php` | **Alterar status de agendamentos (apenas admin)** |

##  Controle de Acesso

- **Usuários comuns**: Veem apenas seus próprios agendamentos
- **Administradores**: Veem todos os agendamentos e podem alterar status

##  Quick Start

1. **Configurar XAMPP:**
   - Inicie o Apache e MySQL no XAMPP
   - Acesse `http://localhost/phpmyadmin`
   - Execute o script `sql/schema.sql` para criar o banco

2. **Configurar o Sistema:**
   ```bash
   # No terminal/prompt de comando, navegue até a pasta do projeto
   cd "caminho/para/o-projeto"

   # Atualizar estrutura do banco
   php tools/update_db.php

   # Criar usuário admin
   php tools/criar_admin.php

   # Verificar se tudo está funcionando
   php tools/verificar_sistema.php
   ```

3. **Credenciais do Admin:**
   - Email: `admin@clinicaodontokids.com.br`
   - Senha: definida ao executar `php tools/criar_admin.php`

4. **Testar o Sistema:**
   - Acesse `http://localhost/<pasta-do-projeto-dentro-de-htdocs>/index.html`
   - Clique em "Entrar" e depois "Criar Conta" para cadastrar usuário
   - Faça login e teste agendamentos
   - Como admin, acesse `pages/admin_dashboard.html`

   Para definir um usuário como admin:
   ```sql
   UPDATE usuarios SET is_admin = TRUE WHERE email = 'seu@email.com';
   ```

   Ou use o arquivo `sql/admin_setup.sql` como referência.

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
projeto/
├── index.html            # Página inicial (fica na raiz para o Apache servir direto)
├── .htaccess
├── css/style.css
├── img/
├── pages/                # Telas HTML (login, agendamento, dashboards...)
│   ├── auth.html
│   ├── agendamentos.html
│   ├── dashboard.html
│   ├── admin_dashboard.html
│   └── visualizar_agendamentos.html
├── api/                  # Endpoints PHP chamados via fetch() pelas páginas
│   ├── processar_agendamento.php
│   ├── processar_auth.php
│   ├── verificar_autenticacao.php
│   ├── verificar_2fa.php
│   ├── admin_agendamentos.php
│   ├── alterar_status_agendamento.php
│   └── meus_agendamentos.php
├── config/                # Conexão com banco e envio de email (require_once)
│   ├── db.php
│   └── envio_email.php
├── tools/                 # Scripts de instalação, manutenção e teste (CLI/browser)
│   ├── setup.php
│   ├── criar_admin.php
│   ├── update_db.php
│   ├── verificar_sistema.php
│   ├── teste_email.php
│   ├── teste_email_api.php
│   └── test_admin_api.html
├── sql/                   # Scripts de banco de dados
│   ├── schema.sql
│   └── admin_setup.sql
├── docs/                  # Documentação
│   ├── README.md
│   ├── APACHE_CONFIG.md
│   └── SUMÁRIO.txt
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

**Clínica OdontoKids** - Especialista em Odontopediatria

---
*Última atualização: Julho 2026*