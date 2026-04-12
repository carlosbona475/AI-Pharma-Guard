-- AI Pharma Guard — schema MySQL (Hostinger)
-- Charset: utf8mb4

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- Tabelas principais
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS farmacias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL,
  senha VARCHAR(255) NOT NULL,
  telefone VARCHAR(50),
  data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
  ativo TINYINT(1) NOT NULL DEFAULT 0,
  aprovado_em DATETIME NULL,
  UNIQUE KEY uq_farmacias_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pacientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  farmacia_id INT NOT NULL,
  nome VARCHAR(100) NOT NULL,
  idade INT NOT NULL,
  sexo VARCHAR(20) DEFAULT 'masculino',
  doencas TEXT,
  medicamentos_usados TEXT,
  alergias TEXT,
  historico_clinico TEXT,
  observacoes TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pacientes_farmacia FOREIGN KEY (farmacia_id) REFERENCES farmacias(id) ON DELETE CASCADE,
  KEY idx_pacientes_farmacia (farmacia_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS medicamentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  farmacia_id INT NOT NULL,
  nome VARCHAR(100) NOT NULL,
  classe_farmacologica VARCHAR(100),
  dose VARCHAR(50),
  indicacao TEXT,
  contraindicacoes TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_medicamentos_farmacia FOREIGN KEY (farmacia_id) REFERENCES farmacias(id) ON DELETE CASCADE,
  KEY idx_medicamentos_farmacia (farmacia_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS interacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  farmacia_id INT NOT NULL,
  medicamento_a INT NOT NULL,
  medicamento_b INT NOT NULL,
  tipo_interacao VARCHAR(100),
  nivel_risco VARCHAR(20) DEFAULT 'medio',
  recomendacao TEXT,
  CONSTRAINT fk_interacoes_farmacia FOREIGN KEY (farmacia_id) REFERENCES farmacias(id) ON DELETE CASCADE,
  CONSTRAINT fk_interacoes_med_a FOREIGN KEY (medicamento_a) REFERENCES medicamentos(id),
  CONSTRAINT fk_interacoes_med_b FOREIGN KEY (medicamento_b) REFERENCES medicamentos(id),
  KEY idx_interacoes_farmacia (farmacia_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Tabelas auxiliares usadas pelo backend (recuperação de senha e checagem global)
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  farmacia_id INT NOT NULL,
  token VARCHAR(64) NOT NULL,
  expira_em DATETIME NOT NULL,
  usado TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_password_resets_token (token),
  KEY idx_password_resets_expira (expira_em),
  CONSTRAINT fk_password_resets_farmacia FOREIGN KEY (farmacia_id) REFERENCES farmacias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS interacoes_globais (
  id INT AUTO_INCREMENT PRIMARY KEY,
  medicamento_a_nome VARCHAR(255) NOT NULL,
  medicamento_b_nome VARCHAR(255) NOT NULL,
  tipo_interacao VARCHAR(100),
  nivel_risco VARCHAR(50),
  recomendacao TEXT,
  KEY idx_interacoes_globais_a (medicamento_a_nome(100)),
  KEY idx_interacoes_globais_b (medicamento_b_nome(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
