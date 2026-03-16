-- =============================================================================
-- AI Pharma Guard - Seed de interações medicamentosas (literatura oficial)
-- ANVISA, Manual MSD, ICTQ, SES-DF. Interações globais (visíveis a todas as farmácias).
-- =============================================================================
-- Uso: execute no Supabase (PostgreSQL) ou adapte para MySQL.
-- Tabela interacoes_globais: por nome de medicamento (não depende de IDs de medicamentos).
-- =============================================================================

-- Tabela de interações globais por nome (sem FK para medicamentos)
CREATE TABLE IF NOT EXISTS interacoes_globais (
  id SERIAL PRIMARY KEY,
  medicamento_a_nome VARCHAR(200) NOT NULL,
  medicamento_b_nome VARCHAR(200) NOT NULL,
  tipo_interacao VARCHAR(100),
  nivel_risco VARCHAR(20) NOT NULL,
  recomendacao TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_interacoes_globais_a ON interacoes_globais (LOWER(medicamento_a_nome));
CREATE INDEX IF NOT EXISTS idx_interacoes_globais_b ON interacoes_globais (LOWER(medicamento_b_nome));
CREATE INDEX IF NOT EXISTS idx_interacoes_globais_risco ON interacoes_globais (nivel_risco);

-- Limpar para recarga (opcional)
-- TRUNCATE interacoes_globais RESTART IDENTITY;

-- =============================================================================
-- INTERAÇÕES GRAVES (nivel_risco = 'grave')
-- =============================================================================
INSERT INTO interacoes_globais (medicamento_a_nome, medicamento_b_nome, tipo_interacao, nivel_risco, recomendacao) VALUES
('Varfarina', 'AAS', 'farmacodinâmica', 'grave', 'Risco grave de hemorragia. Evitar uso concomitante. Monitorar RNI rigorosamente se inevitável.'),
('Varfarina', 'Ácido Acetilsalicílico', 'farmacodinâmica', 'grave', 'Risco grave de hemorragia. Evitar uso concomitante. Monitorar RNI rigorosamente se inevitável.'),
('Varfarina', 'Amiodarona', 'farmacocinética', 'grave', 'Amiodarona inibe metabolismo de ambos isômeros da varfarina, potencializando efeito anticoagulante. Risco grave de hemorragia. Reduzir dose da varfarina e monitorar RNI.'),
('Varfarina', 'Metronidazol', 'farmacocinética', 'grave', 'Metronidazol inibe metabolismo da varfarina. Risco de hemorragia grave. Monitorar RNI e ajustar dose.'),
('Varfarina', 'Fluconazol', 'farmacocinética', 'grave', 'Fluconazol inibe CYP2C9, aumentando efeito da varfarina. Risco de hemorragia. Monitorar RNI.'),
('Varfarina', 'Sulfametoxazol-Trimetoprima', 'farmacocinética', 'grave', 'Potencializa ação anticoagulante da varfarina. Risco de hemorragia grave. Monitorar RNI.'),
('Varfarina', 'Diclofenaco', 'farmacodinâmica', 'grave', 'AINE aumenta risco de sangramento gastrointestinal e potencializa efeito anticoagulante. Evitar. Se necessário, usar protetor gástrico e monitorar.'),
('IMAO', 'Tiramina', 'farmacodinâmica', 'grave', 'Risco de crise hipertensiva grave e hemorragia intracraniana. Pacientes em uso de IMAO devem evitar alimentos ricos em tiramina.'),
('IMAO', 'Fluoxetina', 'farmacodinâmica', 'grave', 'Risco de síndrome serotoninérgica grave, podendo ser fatal. Contraindicado. Aguardar 14 dias após suspender fluoxetina antes de iniciar IMAO.'),
('IMAO', 'Tramadol', 'farmacodinâmica', 'grave', 'Risco de síndrome serotoninérgica grave. Contraindicado.'),
('Metotrexato', 'AAS', 'farmacocinética', 'grave', 'AAS inibe excreção renal do metotrexato, causando toxicidade grave. Evitar uso concomitante.'),
('Metotrexato', 'Ácido Acetilsalicílico', 'farmacocinética', 'grave', 'AAS inibe excreção renal do metotrexato, causando toxicidade grave. Evitar uso concomitante.'),
('Metotrexato', 'Diclofenaco', 'farmacocinética', 'grave', 'AINEs inibem excreção renal do metotrexato, elevando risco de toxicidade grave (mielossupressão, mucosite). Contraindicado em doses altas de metotrexato.'),
('Metotrexato', 'Sulfametoxazol-Trimetoprima', 'farmacocinética', 'grave', 'Aumenta toxicidade do metotrexato por redução da excreção renal e inibição do metabolismo do folato. Contraindicado.'),
('Digoxina', 'Amiodarona', 'farmacocinética', 'grave', 'Amiodarona aumenta níveis séricos de digoxina. Risco de toxicidade digitálica (arritmia, náusea, distúrbio visual). Reduzir dose de digoxina em 50%.'),
('Digoxina', 'Diazepam', 'farmacocinética', 'grave', 'Diazepam reduz excreção renal da digoxina. Risco de toxicidade. Monitorar sinais de intoxicação digitálica.'),
('Álcool', 'Metronidazol', 'farmacodinâmica', 'grave', 'Reação tipo dissulfiram: taquicardia, vômito, queda de pressão, rubor, cefaleia intensa. Contraindicado. Orientar paciente a não consumir álcool durante o tratamento e 48h após.'),
('Álcool', 'Benzodiazepínicos', 'farmacodinâmica', 'grave', 'Potencialização grave da depressão do SNC. Risco de depressão respiratória, coma e morte. Contraindicado.'),
('Álcool', 'Diazepam', 'farmacodinâmica', 'grave', 'Potencialização grave da depressão do SNC. Risco de depressão respiratória, coma e morte. Contraindicado.'),
('Álcool', 'Alprazolam', 'farmacodinâmica', 'grave', 'Potencialização grave da depressão do SNC. Risco de depressão respiratória, coma e morte. Contraindicado.'),
('Álcool', 'Clonazepam', 'farmacodinâmica', 'grave', 'Potencialização grave da depressão do SNC. Risco de depressão respiratória, coma e morte. Contraindicado.'),
('Cloroquina', 'Azitromicina', 'farmacodinâmica', 'grave', 'Prolongamento do intervalo QT com risco de arritmia grave (Torsades de Pointes). Evitar associação. Monitorar ECG se inevitável.'),
('Hidroxicloroquina', 'Azitromicina', 'farmacodinâmica', 'grave', 'Prolongamento do intervalo QT com risco de arritmia grave (Torsades de Pointes). Evitar associação. Monitorar ECG se inevitável.'),
('Carbamazepina', 'Haloperidol', 'farmacocinética', 'grave', 'Carbamazepina induz metabolismo do haloperidol, reduzindo seus níveis plasmáticos. Ajustar dose do haloperidol.'),
('Lítio', 'Ibuprofeno', 'farmacocinética', 'grave', 'AINEs reduzem excreção renal do lítio, elevando seus níveis séricos. Risco de toxicidade por lítio. Monitorar litemias e função renal.'),
('Lítio', 'Diclofenaco', 'farmacocinética', 'grave', 'AINEs reduzem excreção renal do lítio, elevando seus níveis séricos. Risco de toxicidade por lítio. Monitorar litemias e função renal.'),
('Lítio', 'Naproxeno', 'farmacocinética', 'grave', 'AINEs reduzem excreção renal do lítio, elevando seus níveis séricos. Risco de toxicidade por lítio. Monitorar litemias e função renal.'),
('Lítio', 'Hidroclorotiazida', 'farmacocinética', 'grave', 'Diuréticos reduzem excreção renal do lítio. Risco de intoxicação por lítio. Monitorar litemias.');

-- =============================================================================
-- INTERAÇÕES MODERADAS (nivel_risco = 'moderada')
-- =============================================================================
INSERT INTO interacoes_globais (medicamento_a_nome, medicamento_b_nome, tipo_interacao, nivel_risco, recomendacao) VALUES
('AAS', 'Captopril', 'farmacodinâmica', 'moderada', 'AAS pode reduzir efeito anti-hipertensivo do captopril. Monitorar pressão arterial. Preferir paracetamol como analgésico.'),
('AAS', 'IECA', 'farmacodinâmica', 'moderada', 'AAS pode reduzir efeito anti-hipertensivo do IECA. Monitorar pressão arterial. Preferir paracetamol como analgésico.'),
('AAS', 'Insulina', 'farmacodinâmica', 'moderada', 'AAS pode potencializar efeito hipoglicemiante da insulina. Monitorar glicemia.'),
('Omeprazol', 'Clopidogrel', 'farmacocinética', 'moderada', 'Omeprazol inibe CYP2C19, reduzindo ativação do clopidogrel e seu efeito antiagregante. Preferir pantoprazol se necessário protetor gástrico.'),
('Omeprazol', 'Varfarina', 'farmacocinética', 'moderada', 'Omeprazol inibe metabolismo da varfarina (isômero R). Pode aumentar efeito anticoagulante. Monitorar RNI.'),
('Omeprazol', 'Fenobarbital', 'farmacocinética', 'moderada', 'Omeprazol pode potencializar ação do fenobarbital. Monitorar sinais de sedação excessiva.'),
('Fluoxetina', 'Varfarina', 'farmacocinética', 'moderada', 'ISRS inibem agregação plaquetária e podem inibir metabolismo da varfarina. Risco de sangramento aumentado. Monitorar RNI.'),
('Fluoxetina', 'Metoprolol', 'farmacocinética', 'moderada', 'Fluoxetina e paroxetina inibem CYP2D6, aumentando níveis dos betabloqueadores. Risco de bradicardia e hipotensão. Monitorar FC e PA.'),
('Paroxetina', 'Metoprolol', 'farmacocinética', 'moderada', 'Fluoxetina e paroxetina inibem CYP2D6, aumentando níveis dos betabloqueadores. Risco de bradicardia e hipotensão. Monitorar FC e PA.'),
('Paroxetina', 'Propranolol', 'farmacocinética', 'moderada', 'Fluoxetina e paroxetina inibem CYP2D6, aumentando níveis dos betabloqueadores. Risco de bradicardia e hipotensão. Monitorar FC e PA.'),
('Rifampicina', 'Anticoncepcionais orais', 'farmacocinética', 'moderada', 'Rifampicina é potente indutor enzimático, reduzindo eficácia do anticoncepcional. Usar método contraceptivo adicional durante o tratamento e por 28 dias após.'),
('Carbamazepina', 'Anticoncepcionais orais', 'farmacocinética', 'moderada', 'Carbamazepina induz metabolismo dos anticoncepcionais, reduzindo sua eficácia. Usar método contraceptivo adicional.'),
('Anticoncepcionais orais', 'Anti-hipertensivos', 'farmacodinâmica', 'moderada', 'Anticoncepcionais podem elevar pressão arterial, antagonizando efeito dos anti-hipertensivos. Monitorar PA regularmente.'),
('Lamotrigina', 'Anticoncepcionais orais', 'farmacocinética', 'moderada', 'ACO induz metabolismo da lamotrigina, reduzindo seus níveis em até 50%. Pode ser necessário dobrar a dose da lamotrigina. Monitorar convulsões.'),
('Metformina', 'Álcool', 'farmacodinâmica', 'moderada', 'Aumento do risco de acidose láctica. Orientar paciente a evitar consumo de álcool.'),
('Benzodiazepínicos', 'Cimetidina', 'farmacocinética', 'moderada', 'Cimetidina reduz metabolismo de diazepam, alprazolam e outros BZD. Aumenta sedação e meia-vida plasmática. Monitorar sedação.'),
('Diazepam', 'Cimetidina', 'farmacocinética', 'moderada', 'Cimetidina reduz metabolismo de diazepam, alprazolam e outros BZD. Aumenta sedação e meia-vida plasmática. Monitorar sedação.'),
('Tetraciclina', 'Leite', 'farmacocinética', 'moderada', 'Íons divalentes formam quelatos com tetraciclina, impedindo sua absorção. Administrar tetraciclina 2h antes ou 4h após antiácidos e laticínios.'),
('Tetraciclina', 'Antiácidos', 'farmacocinética', 'moderada', 'Íons divalentes formam quelatos com tetraciclina, impedindo sua absorção. Administrar tetraciclina 2h antes ou 4h após antiácidos e laticínios.'),
('Furosemida', 'Aminoglicosídeos', 'farmacodinâmica', 'moderada', 'Potencialização da nefrotoxicidade e ototoxicidade. Monitorar função renal e audição.'),
('Furosemida', 'Gentamicina', 'farmacodinâmica', 'moderada', 'Potencialização da nefrotoxicidade e ototoxicidade. Monitorar função renal e audição.'),
('Furosemida', 'Amicacina', 'farmacodinâmica', 'moderada', 'Potencialização da nefrotoxicidade e ototoxicidade. Monitorar função renal e audição.'),
('Haloperidol', 'Lítio', 'farmacodinâmica', 'moderada', 'Combinação pode aumentar neurotoxicidade. Monitorar sinais de toxicidade por lítio e sintomas extrapiramidais.'),
('Escitalopram', 'Tramadol', 'farmacodinâmica', 'moderada', 'Risco de síndrome serotoninérgica. Monitorar sintomas como agitação, hipertermia, taquicardia, tremores.'),
('Citalopram', 'Tramadol', 'farmacodinâmica', 'moderada', 'Risco de síndrome serotoninérgica. Monitorar sintomas como agitação, hipertermia, taquicardia, tremores.'),
('Ciprofloxacino', 'Antiácidos', 'farmacocinética', 'moderada', 'Antiácidos reduzem absorção do ciprofloxacino em até 90%. Administrar ciprofloxacino 2h antes ou 6h após o antiácido.'),
('Atorvastatina', 'Eritromicina', 'farmacocinética', 'moderada', 'Macrolídeos inibem CYP3A4, aumentando níveis de atorvastatina. Risco de miopatia e rabdomiólise. Suspender estatina durante o tratamento com macrolídeo se possível.'),
('Atorvastatina', 'Claritromicina', 'farmacocinética', 'moderada', 'Macrolídeos inibem CYP3A4, aumentando níveis de atorvastatina. Risco de miopatia e rabdomiólise. Suspender estatina durante o tratamento com macrolídeo se possível.'),
('Atorvastatina', 'Fluconazol', 'farmacocinética', 'moderada', 'Fluconazol inibe CYP3A4, aumentando níveis de atorvastatina. Risco de miopatia. Monitorar.');

-- =============================================================================
-- INTERAÇÕES LEVES (nivel_risco = 'baixo')
-- =============================================================================
INSERT INTO interacoes_globais (medicamento_a_nome, medicamento_b_nome, tipo_interacao, nivel_risco, recomendacao) VALUES
('Levodopa', 'Dieta hiperproteica', 'farmacocinética', 'baixo', 'Aminoácidos competem com levodopa na absorção intestinal. Orientar paciente a tomar levodopa 30 min antes das refeições e evitar dieta muito proteica.'),
('Ampicilina', 'Vitamina C', 'farmacocinética', 'baixo', 'Vitamina C em altas doses pode inibir ação de alguns antibióticos. Evitar sucos cítricos junto com o antibiótico.'),
('Amoxicilina', 'Vitamina C', 'farmacocinética', 'baixo', 'Vitamina C em altas doses pode inibir ação de alguns antibióticos. Evitar sucos cítricos junto com o antibiótico.'),
('Óleo mineral', 'Vitaminas lipossolúveis', 'farmacocinética', 'baixo', 'Óleo mineral reduz absorção de vitaminas lipossolúveis (A, D, E, K). Não usar cronicamente. Administrar vitaminas em horários diferentes.'),
('Furosemida', 'Potássio', 'farmacocinética', 'baixo', 'Furosemida aumenta excreção de potássio, magnésio, cálcio e zinco. Monitorar eletrólitos. Considerar suplementação de potássio.'),
('Furosemida', 'Magnésio', 'farmacocinética', 'baixo', 'Furosemida aumenta excreção de potássio, magnésio, cálcio e zinco. Monitorar eletrólitos. Considerar suplementação.'),
('Penicilina', 'Alimentos', 'farmacocinética', 'baixo', 'Alimentos alteram pH gástrico e podem reduzir absorção. Administrar em jejum ou conforme orientação da bula.'),
('Eritromicina', 'Alimentos', 'farmacocinética', 'baixo', 'Alimentos alteram pH gástrico e podem reduzir absorção. Administrar em jejum ou conforme orientação da bula.');

-- =============================================================================
-- INTERAÇÕES GRAVES (continuação 46-60)
-- =============================================================================
INSERT INTO interacoes_globais (medicamento_a_nome, medicamento_b_nome, tipo_interacao, nivel_risco, recomendacao) VALUES
('Sinvastatina', 'Cetoconazol', 'farmacocinética', 'grave', 'Cetoconazol inibe CYP3A4, elevando níveis de sinvastatina até 20x. Risco de rabdomiólise e insuficiência renal aguda. Contraindicado.'),
('Sinvastatina', 'Claritromicina', 'farmacocinética', 'grave', 'Claritromicina inibe CYP3A4, aumentando exposição à sinvastatina. Risco de miopatia grave e rabdomiólise. Suspender sinvastatina durante o tratamento.'),
('Amiodarona', 'Claritromicina', 'farmacodinâmica', 'grave', 'Ambos prolongam intervalo QT. Risco de arritmia ventricular grave (Torsades de Pointes). Contraindicado. Monitorar ECG se inevitável.'),
('Amiodarona', 'Azitromicina', 'farmacodinâmica', 'grave', 'Prolongamento aditivo do intervalo QT. Risco de Torsades de Pointes. Evitar associação. Preferir outro antibiótico.'),
('Fluoxetina', 'Sibutramina', 'farmacodinâmica', 'grave', 'Fluoxetina inibe metabolismo da sibutramina e potencializa síndrome serotoninérgica. Risco de hipertensão grave e taquicardia. Contraindicado.'),
('Prednisona', 'Ibuprofeno', 'farmacodinâmica', 'grave', 'Associação aumenta drasticamente risco de úlcera péptica e hemorragia gastrointestinal. Evitar. Se inevitável, usar protetor gástrico (IBP).'),
('Prednisona', 'Diclofenaco', 'farmacodinâmica', 'grave', 'Associação aumenta drasticamente risco de úlcera péptica e hemorragia gastrointestinal. Evitar. Se inevitável, usar protetor gástrico (IBP).'),
('Corticosteroide', 'AINEs', 'farmacodinâmica', 'grave', 'Associação aumenta drasticamente risco de úlcera péptica e hemorragia gastrointestinal. Evitar. Se inevitável, usar protetor gástrico (IBP).'),
('Ciclosporina', 'Cetoconazol', 'farmacocinética', 'grave', 'Cetoconazol inibe metabolismo da ciclosporina, elevando seus níveis sanguíneos. Risco de nefrotoxicidade grave. Monitorar função renal e níveis de ciclosporina rigorosamente.'),
('Fenitoína', 'Rifampicina', 'farmacocinética', 'grave', 'Rifampicina induz metabolismo da fenitoína, reduzindo seus níveis e aumentando risco de convulsões. Monitorar níveis séricos de fenitoína e ajustar dose.'),
('Tramadol', 'IMAO', 'farmacodinâmica', 'grave', 'Risco grave de síndrome serotoninérgica e convulsões. Contraindicado. Aguardar 14 dias após suspender IMAO antes de usar tramadol.'),
('Varfarina', 'Vitamina K', 'farmacodinâmica', 'grave', 'Vitamina K antagoniza efeito anticoagulante da varfarina. Pacientes em uso de varfarina devem manter consumo de vegetais ricos em vitamina K constante e controlado. Variações bruscas alteram o RNI.'),
('Ciprofloxacino', 'Laticínios', 'farmacocinética', 'grave', 'Cálcio e outros íons divalentes formam quelatos com quinolonas, reduzindo absorção em até 90%. Administrar quinolona 2h antes ou 6h após laticínios e antiácidos.'),
('Ciprofloxacino', 'Antiácidos com cálcio', 'farmacocinética', 'grave', 'Cálcio e outros íons divalentes formam quelatos com quinolonas, reduzindo absorção em até 90%. Administrar quinolona 2h antes ou 6h após laticínios e antiácidos.'),
('Metotrexato', 'Omeprazol', 'farmacocinética', 'grave', 'Omeprazol inibe transporte renal do metotrexato, elevando seus níveis séricos. Risco de toxicidade grave. Monitorar função renal e níveis de metotrexato. Considerar suspender IBP durante tratamento com metotrexato.'),
('Álcool', 'Paracetamol', 'farmacocinética', 'grave', 'Em usuários crônicos de álcool, paracetamol tem metabolismo desviado para via tóxica, produzindo NAPQI em excesso. Risco de hepatotoxicidade grave mesmo em doses terapêuticas. Evitar ou reduzir dose.'),
('Lítio', 'Captopril', 'farmacocinética', 'grave', 'IECA reduzem excreção renal do lítio. Risco de toxicidade por lítio (tremores, confusão, arritmia). Monitorar litemias e função renal ao iniciar IECA.'),
('Lítio', 'Enalapril', 'farmacocinética', 'grave', 'IECA reduzem excreção renal do lítio. Risco de toxicidade por lítio (tremores, confusão, arritmia). Monitorar litemias e função renal ao iniciar IECA.'),
('Lítio', 'IECA', 'farmacocinética', 'grave', 'IECA reduzem excreção renal do lítio. Risco de toxicidade por lítio (tremores, confusão, arritmia). Monitorar litemias e função renal ao iniciar IECA.'),
('Teofilina', 'Ciprofloxacino', 'farmacocinética', 'grave', 'Ciprofloxacino inibe CYP1A2, aumentando níveis de teofilina. Risco de toxicidade (convulsões, arritmia, náusea). Reduzir dose de teofilina e monitorar níveis séricos.');

-- =============================================================================
-- INTERAÇÕES MODERADAS (continuação 61-90)
-- =============================================================================
INSERT INTO interacoes_globais (medicamento_a_nome, medicamento_b_nome, tipo_interacao, nivel_risco, recomendacao) VALUES
('Metformina', 'Contraste iodado', 'farmacocinética', 'moderada', 'Contraste iodado pode causar insuficiência renal aguda, acumulando metformina e aumentando risco de acidose láctica. Suspender metformina 48h antes e 48h após exame com contraste. Avaliar função renal antes de reintroduzir.'),
('Omeprazol', 'Diazepam', 'farmacocinética', 'moderada', 'Omeprazol inibe CYP2C19, reduzindo metabolismo do diazepam. Aumento dos níveis plasmáticos. Risco de ataxia, fraqueza muscular e sedação excessiva. Monitorar.'),
('Sibutramina', 'Antidepressivos', 'farmacodinâmica', 'moderada', 'Risco de síndrome serotoninérgica. Associação geralmente contraindicada. Discutir com prescritor antes de dispensar.'),
('Valeriana', 'Diazepam', 'farmacodinâmica', 'moderada', 'Valeriana potencializa efeito sedativo dos benzodiazepínicos. Risco de letargia e hipotensão. Orientar paciente a evitar associação.'),
('Valeriana', 'Clonazepam', 'farmacodinâmica', 'moderada', 'Valeriana potencializa efeito sedativo dos benzodiazepínicos. Risco de letargia e hipotensão. Orientar paciente a evitar associação.'),
('Ginkgo biloba', 'AAS', 'farmacodinâmica', 'moderada', 'Ginkgo biloba inibe fator de ativação plaquetária. Associação com AAS aumenta risco de sangramento. Orientar paciente a informar uso de fitoterápicos.'),
('Guaraná', 'Varfarina', 'farmacodinâmica', 'moderada', 'Guaraná inibe agregação plaquetária e pode potencializar efeito anticoagulante. Orientar paciente a evitar suplementos com guaraná durante anticoagulação.'),
('Cafeína', 'Varfarina', 'farmacodinâmica', 'moderada', 'Cafeína/guaraná podem potencializar efeito anticoagulante. Orientar paciente a evitar uso excessivo durante anticoagulação.'),
('Espinheira santa', 'Antibióticos', 'farmacocinética', 'moderada', 'Espinheira santa pode reduzir absorção de antibióticos. Orientar paciente a não usar fitoterápico junto com antibiótico.'),
('Erva-de-são-joão', 'Anticoncepcionais orais', 'farmacocinética', 'moderada', 'Erva-de-são-joão é potente indutor enzimático (CYP3A4). Reduz eficácia dos anticoncepcionais. Usar método contraceptivo adicional. Também reduz eficácia de varfarina, ciclosporina e antirretrovirais.'),
('Erva-de-são-joão', 'Varfarina', 'farmacocinética', 'moderada', 'Indução de CYP2C9 pela erva-de-são-joão reduz níveis de varfarina. Risco de trombose. Contraindicado. Orientar paciente a não usar sem supervisão médica.'),
('Eritromicina', 'Carbamazepina', 'farmacocinética', 'moderada', 'Eritromicina inibe CYP3A4, aumentando níveis de carbamazepina. Risco de toxicidade (diplopia, ataxia, sedação). Monitorar níveis de carbamazepina.'),
('Fenitoína', 'Ácido valproico', 'farmacocinética', 'moderada', 'Interação complexa e bidirecional. Valproico pode aumentar ou diminuir fenitoína. Fenitoína reduz valproico. Monitorar níveis séricos de ambos.'),
('Álcool', 'Ibuprofeno', 'farmacodinâmica', 'moderada', 'Combinação aumenta risco de irritação e hemorragia gastrointestinal. Também potencializa hepatotoxicidade. Orientar paciente a evitar.'),
('Álcool', 'Naproxeno', 'farmacodinâmica', 'moderada', 'Combinação aumenta risco de irritação e hemorragia gastrointestinal. Também potencializa hepatotoxicidade. Orientar paciente a evitar.'),
('Álcool', 'Glibenclamida', 'farmacodinâmica', 'moderada', 'Álcool potencializa efeito hipoglicemiante. Risco de hipoglicemia grave. Orientar paciente a evitar consumo de álcool.'),
('Álcool', 'Metformina', 'farmacodinâmica', 'moderada', 'Álcool potencializa efeito hipoglicemiante. Risco de hipoglicemia grave. Orientar paciente a evitar consumo de álcool.'),
('Álcool', 'Fenitoína', 'farmacodinâmica', 'moderada', 'Álcool reduz proteção antiepiléptica significativamente. Risco de convulsões. Contraindicado.'),
('Álcool', 'Carbamazepina', 'farmacodinâmica', 'moderada', 'Álcool reduz proteção antiepiléptica significativamente. Risco de convulsões. Contraindicado.'),
('Amitriptilina', 'Anti-hipertensivos', 'farmacodinâmica', 'moderada', 'Tricíclicos podem causar hipotensão ortostática e potencializar efeito de anti-hipertensivos. Monitorar pressão arterial, especialmente em idosos.'),
('Sertralina', 'AINEs', 'farmacodinâmica', 'moderada', 'ISRS inibem agregação plaquetária. Associação com AINEs aumenta significativamente risco de hemorragia gastrointestinal. Usar protetor gástrico se necessário.'),
('Escitalopram', 'AINEs', 'farmacodinâmica', 'moderada', 'ISRS inibem agregação plaquetária. Associação com AINEs aumenta significativamente risco de hemorragia gastrointestinal. Usar protetor gástrico se necessário.'),
('Sertralina', 'Tramadol', 'farmacodinâmica', 'moderada', 'Risco de síndrome serotoninérgica e convulsões. Monitorar agitação, hipertermia, tremores, taquicardia. Usar com cautela e em menor dose.'),
('Escitalopram', 'Tramadol', 'farmacodinâmica', 'moderada', 'Risco de síndrome serotoninérgica e convulsões. Monitorar agitação, hipertermia, tremores, taquicardia. Usar com cautela e em menor dose.'),
('Ácido valproico', 'Carbamazepina', 'farmacocinética', 'moderada', 'Carbamazepina induz metabolismo do valproico, reduzindo seus níveis. Valproico inibe metabolismo de carbamazepina, elevando seu epóxido ativo (tóxico). Monitorar níveis séricos e sinais de toxicidade.'),
('Losartana', 'Ibuprofeno', 'farmacodinâmica', 'moderada', 'AINEs reduzem efeito anti-hipertensivo dos sartans e IECAs, além de aumentar risco de insuficiência renal aguda. Monitorar PA e função renal.'),
('Losartana', 'Naproxeno', 'farmacodinâmica', 'moderada', 'AINEs reduzem efeito anti-hipertensivo dos sartans e IECAs, além de aumentar risco de insuficiência renal aguda. Monitorar PA e função renal.'),
('Alopurinol', 'Azatioprina', 'farmacocinética', 'moderada', 'Alopurinol inibe xantina oxidase, aumentando toxicidade da azatioprina/mercaptopurina 3-4x. Risco de mielossupressão grave. Contraindicado ou reduzir dose da azatioprina em 75%.'),
('Alopurinol', 'Mercaptopurina', 'farmacocinética', 'moderada', 'Alopurinol inibe xantina oxidase, aumentando toxicidade da azatioprina/mercaptopurina 3-4x. Risco de mielossupressão grave. Contraindicado ou reduzir dose em 75%.'),
('Fluconazol', 'Fenitoína', 'farmacocinética', 'moderada', 'Fluconazol inibe CYP2C9, aumentando níveis de fenitoína. Risco de toxicidade (ataxia, nistagmo, sedação). Monitorar níveis séricos.'),
('Rifampicina', 'Varfarina', 'farmacocinética', 'moderada', 'Rifampicina é potente indutor de CYP2C9 e CYP3A4, reduzindo drasticamente efeito da varfarina. Risco de trombose. Monitorar RNI e aumentar dose de varfarina durante tratamento.'),
('Rifampicina', 'Efavirenz', 'farmacocinética', 'moderada', 'Rifampicina reduz drasticamente níveis plasmáticos de antirretrovirais por indução enzimática. Pode levar a falha terapêutica e resistência ao HIV. Discutir esquema alternativo com infectologista.'),
('Rifampicina', 'Lopinavir', 'farmacocinética', 'moderada', 'Rifampicina reduz drasticamente níveis plasmáticos de antirretrovirais por indução enzimática. Pode levar a falha terapêutica e resistência ao HIV. Discutir esquema alternativo com infectologista.'),
('Captopril', 'Espironolactona', 'farmacodinâmica', 'moderada', 'Associação de IECA com diurético poupador de potássio eleva risco de hipercalemia grave. Monitorar potássio sérico regularmente.'),
('Enalapril', 'Espironolactona', 'farmacodinâmica', 'moderada', 'Associação de IECA com diurético poupador de potássio eleva risco de hipercalemia grave. Monitorar potássio sérico regularmente.'),
('Captopril', 'Amilorida', 'farmacodinâmica', 'moderada', 'Associação de IECA com diurético poupador de potássio eleva risco de hipercalemia grave. Monitorar potássio sérico regularmente.'),
('Metoclopramida', 'Haloperidol', 'farmacodinâmica', 'moderada', 'Associação potencializa efeitos extrapiramidais (parkinsonismo, acatisia, discinesia). Evitar uso prolongado conjunto. Monitorar sinais extrapiramidais.'),
('Gentamicina', 'Furosemida', 'farmacodinâmica', 'moderada', 'Associação potencializa nefrotoxicidade e ototoxicidade dos aminoglicosídeos. Monitorar função renal, audição e níveis séricos de gentamicina.'),
('Vancomicina', 'Aminoglicosídeos', 'farmacodinâmica', 'moderada', 'Potencialização de nefrotoxicidade. Monitorar função renal diariamente. Evitar associação se possível.'),
('Clopidogrel', 'Omeprazol', 'farmacocinética', 'moderada', 'Omeprazol e esomeprazol inibem CYP2C19, reduzindo ativação do clopidogrel em até 45%. Preferir pantoprazol como protetor gástrico em pacientes em uso de clopidogrel.'),
('Clopidogrel', 'Esomeprazol', 'farmacocinética', 'moderada', 'Omeprazol e esomeprazol inibem CYP2C19, reduzindo ativação do clopidogrel em até 45%. Preferir pantoprazol como protetor gástrico em pacientes em uso de clopidogrel.'),
('Lamotrigina', 'Valproato de sódio', 'farmacocinética', 'moderada', 'Valproato inibe metabolismo da lamotrigina, dobrando seus níveis plasmáticos. Risco de toxicidade grave (síndrome de Stevens-Johnson). Reduzir dose de lamotrigina à metade ao iniciar valproato.'),
('Anlodipino', 'Eritromicina', 'farmacocinética', 'moderada', 'Macrolídeos inibem CYP3A4, aumentando níveis de bloqueadores de canal de cálcio. Risco de hipotensão grave. Monitorar pressão arterial.'),
('Anlodipino', 'Claritromicina', 'farmacocinética', 'moderada', 'Macrolídeos inibem CYP3A4, aumentando níveis de bloqueadores de canal de cálcio. Risco de hipotensão grave. Monitorar pressão arterial.'),
('Nifedipino', 'Eritromicina', 'farmacocinética', 'moderada', 'Macrolídeos inibem CYP3A4, aumentando níveis de bloqueadores de canal de cálcio. Risco de hipotensão grave. Monitorar pressão arterial.'),
('Nifedipino', 'Claritromicina', 'farmacocinética', 'moderada', 'Macrolídeos inibem CYP3A4, aumentando níveis de bloqueadores de canal de cálcio. Risco de hipotensão grave. Monitorar pressão arterial.');

-- =============================================================================
-- INTERAÇÕES BAIXO RISCO / INFORMATIVAS (continuação 91-100)
-- =============================================================================
INSERT INTO interacoes_globais (medicamento_a_nome, medicamento_b_nome, tipo_interacao, nivel_risco, recomendacao) VALUES
('Antiácidos', 'Antibióticos', 'farmacocinética', 'baixo', 'Antiácidos podem reduzir absorção de vários antibióticos. Regra geral: administrar antibiótico 1-2h antes ou 4-6h após o antiácido.'),
('Valeriana', 'Alprazolam', 'farmacodinâmica', 'baixo', 'Valeriana potencializa sedação de ansiolíticos. Risco de letargia e queda de pressão. Orientar paciente a evitar associação sem supervisão.'),
('Valeriana', 'Bromazepam', 'farmacodinâmica', 'baixo', 'Valeriana potencializa sedação de ansiolíticos. Risco de letargia e queda de pressão. Orientar paciente a evitar associação sem supervisão.'),
('Fenitoína', 'Anticoncepcionais orais', 'farmacocinética', 'baixo', 'Antiepilépticos indutores reduzem eficácia dos anticoncepcionais. Usar método contraceptivo adicional ou anticoncepcional com maior dose estrogênica sob orientação médica.'),
('Carbamazepina', 'Anticoncepcionais orais', 'farmacocinética', 'baixo', 'Antiepilépticos indutores reduzem eficácia dos anticoncepcionais. Usar método contraceptivo adicional ou anticoncepcional com maior dose estrogênica sob orientação médica.'),
('Insulina', 'Propranolol', 'farmacodinâmica', 'baixo', 'Betabloqueadores mascaram sinais de hipoglicemia (taquicardia). Pacientes diabéticos devem monitorar glicemia com maior frequência. Preferir betabloqueadores cardiosseletivos.'),
('Álcool', 'Paracetamol', 'farmacodinâmica', 'baixo', 'Uso ocasional de álcool com dose terapêutica de paracetamol tem risco baixo em pessoas saudáveis. Orientar não exceder dose máxima (4g/dia) e evitar uso em pacientes com hepatopatia.'),
('Ferro oral', 'Leite', 'farmacocinética', 'baixo', 'Cálcio e outros componentes do leite reduzem absorção do ferro. Administrar ferro em jejum ou 2h separado de laticínios e antiácidos.'),
('Ferro oral', 'Antiácidos', 'farmacocinética', 'baixo', 'Cálcio e antiácidos reduzem absorção do ferro. Administrar ferro em jejum ou 2h separado de laticínios e antiácidos.'),
('Levotiroxina', 'Antiácidos', 'farmacocinética', 'baixo', 'Antiácidos e cálcio reduzem absorção da levotiroxina. Administrar levotiroxina em jejum e aguardar pelo menos 4h antes de tomar antiácido ou cálcio.'),
('Levotiroxina', 'Carbonato de cálcio', 'farmacocinética', 'baixo', 'Antiácidos e cálcio reduzem absorção da levotiroxina. Administrar levotiroxina em jejum e aguardar pelo menos 4h antes de tomar antiácido ou cálcio.'),
('Cálcio', 'Ferro', 'farmacocinética', 'baixo', 'Cálcio e ferro competem pela absorção intestinal. Administrar em horários separados (manhã e tarde) para maximizar absorção de ambos.'),
('Digoxina', 'Claritromicina', 'farmacocinética', 'baixo', 'Claritromicina inibe P-glicoproteína e CYP3A4, aumentando biodisponibilidade da digoxina. Risco de intoxicação digitálica. Monitorar sinais de toxicidade e níveis séricos de digoxina.'),
('Carbamazepina', 'Doxiciclina', 'farmacocinética', 'baixo', 'Carbamazepina induz metabolismo da doxiciclina, reduzindo sua meia-vida à metade. Pode ser necessário dobrar a dose ou trocar o antibiótico.');
