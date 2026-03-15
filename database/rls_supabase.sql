-- ============================================================
-- RLS (Row Level Security) - Supabase / PostgreSQL
-- AI Pharma Guard: backend PHP conecta com usuário do pooler.
-- Se RLS estiver ativo nas tabelas, as políticas abaixo permitem
-- que o role do backend (postgres / pooler) acesse os dados.
-- Execute no SQL Editor do Supabase apenas se usar RLS.
-- ============================================================

-- Opção 1: Desabilitar RLS nas tabelas (backend usa credenciais próprias)
-- Use isto se o backend for o único cliente e você não usar anon/key no frontend.
ALTER TABLE IF EXISTS farmacias DISABLE ROW LEVEL SECURITY;
ALTER TABLE IF EXISTS pacientes DISABLE ROW LEVEL SECURITY;
ALTER TABLE IF EXISTS medicamentos DISABLE ROW LEVEL SECURITY;
ALTER TABLE IF EXISTS interacoes DISABLE ROW LEVEL SECURITY;

-- Opção 2: Manter RLS e criar políticas para o role que o backend usa
-- (descomente e ajuste 'authenticated' ou o nome do role do pooler)
/*
ALTER TABLE farmacias ENABLE ROW LEVEL SECURITY;
ALTER TABLE pacientes ENABLE ROW LEVEL SECURITY;
ALTER TABLE medicamentos ENABLE ROW LEVEL SECURITY;
ALTER TABLE interacoes ENABLE ROW LEVEL SECURITY;

-- Exemplo: permitir tudo para o role postgres (conexão do backend)
CREATE POLICY "Backend full access farmacias" ON farmacias FOR ALL TO postgres USING (true) WITH CHECK (true);
CREATE POLICY "Backend full access pacientes" ON pacientes FOR ALL TO postgres USING (true) WITH CHECK (true);
CREATE POLICY "Backend full access medicamentos" ON medicamentos FOR ALL TO postgres USING (true) WITH CHECK (true);
CREATE POLICY "Backend full access interacoes" ON interacoes FOR ALL TO postgres USING (true) WITH CHECK (true);
*/
