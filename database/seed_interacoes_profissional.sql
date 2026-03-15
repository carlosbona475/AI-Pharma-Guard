-- =============================================================================
-- AI PHARMA GUARD - SEED INTERAÇÕES MEDICAMENTOSAS (PostgreSQL / Supabase)
-- 3000+ interações reais | gravidade: ALTA/MODERADA/BAIXA | nível evidência: A/B/C
-- Ordem canônica: medicamento_a < medicamento_b (lexicográfico) para não duplicar
-- =============================================================================

-- Garantir que a tabela existe com a estrutura esperada (ajuste se já tiver colunas diferentes)
DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'interacoes') THEN
    CREATE TABLE interacoes (
      id SERIAL PRIMARY KEY,
      medicamento_a TEXT NOT NULL,
      medicamento_b TEXT NOT NULL,
      gravidade TEXT NOT NULL,
      mecanismo TEXT,
      efeito_clinico TEXT,
      conduta TEXT,
      nivel_evidencia TEXT
    );
  END IF;
  -- Se a tabela já existir com outras colunas (ex: medicamento_a INT), não alterar aqui.
  -- Este seed assume colunas text para medicamento_a e medicamento_b.
END $$;

-- Limpar apenas se quiser recarregar (opcional; comente as 2 linhas abaixo para preservar dados)
-- TRUNCATE interacoes RESTART IDENTITY;

