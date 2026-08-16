CREATE DATABASE IF NOT EXISTS projeto_social;
USE projeto_social;

-- 1. Tabela de Usuários (Administradores e Professores)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    nivel ENUM('admin', 'professor') NOT NULL DEFAULT 'professor',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inserção/Atualização do Administrador Oficial
-- Usuário: JBadmin123 | Senha: infomatica2024-2026
INSERT INTO usuarios (nome, usuario, senha, nivel) 
VALUES (
    'Administrador Master', 
    'JBadmin123', 
    '$2y$10$w8.Bf4v.gGf7Y2oM/2wXo.6j6E7wO0vA3H1uP2wQ4R5S6T7U8V9W', 
    'admin'
)
ON DUPLICATE KEY UPDATE 
    senha = '$2y$10$w8.Bf4v.gGf7Y2oM/2wXo.6j6E7wO0vA3H1uP2wQ4R5S6T7U8V9W',
    nivel = 'admin';

-- 2. Tabela de Alunos
CREATE TABLE IF NOT EXISTS alunos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matricula VARCHAR(20) NOT NULL UNIQUE,
    nome VARCHAR(100) NOT NULL,
    turma VARCHAR(50) NOT NULL,
    nascimento DATE NOT NULL,
    sexo VARCHAR(20) NOT NULL,
    curso VARCHAR(50) NOT NULL,
    email VARCHAR(100),
    telefone VARCHAR(20),
    endereco TEXT NOT NULL,
    resp_nome VARCHAR(100) NOT NULL,
    resp_tel VARCHAR(20) NOT NULL,
    resp2_nome VARCHAR(100),
    resp2_tel VARCHAR(20),
    cpf_aluno VARCHAR(20) NOT NULL,
    cpf_resp1 VARCHAR(20) NOT NULL,
    cpf_resp2 VARCHAR(20),
    obs TEXT,
    status VARCHAR(20) DEFAULT 'Ativo',
    criado_em VARCHAR(20)
);

-- 3. Tabela de Registros
CREATE TABLE IF NOT EXISTS registros (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tipo_ocorrencia VARCHAR(50) NOT NULL,
    tipo VARCHAR(20) NOT NULL,
    curso VARCHAR(50) NOT NULL,
    turma VARCHAR(50) NOT NULL,
    aluno VARCHAR(100) NOT NULL,
    matricula VARCHAR(20) NOT NULL,
    data_registro DATE,
    hora_registro TIME,
    motivo_saida VARCHAR(100),
    outro_motivo TEXT,
    observacoes TEXT,
    criado_em VARCHAR(20),
    criado_hora VARCHAR(10),
    lida TINYINT(1) DEFAULT 0,
    FOREIGN KEY (matricula) REFERENCES alunos(matricula) ON DELETE CASCADE ON UPDATE CASCADE
);
ALTER TABLE usuarios 
ADD COLUMN is_pdt TINYINT(1) DEFAULT 0,
ADD COLUMN turma_dirigida VARCHAR(100) NULL,
ADD COLUMN pdt_curso VARCHAR(100) NULL,
ADD COLUMN pdt_serie VARCHAR(50) NULL;
