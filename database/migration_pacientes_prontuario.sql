-- Migração: adicionar campos de prontuário à tabela pacientes
-- Execute este script se a tabela pacientes já existir (ALTER TABLE seguro).

USE farmacia;

-- Adicionar colunas apenas se não existirem (MySQL 8.0+ não suporta IF NOT EXISTS em ADD COLUMN;
-- execute uma vez; se der erro de coluna duplicada, ignore).

ALTER TABLE pacientes ADD COLUMN alergias TEXT NULL AFTER medicamentos_usados;
ALTER TABLE pacientes ADD COLUMN historico_clinico TEXT NULL AFTER alergias;
ALTER TABLE pacientes ADD COLUMN observacoes TEXT NULL AFTER historico_clinico;