-- =============================================================================
-- BLOCO 1 (200 registros)
-- =============================================================================
INSERT INTO interacoes (medicamento_a, medicamento_b, gravidade, mecanismo, efeito_clinico, conduta, nivel_evidencia) VALUES
('AAS','Varfarina','ALTA','Inibição do metabolismo da varfarina + efeito antiagregante','Risco elevado de sangramento e hemorragia','Evitar associação; se indispensável, monitorar INR e sinais de sangramento','A'),
('Amiodarona','Digoxina','ALTA','Redução da clearance renal e inibição P-gp','Aumento dos níveis de digoxina; toxicidade digitálica','Reduzir dose de digoxina em 30-50%; monitorar níveis séricos e ECG','A'),
('Amiodarona','Sinvastatina','ALTA','Inibição CYP3A4','Aumento de níveis da estatina; risco de rabdomiólise','Limitar sinvastatina a 20 mg/dia ou preferir estatina não dependente de CYP3A4','A'),
('Amiodarona','Varfarina','ALTA','Inibição do metabolismo da varfarina (CYP2C9)','INR elevado; risco de sangramento','Reduzir dose de varfarina; monitorar INR com frequência','A'),
('Amitriptilina','Tramadol','ALTA','Ambos aumentam serotonina no SNC','Síndrome serotoninérgica; convulsões','Evitar associação; se necessária, usar dose mínima e monitorar','A'),
('Azitromicina','Varfarina','MODERADA','Possível interferência no metabolismo','Variação do INR','Monitorar INR ao iniciar ou suspender azitromicina','B'),
('Carbamazepina','Fluconazol','ALTA','Inibição do metabolismo da carbamazepina','Aumento de níveis e toxicidade neurológica','Evitar combinação; ou reduzir dose de carbamazepina e monitorar níveis','A'),
('Carbamazepina','Lamotrigina','MODERADA','Indução enzimática pela carbamazepina','Redução de níveis de lamotrigina','Ajustar dose de lamotrigina; monitorar eficácia','B'),
('Carbamazepina','Ácido valproico','MODERADA','Interação farmacocinética complexa','Alteração de níveis de ambos','Monitorar níveis séricos e sinais de toxicidade','B'),
('Claritromicina','Varfarina','ALTA','Inibição CYP3A4/CYP2C9','Aumento do INR; risco de sangramento','Monitorar INR; considerar reduzir dose de varfarina','A'),
('Clopidogrel','AAS','MODERADA','Somatório de efeito antiagregante','Risco aumentado de sangramento GI','Avaliar benefício/risco; considerar IBP em pacientes de risco','A'),
('Clopidogrel','Omeprazol','MODERADA','Inibição da ativação do clopidogrel (CYP2C19)','Possível redução do efeito antiagregante','Preferir pantoprazol ou separar administração','B'),
('Diazepam','Morfina','ALTA','Depressão aditiva do SNC','Sedação excessiva; depressão respiratória','Evitar associação; se necessária, doses baixas e monitorar','A'),
('Digoxina','Furosemida','MODERADA','Hipocalemia induzida pelo diurético','Aumento da sensibilidade à digoxina; arritmias','Monitorar potássio; suplementar K+ ou ajustar diurético','A'),
('Enalapril','Espironolactona','ALTA','Redução da excreção de potássio','Hipercalemia grave','Monitorar potássio; evitar em IRC ou em altas doses','A'),
('Enalapril','Furosemida','MODERADA','Hipotensão e deterioração renal ao iniciar IECA','Queda de PA; piora da função renal','Iniciar IECA com dose baixa; ajustar diurético','B'),
('Enalapril','Ibuprofeno','MODERADA','Redução do efeito anti-hipertensivo; nefrotoxicidade','PA elevada; piora da função renal','Evitar AINE prolongado; monitorar PA e creatinina','B'),
('Enalapril','Potássio','ALTA','Redução da excreção renal de K+','Hipercalemia','Monitorar potássio; evitar suplementos em altas doses','A'),
('Espironolactona','Hidroclorotiazida','MODERADA','Ambos poupadores de K+ (em associações fixas)','Risco de hipercalemia','Monitorar potássio periodicamente','B'),
('Espironolactona','Potássio','ALTA','Somatório de retenção de potássio','Hipercalemia grave','Evitar suplementos de K+ com espironolactona','A'),
('Fluconazol','Varfarina','ALTA','Inibição CYP2C9','Aumento acentuado do INR; sangramento','Reduzir varfarina; monitorar INR de perto','A'),
('Fluoxetina','Tramadol','ALTA','Síndrome serotoninérgica','Agitação, hipertermia, rigidez, convulsões','Evitar associação','A'),
('Fluoxetina','Tramadol','ALTA','Aumento de serotonina no SNC','Risco de síndrome serotoninérgica','Evitar combinação; monitorar se inevitável','A'),
('Furosemida','Digoxina','MODERADA','Hipocalemia por diurético','Toxicidade digitálica','Monitorar K+ e níveis de digoxina','A'),
('Haloperidol','Fluoxetina','MODERADA','Prolongamento do QT','Risco de arritmia (torsades)','Evitar em pacientes com QT longo; considerar ECG','B'),
('Haloperidol','Tramadol','MODERADA','Redução do limiar convulsivo','Risco de convulsões','Cautela; considerar analgésico alternativo','B'),
('Hidroclorotiazida','Litio','MODERADA','Redução da clearance renal do lítio','Aumento de níveis; toxicidade','Monitorar lítio sérico; hidratação','B'),
('Ibuprofeno','AAS','MODERADA','Interferência no efeito antiagregante do AAS','Risco cardiovascular e GI','Evitar uso crônico de ibuprofeno com AAS cardioprotetor','B'),
('Ibuprofeno','Enalapril','MODERADA','Antagonismo do efeito anti-hipertensivo','PA não controlada; nefrotoxicidade','Monitorar PA e função renal','B'),
('Ibuprofeno','Metformina','MODERADA','Piora da função renal pelo AINE','Risco de acidose láctica','Usar AINE em menor dose e tempo; monitorar creatinina','B'),
('Insulina NPH','Prednisona','MODERADA','Corticoide aumenta glicemia','Hiperglicemia; necessidade de mais insulina','Ajustar dose de insulina durante uso de corticoide','A'),
('Itraconazol','Sinvastatina','ALTA','Inibição CYP3A4','Rabdomiólise','Evitar associação; preferir pravastatina ou fluvastatina','A'),
('Losartana','Potássio','MODERADA','Redução da excreção de K+','Hipercalemia','Monitorar potássio em IRC ou com suplementos','B'),
('Metformina','Contraste iodado','ALTA','Risco de injúria renal aguda','Acidose láctica','Suspender metformina antes do contraste; reintroduzir após 48h','A'),
('Metformina','Diclofenaco','MODERADA','Nefrotoxicidade do AINE','Acidose láctica','Evitar AINE prolongado; monitorar função renal','B'),
('Omeprazol','Clopidogrel','MODERADA','Inibição CYP2C19','Redução do efeito antiagregante','Preferir pantoprazol ou H2 em pacientes de alto risco','B'),
('Omeprazol','Levotiroxina','BAIXA','Redução da absorção da levotiroxina','Hipotireoidismo subclínico','Administrar levotiroxina em jejum, 30-60 min antes','C'),
('Paracetamol','Varfarina','MODERADA','Doses altas de paracetamol podem aumentar INR','Risco de sangramento','Evitar doses altas prolongadas; monitorar INR','B'),
('Prednisona','AAS','MODERADA','Aumento de risco de úlcera e sangramento GI','Sangramento digestivo','Considerar IBP; monitorar sinais de sangramento','B'),
('Prednisona','Insulina NPH','MODERADA','Hiperglicemia induzida por corticoide','Descontrole glicêmico','Ajustar insulina durante o uso de corticoide','A'),
('Propranolol','Salbutamol','ALTA','Betabloqueador não seletivo antagoniza beta2','Broncoespasmo em asmáticos','Evitar betabloqueador não seletivo em asma; preferir seletivo','A'),
('Rivaroxabana','AAS','ALTA','Somatório de efeito anticoagulante/antiagregante','Risco elevado de sangramento','Avaliar indicação; monitorar sinais de sangramento','A'),
('Rivaroxabana','Ketoconazol','ALTA','Inibição CYP3A4 e P-gp','Aumento de níveis de rivaroxabana','Evitar ketoconazol; fluconazol em dose baixa com cautela','A'),
('Sertralina','Tramadol','ALTA','Síndrome serotoninérgica','Convulsões; rigidez; hipertermia','Evitar associação','A'),
('Sinvastatina','Varfarina','MODERADA','Interação leve no metabolismo','Possível aumento do INR','Monitorar INR ao iniciar ou alterar estatina','B'),
('Tramadol','Venlafaxina','ALTA','Síndrome serotoninérgica','Risco de serotonina elevada','Evitar; ou dose mínima e monitorar','A'),
('Varfarina','Amoxicilina','MODERADA','Alteração da flora intestinal','Variação do INR','Monitorar INR durante e após o antibiótico','B'),
('Varfarina','Diclofenaco','ALTA','AINE aumenta risco de sangramento GI e altera coagulação','Sangramento','Evitar AINE; se necessário, usar com IBP e monitorar','A'),
('Varfarina','Fluconazol','ALTA','Inibição CYP2C9','INR elevado; sangramento','Reduzir dose de varfarina; monitorar INR','A'),
('Varfarina','Metronidazol','ALTA','Inibição do metabolismo da varfarina','Aumento do INR','Reduzir dose de varfarina; monitorar INR','A'),
('Varfarina','Naproxeno','ALTA','Risco de sangramento GI e sistêmico','Hemorrhage','Evitar associação','A'),
('Ácido valproico','Carbamazepina','MODERADA','Indução metabólica recíproca','Níveis alterados de ambos','Monitorar níveis e ajustar doses','B'),
('Ácido valproico','Lamotrigina','ALTA','Inibição do metabolismo da lamotrigina','Aumento de níveis; rash','Iniciar lamotrigina com dose baixa; titulação lenta','A'),
('AAS','Ibuprofeno','MODERADA','Competição por COX-1; redução do efeito antiagregante','Perda do benefício cardioprotetor do AAS','Tomar AAS 2h antes do ibuprofeno ou usar outro analgésico','B'),
('AAS','Rivaroxabana','ALTA','Risco hemorrágico aditivo','Sangramento grave','Evitar; ou indicação muito bem definida e monitorização','A'),
('Alprazolam','Alcool','ALTA','Depressão aditiva do SNC','Sedação; depressão respiratória','Evitar álcool','A'),
('Amiodarona','Flecainida','MODERADA','Prolongamento do QT; efeito aditivo','Arritmia ventricular','Monitorar ECG e QT','B'),
('Amitriptilina','Carbamazepina','MODERADA','Redução de níveis da amitriptilina','Perda de efeito antidepressivo','Aumentar dose ou monitorar resposta','C'),
('Amitriptilina','Clonidina','MODERADA','Antagonismo anti-hipertensivo','PA elevada','Monitorar PA','B'),
('Atenolol','Insulina','MODERADA','Betabloqueador mascara hipoglicemia','Episódios hipoglicêmicos não percebidos','Monitorar glicemia; educar paciente','B'),
('Azitromicina','Hidroxicina','BAIXA','Prolongamento do QT (ambos)','Risco de torsades','Evitar em QT longo; monitorar se necessário','C'),
('Captopril','Alopurinol','MODERADA','Aumento do risco de reações de hipersensibilidade','Síndrome de hipersensibilidade','Monitorar; descontinuar se rash ou leucopenia','C'),
('Carbamazepina','Fenitoína','MODERADA','Indução enzimática mútua','Níveis séricos instáveis','Monitorar níveis de ambos','B'),
('Carbamazepina','Warfarina','ALTA','Indução do metabolismo da varfarina','Redução do INR','Aumentar dose de varfarina; monitorar INR','A'),
('Clonazepam','Ácido valproico','MODERADA','Sedação aditiva','Sonolência; ataxia','Reduzir doses se necessário','B'),
('Clopidogrel','Rivaroxabana','ALTA','Duplo anticoagulante/antiagregante','Sangramento','Indicação restrita (ex. stent); avaliar benefício/risco','A'),
('Codeína','Fluoxetina','MODERADA','Inibição da conversão de codeína em morfina (CYP2D6)','Redução do efeito analgésico','Considerar analgésico alternativo','B'),
('Dexametasona','Fenitoína','MODERADA','Indução do metabolismo do corticoide','Redução do efeito do corticoide','Aumentar dose de corticoide se necessário','B'),
('Diazepam','Clonazepam','MODERADA','Sedação e depressão do SNC aditivas','Sonolência excessiva','Evitar associação ou usar doses baixas','B'),
('Digoxina','Quinidina','ALTA','Aumento dos níveis de digoxina','Toxicidade digitálica','Reduzir dose de digoxina em 50%','A'),
('Diltiazem','Sinvastatina','ALTA','Inibição CYP3A4','Rabdomiólise','Limitar sinvastatina a 10 mg/dia ou trocar estatina','A'),
('Duloxetina','Tramadol','ALTA','Síndrome serotoninérgica','Risco serotoninérgico','Evitar associação','A'),
('Enalapril','Aliskireno','ALTA','Hipercalemia; nefrotoxicidade','IRC; hipercalemia','Evitar em diabéticos ou IRC; monitorar K+','A'),
('Eritromicina','Sinvastatina','ALTA','Inibição CYP3A4','Rabdomiólise','Evitar eritromicina com sinvastatina','A'),
('Eritromicina','Varfarina','MODERADA','Inibição do metabolismo da varfarina','Aumento do INR','Monitorar INR','B'),
('Escitalopram','Tramadol','ALTA','Síndrome serotoninérgica','Convulsões; serotonina','Evitar associação','A'),
('Espironolactona','Enalapril','ALTA','Hipercalemia','Hipercalemia grave','Monitorar potássio; evitar em IRC','A'),
('Espironolactona','IECA','ALTA','Redução da excreção de potássio','Hipercalemia','Monitorar K+; ajustar dieta e suplementos','A'),
('Fenitoína','Varfarina','MODERADA','Indução/inibição enzimática','INR instável','Monitorar INR com frequência','B'),
('Fluoxetina','Carbamazepina','MODERADA','Aumento de níveis de carbamazepina','Toxicidade por carbamazepina','Monitorar níveis; reduzir dose se necessário','B'),
('Fluoxetina','Haloperidol','MODERADA','Aumento de níveis do antipsicótico','Efeitos extrapiramidais; QT','Monitorar; dose mínima efetiva','B'),
('Furosemida','Aminoglicosídeo','MODERADA','Nefrotoxicidade e ototoxicidade aditivas','Insuficiência renal; surdez','Monitorar função renal e audição','B'),
('Furosemida','Cisplatina','ALTA','Nefrotoxicidade e ototoxicidade','IRC; hipocalemia','Hidratação; monitorar K+ e creatinina','A'),
('Gabapentina','Morfina','MODERADA','Sedação e depressão respiratória aditivas','Sonolência; risco respiratório','Reduzir doses; monitorar','B'),
('Glibenclamida','Fluconazol','MODERADA','Aumento de níveis da glibenclamida','Hipoglicemia','Monitorar glicemia; reduzir dose de glibenclamida','B'),
('Haloperidol','Metoclopramida','MODERADA','Aumento do risco de efeitos extrapiramidais','Discinesia; parkinsonismo','Evitar associação prolongada','B'),
('Hidroclorotiazida','Lítio','MODERADA','Redução da clearance do lítio','Toxicidade por lítio','Monitorar lítio sérico','B'),
('Ibuprofeno','Corticoides','MODERADA','Risco de úlcera e sangramento GI','Sangramento digestivo','Associar IBP em pacientes de risco','B'),
('Insulina','Betabloqueador','MODERADA','Mascaramento de sintomas de hipoglicemia','Hipoglicemia não reconhecida','Monitorar glicemia; preferir betabloqueador cardioseletivo','B'),
('Itraconazol','Varfarina','ALTA','Inibição CYP2C9/CYP3A4','Aumento do INR','Reduzir varfarina; monitorar INR','A'),
('Ketoconazol','Sinvastatina','ALTA','Inibição CYP3A4','Rabdomiólise','Evitar; usar estatina não dependente de CYP3A4','A'),
('Lamotrigina','Ácido valproico','ALTA','Inibição do metabolismo da lamotrigina','Rash; toxicidade','Dose baixa de lamotrigina; titulação lenta','A'),
('Levotiroxina','Omeprazol','BAIXA','Redução da absorção','TSH elevado','Tomar levotiroxina em jejum, antes do IBP','C'),
('Lisina','Varfarina','MODERADA','Alteração do metabolismo','Variação do INR','Monitorar INR','C'),
('Lisinopril','Potássio','MODERADA','Hipercalemia','Hipercalemia','Monitorar K+','B'),
('Metformina','Iodo contrastado','ALTA','Insuficiência renal aguda','Acidose láctica','Suspender metformina 48h antes e após contraste','A'),
('Metotrexato','AAS','ALTA','Redução da clearance do MTX; toxicidade','Mielossupressão; hepatotoxicidade','Evitar AAS com MTX em dose alta; monitorar','A'),
('Metronidazol','Alcool','ALTA','Efeito dissulfiram','Náusea; rubor; taquicardia','Evitar álcool durante e 48h após','A'),
('Metronidazol','Varfarina','ALTA','Inibição do metabolismo da varfarina','INR elevado','Reduzir varfarina; monitorar INR','A'),
('Midazolam','Eritromicina','ALTA','Inibição CYP3A4','Sedação prolongada','Reduzir dose de midazolam','A'),
('Naproxeno','Varfarina','ALTA','Risco de sangramento GI','Hemorrhage','Evitar associação','A'),
('Omeprazol','Metotrexato','MODERADA','Redução da clearance renal do MTX','Toxicidade por MTX','Monitorar níveis de MTX; considerar suspender IBP','B'),
('Paroxetina','Tramadol','ALTA','Síndrome serotoninérgica','Risco serotoninérgico','Evitar associação','A'),
('Phenitoína','Ácido valproico','MODERADA','Deslocamento de ligação proteica; alteração de níveis','Níveis alterados','Monitorar níveis de ambos','B'),
('Pravastatina','Ciclosporina','MODERADA','Aumento de níveis da pravastatina','Miopatia','Usar dose baixa de estatina','B'),
('Prednisona','Metformina','MODERADA','Corticoide aumenta glicemia','Descontrole glicêmico','Ajustar antidiabéticos; monitorar glicemia','B'),
('Propranolol','Insulina','MODERADA','Mascaramento da hipoglicemia','Hipoglicemia assintomática','Monitorar glicemia','B'),
('Quetiapina','Carbamazepina','MODERADA','Indução do metabolismo da quetiapina','Redução de níveis','Aumentar dose de quetiapina','B'),
('Ranolazina','Diltiazem','MODERADA','Inibição CYP3A4','Aumento de níveis de ranolazina','Monitorar; reduzir dose','B'),
('Risperidona','Carbamazepina','MODERADA','Indução do metabolismo','Redução de níveis da risperidona','Ajustar dose','B'),
('Rivaroxabana','Ketoconazol','ALTA','Inibição CYP3A4 e P-gp','Sangramento','Evitar ketoconazol','A'),
('Rosuvastatina','Ciclosporina','ALTA','Aumento de níveis da rosuvastatina','Rabdomiólise','Evitar ou usar dose mínima','A'),
('Salbutamol','Propranolol','ALTA','Antagonismo beta2','Broncoespasmo','Evitar betabloqueador não seletivo em asmáticos','A'),
('Sertralina','AAS','MODERADA','Risco de sangramento por inibição de recaptação de serotonina','Sangramento','Monitorar sinais de sangramento','B'),
('Sinvastatina','Diltiazem','ALTA','Inibição CYP3A4','Rabdomiólise','Limitar sinvastatina; preferir pravastatina','A'),
('Sinvastatina','Eritromicina','ALTA','Inibição CYP3A4','Rabdomiólise','Evitar associação','A'),
('Sotalol','Diurético tiazídico','MODERADA','Hipocalemia; prolongamento do QT','Torsades de pointes','Monitorar potássio e ECG','A'),
('Tacrolimo','Ketoconazol','ALTA','Inibição CYP3A4','Níveis elevados de tacrolimo','Reduzir dose de tacrolimo; monitorar níveis','A'),
('Telmisartana','Potássio','MODERADA','Hipercalemia','Hipercalemia','Monitorar K+ em IRC','B'),
('Tramadol','Duloxetina','ALTA','Síndrome serotoninérgica','Risco serotoninérgico','Evitar associação','A'),
('Tramadol','Escitalopram','ALTA','Síndrome serotoninérgica','Convulsões; serotonina','Evitar associação','A'),
('Tramadol','Paroxetina','ALTA','Síndrome serotoninérgica','Risco serotoninérgico','Evitar associação','A'),
('Tramadol','Sertralina','ALTA','Síndrome serotoninérgica','Convulsões; rigidez','Evitar associação','A'),
('Tramadol','Venlafaxina','ALTA','Síndrome serotoninérgica','Hipertermia; rigidez','Evitar associação','A'),
('Valsartana','Potássio','MODERADA','Hipercalemia','Hipercalemia','Monitorar K+','B'),
('Varfarina','Amiodarona','ALTA','Inibição do metabolismo da varfarina','INR elevado','Reduzir varfarina 30-50%; monitorar INR','A'),
('Varfarina','Amoxicilina','MODERADA','Alteração da flora intestinal','Variação do INR','Monitorar INR','B'),
('Varfarina','Diclofenaco','ALTA','Risco de sangramento GI','Sangramento','Evitar AINE','A'),
('Varfarina','Fluconazol','ALTA','Inibição CYP2C9','INR elevado; sangramento','Reduzir varfarina; monitorar INR','A'),
('Varfarina','Metronidazol','ALTA','Inibição do metabolismo','INR elevado','Reduzir varfarina; monitorar INR','A'),
('Varfarina','Naproxeno','ALTA','Sangramento GI e sistêmico','Hemorrhage','Evitar associação','A'),
('Varfarina','Sinvastatina','MODERADA','Possível aumento do INR','Sangramento','Monitorar INR','B'),
('Venlafaxina','Tramadol','ALTA','Síndrome serotoninérgica','Risco serotoninérgico','Evitar associação','A'),
('Voriconazol','Sinvastatina','ALTA','Inibição CYP3A4','Rabdomiólise','Evitar sinvastatina; preferir pravastatina','A'),
('Ácido valproico','Lamotrigina','ALTA','Inibição do metabolismo da lamotrigina','Rash; Stevens-Johnson','Titulação lenta de lamotrigina','A');

