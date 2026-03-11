-- Script para criação do banco de dados do sistema AI Pharma Guard

CREATE DATABASE IF NOT EXISTS farmacia;
USE farmacia;

-- Tabela de Pacientes
CREATE TABLE IF NOT EXISTS pacientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    idade INT NOT NULL,
    sexo ENUM('masculino', 'feminino'),
    doencas TEXT,
    medicamentos_usados TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Medicamentos
CREATE TABLE IF NOT EXISTS medicamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    classe_farmacologica VARCHAR(100),
    dose VARCHAR(50),
    indicacao TEXT,
    contraindicacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Interações (nivel_risco: baixo=verde, medio=amarelo, alto=vermelho)
CREATE TABLE IF NOT EXISTS interacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicamentoA INT NOT NULL,
    medicamentoB INT NOT NULL,
    tipo_interacao VARCHAR(100),
    nivel_risco ENUM('baixo', 'medio', 'alto'),
    recomendacao TEXT,
    FOREIGN KEY (medicamentoA) REFERENCES medicamentos(id) ON DELETE CASCADE,
    FOREIGN KEY (medicamentoB) REFERENCES medicamentos(id) ON DELETE CASCADE
);

-- Dados de exemplo (opcional: comentar se quiser banco vazio)
INSERT INTO medicamentos (nome, classe_farmacologica, dose, indicacao, contraindicacoes) VALUES
('Paracetamol', 'Analgésico', '500mg', 'Dor e febre', 'Hepatopatia grave'),
('Ibuprofeno', 'Anti-inflamatório', '400mg', 'Dor e inflamação', 'Úlcera ativa'),
('Ácido Acetilsalicílico', 'Antiagregante', '100mg', 'Prevenção cardiovascular', 'Hemorragia ativa');

INSERT INTO interacoes (medicamentoA, medicamentoB, tipo_interacao, nivel_risco, recomendacao) VALUES
(1, 2, 'Aumento do risco de sangramento e lesão hepática', 'alto', 'Evitar associação; preferir um ou outro conforme indicação.'),
(1, 3, 'Risco de toxicidade hepática e sangramento', 'medio', 'Monitorar função hepática e evitar doses altas.');
