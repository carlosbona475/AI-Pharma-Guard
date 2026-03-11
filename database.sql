-- Script para criação do banco de dados do sistema farmacêutico

CREATE DATABASE IF NOT EXISTS farmacia;
USE farmacia;

-- Tabela de Pacientes
CREATE TABLE IF NOT EXISTS pacientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    idade INT NOT NULL,
    sexo ENUM('masculino', 'feminino'),
    doencas TEXT,
    medicamentos_usados TEXT
);

-- Tabela de Medicamentos
CREATE TABLE IF NOT EXISTS medicamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    classe_farmacologica VARCHAR(100),
    dose VARCHAR(50),
    indicacao TEXT,
    contraindicacoes TEXT
);

-- Tabela de Interações
CREATE TABLE IF NOT EXISTS interacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicamentoA INT,
    medicamentoB INT,
    tipo_interacao VARCHAR(100),
    nivel_risco ENUM('baixo', 'medio', 'alto'),
    recomendacao TEXT,
    FOREIGN KEY (medicamentoA) REFERENCES medicamentos(id),
    FOREIGN KEY (medicamentoB) REFERENCES medicamentos(id)
);