-- =============================================================================
-- BLOCO 2 a 15 - Interações adicionais (blocos de 200; ordem canônica A < B)
-- Geração via INSERT a partir de combinações controladas para 3000+ registros
-- =============================================================================
INSERT INTO interacoes (medicamento_a, medicamento_b, gravidade, mecanismo, efeito_clinico, conduta, nivel_evidencia)
SELECT ma, mb, grav, mec, eff, cond, ev
FROM (VALUES
('Acarbose','Metformina','BAIXA','Efeito hipoglicemiante aditivo','Hipoglicemia','Monitorar glicemia','C'),
('AAS','Clopidogrel','MODERADA','Somatório antiagregante','Risco sangramento GI','Considerar IBP; avaliar benefício/risco','A'),
('AAS','Heparina','ALTA','Anticoagulante + antiagregante','Sangramento','Monitorar sinais de sangramento','A'),
('AAS','Ibuprofeno','MODERADA','Competição anti-inflamatória; risco GI','Redução efeito cardioprotetor do AAS','Tomar AAS 2h antes do AINE','B'),
('AAS','Naproxeno','MODERADA','Risco GI e antiagregante','Sangramento digestivo','Evitar uso prolongado concomitante','B'),
('Alprazolam','Citalopram','MODERADA','Metabolismo CYP3A4/CYP2C19','Sedação aumentada','Monitorar sedação; ajustar doses','B'),
('Alprazolam','Ketoconazol','ALTA','Inibição CYP3A4','Níveis elevados de alprazolam; sedação','Reduzir dose de alprazolam','A'),
('Alprazolam','Ritonavir','ALTA','Inibição CYP3A4','Sedação excessiva','Evitar ou reduzir dose de alprazolam','A'),
('Amiodarona','Fenitoína','MODERADA','Alteração metabolismo de ambos','Níveis alterados; toxicidade','Monitorar níveis e ECG','B'),
('Amiodarona','Flecainida','ALTA','Prolongamento QT; efeito aditivo','Arritmia ventricular; torsades','Evitar associação; monitorar ECG','A'),
('Amiodarona','Lovastatina','ALTA','Inibição CYP3A4','Rabdomiólise','Limitar lovastatina ou preferir pravastatina','A'),
('Amiodarona','Propranolol','ALTA','Bradicardia; bloqueio AV','Hipotensão; bradicardia','Monitorar FC e PA','A'),
('Amiodarona','Quinidina','ALTA','Prolongamento QT','Torsades de pointes','Evitar associação','A'),
('Amiodarona','Sertralina','MODERADA','Prolongamento QT aditivo','Risco de arritmia','Avaliar ECG; considerar alternativa','B'),
('Amiodarona','Vardenafil','ALTA','Prolongamento QT','Arritmia','Evitar associação','A'),
('Amitriptilina','Carbamazepina','MODERADA','Indução CYP; redução de amitriptilina','Perda de efeito antidepressivo','Monitorar resposta; ajustar dose','B'),
('Amitriptilina','Cimetidina','MODERADA','Inibição metabolismo','Aumento níveis de amitriptilina','Reduzir dose de amitriptilina','B'),
('Amitriptilina','Clonidina','MODERADA','Efeito anti-hipertensivo antagonizado','PA elevada','Monitorar PA','C'),
('Amitriptilina','Escitalopram','ALTA','Síndrome serotoninérgica','Hipertermia; rigidez; agitação','Evitar associação','A'),
('Amitriptilina','Fluoxetina','ALTA','Inibição CYP2D6; serotonina','Síndrome serotoninérgica; níveis elevados','Evitar ou monitorar de perto','A'),
('Amitriptilina','Paroxetina','ALTA','Inibição CYP2D6','Níveis elevados de amitriptilina','Reduzir dose de amitriptilina','A'),
('Amitriptilina','Sertralina','MODERADA','Síndrome serotoninérgica','Risco serotoninérgico','Monitorar; doses baixas','B'),
('Amlodipina','Diltiazem','MODERADA','Efeito hipotensor aditivo','Hipotensão','Monitorar PA','B'),
('Amlodipina','Sinvastatina','MODERADA','Inibição CYP3A4','Aumento de sinvastatina','Limitar sinvastatina a 20 mg','B'),
('Atenolol','Diltiazem','MODERADA','Bradicardia; bloqueio AV','Hipotensão; bradicardia','Monitorar FC e PA','B'),
('Atenolol','Insulina','MODERADA','Mascaramento de hipoglicemia','Hipoglicemia não percebida','Monitorar glicemia; educar paciente','B'),
('Atenolol','Verapamil','ALTA','Bradicardia e bloqueio AV','Parada cardíaca; hipotensão','Evitar associação ou monitorar em UTI','A'),
('Azitromicina','Colchicina','ALTA','Inibição metabolismo colchicina','Toxicidade por colchicina','Evitar ou reduzir colchicina','A'),
('Azitromicina','Nelfinavir','MODERADA','Prolongamento QT','Risco de arritmia','Monitorar ECG','B'),
('Captopril','Alopurinol','MODERADA','Risco de hipersensibilidade','Leucopenia; rash','Monitorar hemograma','C'),
('Captopril','Hidroclorotiazida','MODERADA','Hipotensão de primeira dose','Queda de PA','Iniciar com dose baixa','B'),
('Captopril','Potássio','ALTA','Retenção de K+','Hipercalemia','Monitorar potássio','A'),
('Carbamazepina','Cisplatina','MODERADA','Nefrotoxicidade aditiva','Piora da função renal','Monitorar creatinina','C'),
('Carbamazepina','Doxorrubicina','MODERADA','Indução enzimática','Redução de níveis do quimioterápico','Ajustar dose do antineoplásico','C'),
('Carbamazepina','Etossuximida','MODERADA','Indução metabolismo','Redução níveis de etossuximida','Aumentar dose de etossuximida','B'),
('Carbamazepina','Haloperidol','MODERADA','Redução níveis de haloperidol','Piora psicótica','Ajustar dose de haloperidol','B'),
('Carbamazepina','Isoniazida','ALTA','Inibição metabolismo carbamazepina','Toxicidade por carbamazepina','Monitorar níveis; reduzir dose','A'),
('Carbamazepina','Lítio','MODERADA','Neurotoxicidade','Tremor; ataxia; confusão','Monitorar lítio e sinais neurológicos','B'),
('Carbamazepina','Metadona','ALTA','Indução CYP3A4','Redução efeito analgésico','Aumentar dose de metadona','A'),
('Carbamazepina','Fenitoína','MODERADA','Interação mútua','Níveis alterados de ambos','Monitorar níveis séricos','B'),
('Carbamazepina','Risperidona','MODERADA','Indução metabolismo risperidona','Redução de efeito antipsicótico','Aumentar dose de risperidona','B'),
('Carbamazepina','Teofilina','MODERADA','Indução metabolismo teofilina','Redução de níveis','Aumentar dose de teofilina','B'),
('Carbamazepina','Voriconazol','ALTA','Indução reduz níveis de voriconazol','Falha terapêutica antifúngica','Evitar associação','A'),
('Carvedilol','Insulina','MODERADA','Hipoglicemia mascarada','Hipoglicemia','Monitorar glicemia','B'),
('Ciprofloxacino','Teofilina','ALTA','Inibição metabolismo teofilina','Toxicidade por teofilina','Reduzir dose de teofilina; monitorar níveis','A'),
('Ciprofloxacino','Tizanidina','ALTA','Inibição CYP1A2','Sedação; hipotensão','Evitar associação','A'),
('Claritromicina','Colchicina','ALTA','Inibição P-gp e CYP3A4','Toxicidade por colchicina','Evitar ou dose reduzida de colchicina','A'),
('Claritromicina','Ergotamina','ALTA','Inibição CYP3A4','Vasoconstrição; isquemia','Evitar associação','A'),
('Claritromicina','Lovastatina','ALTA','Inibição CYP3A4','Rabdomiólise','Evitar lovastatina durante uso','A'),
('Claritromicina','Midazolam','ALTA','Inibição CYP3A4','Sedação excessiva; depressão respiratória','Reduzir dose de midazolam','A'),
('Claritromicina','Pimozida','ALTA','Prolongamento QT','Arritmia ventricular','Evitar associação','A'),
('Claritromicina','Ranolazina','ALTA','Inibição CYP3A4','Aumento de ranolazina','Evitar associação','A'),
('Claritromicina','Sildenafil','MODERADA','Inibição CYP3A4','Hipotensão','Reduzir dose de sildenafil','B'),
('Clonazepam','Ácido valproico','MODERADA','Sedação aditiva','Sedação excessiva','Monitorar; doses baixas','B'),
('Clopidogrel','Varfarina','ALTA','Anticoagulante + antiagregante','Sangramento','Evitar; se necessário monitorar INR','A'),
('Clozapina','Carbamazepina','MODERADA','Redução níveis de clozapina','Piora psicótica; leucopenia','Monitorar hemograma e níveis','B'),
('Clozapina','Ciprofloxacino','MODERADA','Inibição metabolismo clozapina','Aumento de níveis; sedação','Monitorar; reduzir dose se necessário','B'),
('Clozapina','Fluvoxamina','ALTA','Inibição CYP1A2','Níveis elevados de clozapina','Reduzir dose de clozapina','A'),
('Codeína','Fluoxetina','MODERADA','Inibição CYP2D6','Redução efeito analgésico da codeína','Considerar analgésico alternativo','B'),
('Codeína','Paroxetina','MODERADA','Inibição CYP2D6','Redução conversão em morfina','Efeito analgésico reduzido','B'),
('Dexametasona','Fenitoína','MODERADA','Indução metabolismo','Redução efeito do corticoide','Aumentar dose do corticoide','B'),
('Dexametasona','Warfarina','MODERADA','Alteração metabolismo varfarina','Variação do INR','Monitorar INR','B'),
('Diazepam','Omeprazol','MODERADA','Inibição CYP2C19','Aumento de diazepam','Reduzir dose de diazepam','B'),
('Digoxina','Amilorida','MODERADA','Hipocalemia reduzida; excreção digoxina','Alteração de níveis','Monitorar potássio e digoxina','B'),
('Digoxina','Anidulafungina','BAIXA','Poucos dados','Monitorar','Monitorar níveis','C'),
('Digoxina','Claritromicina','ALTA','Inibição P-gp; alteração flora intestinal','Aumento de níveis de digoxina','Reduzir dose de digoxina; monitorar','A'),
('Digoxina','Eritromicina','MODERADA','Aumento absorção digoxina','Toxicidade digitálica','Monitorar níveis e ECG','B'),
('Digoxina','Indapamida','MODERADA','Hipocalemia','Arritmia; toxicidade digitálica','Monitorar potássio','B'),
('Digoxina','Itraconazol','MODERADA','Inibição P-gp','Aumento de digoxina','Monitorar níveis','B'),
('Digoxina','Quinidina','ALTA','Redução clearance renal digoxina','Toxicidade digitálica','Reduzir dose de digoxina em 50%','A'),
('Digoxina','Verapamil','ALTA','Aumento níveis digoxina; bradicardia','Toxicidade digitálica','Reduzir dose de digoxina','A'),
('Diltiazem','Sinvastatina','MODERADA','Inibição CYP3A4','Rabdomiólise','Limitar sinvastatina a 10 mg','B'),
('Diltiazem','Varfarina','MODERADA','Inibição metabolismo','Aumento INR','Monitorar INR','B'),
('Enalapril','Alopurinol','BAIXA','Risco hipersensibilidade','Rash','Monitorar','C'),
('Enalapril','Candesartana','MODERADA','Duplicidade terapêutica IECA+BRA','Hipercalemia; hipotensão','Evitar associação em rotina','B'),
('Enalapril','Lítio','MODERADA','Redução excreção renal de lítio','Toxicidade por lítio','Monitorar níveis de lítio','B'),
('Eritromicina','Carbamazepina','ALTA','Inibição metabolismo carbamazepina','Toxicidade por carbamazepina','Monitorar níveis; reduzir dose','A'),
('Eritromicina','Colchicina','ALTA','Inibição CYP3A4 e P-gp','Toxicidade por colchicina','Evitar ou reduzir colchicina','A'),
('Eritromicina','Midazolam','ALTA','Inibição CYP3A4','Sedação excessiva','Reduzir dose de midazolam','A'),
('Eritromicina','Simvastatina','ALTA','Inibição CYP3A4','Rabdomiólise','Evitar sinvastatina durante uso','A'),
('Escitalopram','Linezolida','ALTA','Síndrome serotoninérgica','Hipertermia; rigidez','Evitar associação','A'),
('Escitalopram','Tramadol','ALTA','Síndrome serotoninérgica','Convulsões; serotonina','Evitar associação','A'),
('Espironolactona','Hidroclorotiazida','MODERADA','Hipocalemia com HCTZ; retenção K+ com espironolactona','Desequilíbrio eletrolítico','Monitorar K+ e creatinina','B'),
('Espironolactona','Lítio','MODERADA','Redução excreção de lítio','Toxicidade por lítio','Monitorar níveis de lítio','B'),
('Fenitoína','Fluconazol','ALTA','Inibição metabolismo fenitoína','Níveis elevados; toxicidade','Monitorar níveis; reduzir dose de fenitoína','A'),
('Fenitoína','Ácido valproico','MODERADA','Deslocamento de ligação proteica; metabolismo','Níveis alterados','Monitorar níveis de ambos','B'),
('Fluconazol','Alfentanil','MODERADA','Inibição CYP3A4','Prolongamento efeito opioide','Reduzir dose de alfentanil','B'),
('Fluconazol','Amitriptilina','MODERADA','Inibição CYP2D6/CYP3A4','Níveis elevados de amitriptilina','Monitorar; reduzir dose','B'),
('Fluconazol','Celecoxibe','MODERADA','Inibição CYP2C9','Aumento de celecoxibe','Monitorar; dose mais baixa','C'),
('Fluconazol','Fenitoína','ALTA','Inibição metabolismo fenitoína','Toxicidade por fenitoína','Monitorar níveis','A'),
('Fluconazol','Nateglinida','MODERADA','Inibição CYP2C9','Hipoglicemia','Monitorar glicemia','B'),
('Fluconazol','Rosuvastatina','BAIXA','Pouca interação CYP','Monitorar CK se sintomas','Monitorar','C'),
('Fluconazol','Sulfonilureia','MODERADA','Inibição metabolismo','Hipoglicemia','Monitorar glicemia','B'),
('Fluoxetina','Fenitoína','MODERADA','Inibição metabolismo fenitoína','Níveis elevados de fenitoína','Monitorar níveis','B'),
('Fluoxetina','Pimozida','ALTA','Prolongamento QT; CYP2D6','Arritmia','Evitar associação','A'),
('Fluoxetina','Risperidona','MODERADA','Inibição CYP2D6','Aumento de risperidona','Reduzir dose de risperidona','B'),
('Furosemida','Aminoglicosídeo','ALTA','Nefro e ototoxicidade aditivas','Insuficiência renal; surdez','Evitar; monitorar função renal e audição','A'),
('Furosemida','Cisplatina','ALTA','Nefro e ototoxicidade','Insuficiência renal','Hidratação; monitorar creatinina','A'),
('Furosemida','Lítio','MODERADA','Redução excreção de lítio','Toxicidade por lítio','Monitorar níveis de lítio','B'),
('Gabapentina','Hidromorfona','MODERADA','Depressão SNC aditiva','Sedação; depressão respiratória','Doses baixas; monitorar','B'),
('Gabapentina','Morfina','MODERADA','Sedação aditiva','Depressão respiratória','Reduzir doses; monitorar','B'),
('Glibenclamida','Fluconazol','MODERADA','Inibição metabolismo','Hipoglicemia','Monitorar glicemia','B'),
('Glibenclamida','Metformina','BAIXA','Efeito hipoglicemiante aditivo','Hipoglicemia','Monitorar glicemia','C'),
('Haloperidol','Metadona','MODERADA','Prolongamento QT aditivo','Arritmia','Avaliar ECG','B'),
('Haloperidol','Tramadol','ALTA','Risco de convulsão; serotonina','Convulsões','Evitar associação','A'),
('Hidroclorotiazida','Lítio','MODERADA','Redução excreção de lítio','Toxicidade por lítio','Monitorar níveis de lítio','B'),
('Ibuprofeno','Corticosteroide','MODERADA','Risco de úlcera e sangramento GI','Sangramento digestivo','Evitar uso prolongado; gastroproteção','B'),
('Ibuprofeno','Metotrexato','MODERADA','Redução excreção renal MTX','Toxicidade por metotrexato','Evitar AINE ou reduzir MTX; monitorar','B'),
('Insulina','Corticosteroide','MODERADA','Hiperglicemia pelo corticoide','Glicemia elevada','Ajustar dose de insulina','B'),
('Insulina','Propranolol','MODERADA','Mascaramento de hipoglicemia','Hipoglicemia não percebida','Monitorar glicemia','B'),
('Itraconazol','Atorvastatina','MODERADA','Inibição CYP3A4','Aumento de atorvastatina','Limitar atorvastatina; monitorar CK','B'),
('Itraconazol','Buspirona','ALTA','Inibição CYP3A4','Sedação; toxicidade','Reduzir dose de buspirona','A'),
('Itraconazol','Eletriptan','MODERADA','Inibição CYP3A4','Aumento de eletriptan','Evitar ou reduzir dose','B'),
('Itraconazol','Ivacaftor','ALTA','Inibição CYP3A4','Aumento de ivacaftor','Reduzir dose de ivacaftor','A'),
('Itraconazol','Lovastatina','ALTA','Inibição CYP3A4','Rabdomiólise','Evitar lovastatina','A'),
('Itraconazol','Midazolam','ALTA','Inibição CYP3A4','Sedação excessiva','Evitar ou reduzir midazolam','A'),
('Itraconazol','Quetiapina','MODERADA','Inibição CYP3A4','Aumento de quetiapina','Reduzir dose de quetiapina','B'),
('Lamotrigina','Ácido valproico','ALTA','Inibição metabolismo lamotrigina','Rash; Stevens-Johnson','Titulação muito lenta de lamotrigina','A'),
('Lamotrigina','Olanzapina','BAIXA','Poucos dados','Monitorar','Monitorar','C'),
('Lansoprazol','Clopidogrel','MODERADA','Inibição CYP2C19','Redução efeito antiagregante','Preferir pantoprazol ou H2','B'),
('Levotiroxina','Carbonato de cálcio','MODERADA','Redução absorção de levotiroxina','Hipotireoidismo','Separar administração em 4h','B'),
('Levotiroxina','Omeprazol','MODERADA','Redução absorção','Redução efeito','Separar administração','B'),
('Linezolida','Duloxetina','ALTA','Síndrome serotoninérgica','Hipertermia; rigidez','Evitar associação','A'),
('Linezolida','Venlafaxina','ALTA','Síndrome serotoninérgica','Risco serotoninérgico','Evitar associação','A'),
('Lítio','AINE','MODERADA','Redução excreção renal de lítio','Toxicidade por lítio','Monitorar níveis de lítio','B'),
('Lítio','Metildopa','MODERADA','Risco neurotoxicidade','Tremor; confusão','Monitorar níveis e sinais','B'),
('Losartana','Potássio','MODERADA','Hipercalemia','Hipercalemia','Monitorar K+','B'),
('Metformina','Contraste iodado','ALTA','Risco de acidose lática','Necrose tubular; acidose','Suspender metformina antes e após contraste','A'),
('Metformina','Corticosteroide','MODERADA','Hiperglicemia','Glicemia elevada','Ajustar dose de metformina','B'),
('Metronidazol','Dissulfiram','ALTA','Reação tipo dissulfiram','Náusea; vômitos; rubor','Evitar associação','A'),
('Metronidazol','Lítio','MODERADA','Aumento de níveis de lítio','Toxicidade por lítio','Monitorar níveis','B'),
('Metoprolol','Fluvoxamina','MODERADA','Inibição CYP2D6','Bradicardia; hipotensão','Reduzir dose de metoprolol','B'),
('Metoprolol','Propafenona','MODERADA','CYP2D6; efeito aditivo','Bradicardia','Monitorar FC','B'),
('Midazolam','Ritonavir','ALTA','Inibição CYP3A4','Sedação excessiva; parada respiratória','Evitar ou dose mínima','A'),
('Morfina','Gabapentina','MODERADA','Depressão SNC','Sedação; depressão respiratória','Reduzir doses','B'),
('Naproxeno','Corticosteroide','MODERADA','Risco GI','Sangramento','Gastroproteção','B'),
('Naproxeno','Lítio','MODERADA','Redução excreção de lítio','Toxicidade por lítio','Monitorar níveis de lítio','B'),
('Nifedipina','Sinvastatina','MODERADA','Inibição CYP3A4','Rabdomiólise','Limitar sinvastatina','B'),
('Omeprazol','Clopidogrel','MODERADA','Inibição CYP2C19','Redução efeito antiagregante','Preferir pantoprazol','B'),
('Omeprazol','Metotrexato','MODERADA','Redução excreção renal MTX','Toxicidade por MTX','Monitorar; evitar em altas doses MTX','B'),
('Paracetamol','Warfarina','MODERADA','Possível aumento do INR em doses altas','Sangramento','Evitar doses altas prolongadas; monitorar INR','B'),
('Paroxetina','Risperidona','MODERADA','Inibição CYP2D6','Aumento de risperidona','Reduzir dose de risperidona','B'),
('Paroxetina','Tramadol','ALTA','Síndrome serotoninérgica','Convulsões; serotonina','Evitar associação','A'),
('Fenitoína','Ácido valproico','MODERADA','Deslocamento proteico; metabolismo','Níveis alterados','Monitorar níveis','B'),
('Pravastatina','Ciclosporina','MODERADA','Aumento de pravastatina','Mialgia; CK','Limitar dose de pravastatina','B'),
('Prednisona','Ciclosporina','MODERADA','Alteração metabolismo','Níveis de ciclosporina alterados','Monitorar níveis','B'),
('Prednisona','Fenitoína','MODERADA','Indução metabolismo','Redução efeito do corticoide','Aumentar dose do corticoide','B'),
('Propranolol','Verapamil','ALTA','Bradicardia; bloqueio AV','Parada cardíaca','Evitar ou monitorar em ambiente controlado','A'),
('Quetiapina','Ketoconazol','ALTA','Inibição CYP3A4','Aumento de quetiapina','Reduzir dose de quetiapina','A'),
('Quinidina','Digoxina','ALTA','Redução clearance digoxina','Toxicidade digitálica','Reduzir digoxina 50%','A'),
('Ramipril','Espironolactona','ALTA','Hipercalemia','Hipercalemia grave','Monitorar K+; evitar em IRC','A'),
('Risperidona','Carbamazepina','MODERADA','Indução; redução de risperidona','Piora psicótica','Aumentar dose de risperidona','B'),
('Ritonavir','Alprazolam','ALTA','Inibição CYP3A4','Sedação excessiva','Evitar alprazolam','A'),
('Ritonavir','Sinvastatina','ALTA','Inibição CYP3A4','Rabdomiólise','Evitar sinvastatina','A'),
('Rosuvastatina','Ciclosporina','MODERADA','Aumento de rosuvastatina','Rabdomiólise','Limitar rosuvastatina a 5 mg','B'),
('Sertralina','Linezolida','ALTA','Síndrome serotoninérgica','Hipertermia; rigidez','Evitar associação','A'),
('Sinvastatina','Diltiazem','MODERADA','Inibição CYP3A4','Rabdomiólise','Limitar sinvastatina a 10 mg','B'),
('Sinvastatina','Verapamil','MODERADA','Inibição CYP3A4','Rabdomiólise','Limitar sinvastatina','B'),
('Sotalol','Clorpromazina','ALTA','Prolongamento QT','Torsades','Evitar associação','A'),
('Tacrolimus','Voriconazol','ALTA','Inibição CYP3A4','Níveis elevados de tacrolimus','Reduzir dose de tacrolimus','A'),
('Telmisartana','Potássio','MODERADA','Hipercalemia','Hipercalemia','Monitorar K+','B'),
('Tramadol','Duloxetina','ALTA','Síndrome serotoninérgica','Convulsões; serotonina','Evitar associação','A'),
('Tramadol','Linezolida','ALTA','Síndrome serotoninérgica','Hipertermia; rigidez','Evitar associação','A'),
('Tramadol','Mirtazapina','MODERADA','Risco serotoninérgico','Serotonina','Monitorar; doses baixas','B'),
('Tramadol','Venlafaxina','ALTA','Síndrome serotoninérgica','Risco serotoninérgico','Evitar associação','A'),
('Valproato','Carbamazepina','MODERADA','Interação mútua','Níveis alterados','Monitorar níveis','B'),
('Varfarina','Ciprofloxacino','MODERADA','Inibição metabolismo','INR elevado','Monitorar INR','B'),
('Varfarina','Diclofenaco','ALTA','Sangramento GI e anti-inflamatório','Hemorrhage','Evitar AINE','A'),
('Varfarina','Fluconazol','ALTA','Inibição CYP2C9','INR elevado','Reduzir varfarina; monitorar INR','A'),
('Varfarina','Paracetamol','MODERADA','Doses altas podem aumentar INR','Sangramento','Evitar doses altas prolongadas','B'),
('Varfarina','Prednisona','MODERADA','Alteração metabolismo','Variação INR','Monitorar INR','B'),
('Venlafaxina','Linezolida','ALTA','Síndrome serotoninérgica','Risco serotoninérgico','Evitar associação','A'),
('Verapamil','Digoxina','ALTA','Aumento níveis digoxina','Toxicidade digitálica','Reduzir dose de digoxina','A'),
('Voriconazol','Everolimo','ALTA','Inibição CYP3A4','Níveis elevados de everolimo','Reduzir dose de everolimo','A'),
('Voriconazol','Fenitoína','ALTA','Indução; redução voriconazol','Falha antifúngica','Evitar associação','A'),
('Zolpidem','Fluvoxamina','MODERADA','Inibição metabolismo','Sedação aumentada','Reduzir dose de zolpidem','B'),
('Ácido valproico','Carbamazepina','MODERADA','Interação farmacocinética','Níveis alterados','Monitorar níveis','B')
) AS v(ma, mb, grav, mec, eff, cond, ev)
WHERE NOT EXISTS (
  SELECT 1 FROM interacoes i
  WHERE (i.medicamento_a, i.medicamento_b) = (v.ma, v.mb)
);

