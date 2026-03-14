-- Schema PostgreSQL para Supabase (AI Pharma Guard)
-- Execute no SQL Editor do Supabase. Cria tabelas com snake_case para compatibilidade.

-- Tabela farmacias
CREATE TABLE IF NOT EXISTS farmacias (
  id SERIAL PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  email VARCHAR(150) UNIQUE NOT NULL,
  senha VARCHAR(255) NOT NULL,
  telefone VARCHAR(50),
  data_criacao TIMESTAMPTZ DEFAULT NOW()
);

-- Tabela pacientes
CREATE TABLE IF NOT EXISTS pacientes (
  id SERIAL PRIMARY KEY,
  farmacia_id INT NOT NULL REFERENCES farmacias(id) ON DELETE CASCADE,
  nome VARCHAR(100) NOT NULL,
  idade INT NOT NULL,
  sexo VARCHAR(20) DEFAULT 'masculino',
  doencas TEXT,
  medicamentos_usados TEXT,
  alergias TEXT,
  historico_clinico TEXT,
  observacoes TEXT,
  created_at TIMESTAMPTZ DEFAULT NOW()
);

-- Tabela medicamentos
CREATE TABLE IF NOT EXISTS medicamentos (
  id SERIAL PRIMARY KEY,
  farmacia_id INT NOT NULL REFERENCES farmacias(id) ON DELETE CASCADE,
  nome VARCHAR(100) NOT NULL,
  classe_farmacologica VARCHAR(100),
  dose VARCHAR(50),
  indicacao TEXT,
  contraindicacoes TEXT,
  created_at TIMESTAMPTZ DEFAULT NOW()
);

-- Tabela interacoes (snake_case para PostgreSQL)
CREATE TABLE IF NOT EXISTS interacoes (
  id SERIAL PRIMARY KEY,
  farmacia_id INT NOT NULL REFERENCES farmacias(id) ON DELETE CASCADE,
  medicamento_a INT NOT NULL REFERENCES medicamentos(id),
  medicamento_b INT NOT NULL REFERENCES medicamentos(id),
  tipo_interacao VARCHAR(100),
  nivel_risco VARCHAR(20) DEFAULT 'medio',
  recomendacao TEXT
);

-- Índices para performance
CREATE INDEX IF NOT EXISTS idx_pacientes_farmacia ON pacientes(farmacia_id);
CREATE INDEX IF NOT EXISTS idx_medicamentos_farmacia ON medicamentos(farmacia_id);
CREATE INDEX IF NOT EXISTS idx_interacoes_farmacia ON interacoes(farmacia_id);

-- Inserir farmácia padrão (id=1) para uso sem login
INSERT INTO farmacias (id, nome, email, senha, telefone)
VALUES (1, 'Farmácia Padrão', 'admin@pharmaguard.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL)
ON CONFLICT (id) DO NOTHING;
-- Senha padrão: password (troque após primeiro login)

SELECT setval('farmacias_id_seq', (SELECT COALESCE(MAX(id), 1) FROM farmacias));
