-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS prototipo_php CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE prototipo_php;

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id VARCHAR(50) PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(254) UNIQUE NOT NULL,
    telefone VARCHAR(20),
    senha_hash VARCHAR(255) NOT NULL,
    data_cadastro DATETIME NOT NULL,
    verificado BOOLEAN DEFAULT FALSE,
    is_admin BOOLEAN DEFAULT FALSE
);

-- Tabela de agendamentos
CREATE TABLE IF NOT EXISTS agendamentos (
    id VARCHAR(50) PRIMARY KEY,
    usuario_id VARCHAR(50),
    timestamp DATETIME NOT NULL,
    ip_origem VARCHAR(45),
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(254) NOT NULL,
    telefone VARCHAR(20) NOT NULL,
    nomeCrianca VARCHAR(100) NOT NULL,
    idade INT DEFAULT 0,
    servico VARCHAR(100) NOT NULL,
    dataAgendada DATE NOT NULL,
    horaAgendada TIME NOT NULL,
    observacoes TEXT,
    status ENUM('pendente', 'confirmado', 'cancelado') DEFAULT 'pendente',
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Tabela de códigos 2FA
CREATE TABLE IF NOT EXISTS autenticacao_2fa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id VARCHAR(50) NOT NULL,
    codigo VARCHAR(6) NOT NULL,
    ip_origem VARCHAR(45),
    criado_em DATETIME NOT NULL,
    expirado_em DATETIME NOT NULL,
    verificado BOOLEAN DEFAULT FALSE,
    tentativas INT DEFAULT 0,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_codigo (usuario_id, codigo),
    INDEX idx_expirado (expirado_em)
);

-- Tabela de sessões "Manter Login"
CREATE TABLE IF NOT EXISTS sessoes_rememberme (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id VARCHAR(50) NOT NULL,
    token_hash VARCHAR(255) NOT NULL UNIQUE,
    ip_origem VARCHAR(45),
    user_agent VARCHAR(255),
    criado_em DATETIME NOT NULL,
    expira_em DATETIME NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_token (usuario_id, token_hash),
    INDEX idx_expira (expira_em)
);

-- Índices para melhor performance
CREATE INDEX idx_agendamentos_usuario ON agendamentos(usuario_id);
CREATE INDEX idx_agendamentos_data ON agendamentos(dataAgendada);
CREATE INDEX idx_agendamentos_servico ON agendamentos(servico);