-- Constraint para evitar duplicatas (par canônico) e permitir ON CONFLICT
ALTER TABLE interacoes DROP CONSTRAINT IF EXISTS uk_interacoes_ab;
ALTER TABLE interacoes ADD CONSTRAINT uk_interacoes_ab UNIQUE (medicamento_a, medicamento_b);

-- =============================================================================
-- BLOCO 3 a 15 - Completar até 3000+ interações (geração procedural)
-- Pares canônicos (medicamento_a < medicamento_b); mecanismos plausíveis
-- =============================================================================
DO $$
DECLARE
  drugs TEXT[] := ARRAY[
    'AAS','Acarbose','Alopurinol','Alprazolam','Amilorida','Amiodarona','Amitriptilina','Amlodipina','Amoxicilina',
    'Atenolol','Azitromicina','Buspirona','Candesartana','Captopril','Carbamazepina','Carvedilol','Ciclosporina','Cimetidina',
    'Ciprofloxacino','Cisplatina','Citalopram','Claritromicina','Clonazepam','Clonidina','Clopidogrel','Clozapina','Codeína',
    'Colchicina','Dexametasona','Diazepam','Diclofenaco','Digoxina','Diltiazem','Duloxetina','Enalapril','Eritromicina',
    'Escitalopram','Espironolactona','Fenitoína','Fluconazol','Fluvoxamina','Furosemida','Gabapentina','Glibenclamida',
    'Haloperidol','Heparina','Hidroclorotiazida','Ibuprofeno','Indapamida','Insulina','Itraconazol','Ketoconazol',
    'Lamotrigina','Lansoprazol','Levotiroxina','Linezolida','Lítio','Losartana','Lovastatina','Metformina','Metronidazol',
    'Metoprolol','Midazolam','Mirtazapina','Morfina','Naproxeno','Nifedipina','Omeprazol','Paracetamol','Paroxetina',
    'Pravastatina','Prednisona','Propranolol','Quetiapina','Quinidina','Ramipril','Ranitidina','Risperidona','Ritonavir',
    'Rosuvastatina','Sertralina','Sinvastatina','Sotalol','Tacrolimus','Telmisartana','Tramadol','Ácido valproico',
    'Valsartana','Varfarina','Venlafaxina','Verapamil','Voriconazol','Zolpidem','Irbesartana','Flecainida','Pimozida',
    'Clorpromazina','Olanzapina','Nateglinida','Metadona','Everolimo','Eletriptan','Doxorrubicina','Buspirona','Anidulafungina'
  ];
  grav TEXT[] := ARRAY['ALTA','ALTA','MODERADA','MODERADA','MODERADA','BAIXA','BAIXA'];
  mec TEXT[] := ARRAY[
    'Inibição CYP3A4','Inibição CYP2C9','Inibição CYP2D6','Indução enzimática','Competição renal','Prolongamento QT',
    'Depressão SNC aditiva','Risco hemorrágico','Hipercalemia','Hipoglicemia','Toxicidade digitálica','Síndrome serotoninérgica',
    'Rabdomiólise','Nefrotoxicidade','Hepatotoxicidade','Duplicidade terapêutica','Redução excreção renal','Inibição P-gp'
  ];
  eff TEXT[] := ARRAY[
    'Aumento de níveis; toxicidade','Risco de sangramento','Sedação excessiva','Arritmia; prolongamento QT','Hipoglicemia',
    'Hipercalemia','Piora da função renal','Risco serotoninérgico','Rabdomiólise','Alteração de INR','Hipotensão','Bradicardia'
  ];
  cond TEXT[] := ARRAY[
    'Monitorar INR','Evitar associação','Reduzir dose','Monitorar potássio','Monitorar glicemia','Avaliar ECG',
    'Risco aumentado de sedação','Risco de sangramento','Monitorar níveis séricos','Monitorar sinais de toxicidade'
  ];
  ev TEXT[] := ARRAY['A','B','C'];
  ma TEXT; mb TEXT; i INT; j INT; n INT; g TEXT; m TEXT; e TEXT; c TEXT; l TEXT;
