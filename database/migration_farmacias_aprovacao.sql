-- =============================================================================
-- AI Pharma Guard - Controle de aprovação de farmácias
-- Execute no Supabase SQL Editor.
-- =============================================================================

ALTER TABLE farmacias ADD COLUMN IF NOT EXISTS ativo BOOLEAN DEFAULT false;
ALTER TABLE farmacias ADD COLUMN IF NOT EXISTS aprovado_em TIMESTAMP;

-- Para aprovar uma farmácia manualmente (rode no Supabase):
-- UPDATE farmacias SET ativo = true, aprovado_em = NOW() WHERE email = 'email@farmacia.com';
