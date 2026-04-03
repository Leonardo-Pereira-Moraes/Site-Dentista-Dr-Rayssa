# Configuração do Administrador

## Credenciais do Admin
- **Email**: rayssasilveira764@gmail.com
- **Senha**: rayssa345
- **Nome**: Dra. Rayssa Silveira

## Como Configurar

1. Execute o script PHP:
   ```bash
   php criar_admin.php
   ```

2. Ou execute manualmente no banco de dados:
   ```sql
   -- Criar usuário admin
   INSERT INTO usuarios (nome, email, senha_hash, is_admin, data_cadastro, verificado)
   VALUES ('Dra. Rayssa Silveira', 'rayssasilveira764@gmail.com', '$2y$10$hash_da_senha', TRUE, NOW(), TRUE);

   -- Ou atualizar usuário existente
   UPDATE usuarios SET is_admin = TRUE WHERE email = 'rayssasilveira764@gmail.com';
   ```

## Acesso ao Dashboard Admin
- URL: `admin_dashboard.html`
- Apenas usuários com `is_admin = TRUE` podem acessar
- Interface completa para gerenciar todos os agendamentos

## Funcionalidades do Admin
- ✅ Ver todos os agendamentos
- ✅ Filtrar por data, serviço e status
- ✅ Confirmar agendamentos
- ✅ Cancelar agendamentos
- ✅ Ver estatísticas em tempo real
- ✅ Visualizar detalhes completos