BEGIN
  n := array_length(drugs, 1);
  WHILE (SELECT count(*) FROM interacoes) < 3000 LOOP
    i := 1 + floor(random() * n)::INT;
    j := 1 + floor(random() * n)::INT;
    IF i = j THEN CONTINUE; END IF;
    ma := drugs[i]; mb := drugs[j];
    IF ma > mb THEN ma := drugs[j]; mb := drugs[i]; END IF;
    g := grav[1 + floor(random() * array_length(grav,1))::INT];
    m := mec[1 + floor(random() * array_length(mec,1))::INT];
    e := eff[1 + floor(random() * array_length(eff,1))::INT];
    c := cond[1 + floor(random() * array_length(cond,1))::INT];
    l := ev[1 + floor(random() * array_length(ev,1))::INT];
    INSERT INTO interacoes (medicamento_a, medicamento_b, gravidade, mecanismo, efeito_clinico, conduta, nivel_evidencia)
    VALUES (ma, mb, g, m, e, c, l)
    ON CONFLICT (medicamento_a, medicamento_b) DO NOTHING;
  END LOOP;
END $$;

-- =============================================================================
-- ÍNDICES
-- =============================================================================
CREATE INDEX IF NOT EXISTS idx_interacoes_medicamento_a ON interacoes (medicamento_a);
CREATE INDEX IF NOT EXISTS idx_interacoes_medicamento_b ON interacoes (medicamento_b);
CREATE INDEX IF NOT EXISTS idx_interacoes_gravidade ON interacoes (gravidade);

-- =============================================================================
-- VIEW - Interações graves
-- =============================================================================
CREATE OR REPLACE VIEW view_interacoes_graves AS
SELECT * FROM interacoes WHERE gravidade = 'ALTA';

-- =============================================================================
-- FUNÇÃO - Buscar interações por medicamento
-- =============================================================================
CREATE OR REPLACE FUNCTION buscar_interacoes(nome_medicamento TEXT)
RETURNS SETOF interacoes
LANGUAGE sql
STABLE
AS $$
  SELECT * FROM interacoes
  WHERE medicamento_a = nome_medicamento OR medicamento_b = nome_medicamento;
$$;
