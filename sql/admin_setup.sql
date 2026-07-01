-- Script SQL para definir um usuário como administrador
-- Substitua 'EMAIL_DO_ADMIN' pelo email do usuário que deve ser admin

-- ADMIN PRINCIPAL DO SISTEMA:
-- UPDATE usuarios SET is_admin = TRUE WHERE email = 'admin@clinicaodontokids.com.br';

-- Para listar todos os usuários:
-- SELECT id, nome, email, is_admin FROM usuarios;

-- Para definir o primeiro usuário como admin:
-- UPDATE usuarios SET is_admin = TRUE WHERE id = (SELECT id FROM usuarios ORDER BY data_cadastro ASC LIMIT 1);

-- Para remover admin de um usuário:
-- UPDATE usuarios SET is_admin = FALSE WHERE email = 'email@exemplo.com';