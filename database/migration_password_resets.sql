-- =============================================================================
-- AI Pharma Guard - Recuperação de senha por e-mail
-- Execute no Supabase SQL Editor.
-- =============================================================================

CREATE TABLE IF NOT EXISTS password_resets (
  id SERIAL PRIMARY KEY,
  farmacia_id INT NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  expira_em TIMESTAMP NOT NULL,
  usado BOOLEAN DEFAULT false,
  created_at TIMESTAMP DEFAULT NOW()
);

-- Índice para busca por token e limpeza de expirados
CREATE INDEX IF NOT EXISTS idx_password_resets_token ON password_resets (token);
CREATE INDEX IF NOT EXISTS idx_password_resets_expira ON password_resets (expira_em);
