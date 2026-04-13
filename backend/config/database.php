<?php
function getConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=localhost;dbname=u632052358_phramguard;charset=utf8mb4",
            "u632052358_pharmguard1",
            "Cn05091@",
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao conectar ao banco. Verifique o .env e se o MySQL está acessível.',
            'debug'   => $e->getMessage()
        ]);
        exit;
    }
}

/**
 * Garante que a tabela pacientes existe com colunas usadas pelo cadastro (inclui cpf).
 */
function ensurePacientesTable(PDO $conn)
{
    $conn->exec(
        'CREATE TABLE IF NOT EXISTS pacientes (
          id INT AUTO_INCREMENT PRIMARY KEY,
          farmacia_id INT NOT NULL DEFAULT 1,
          nome VARCHAR(100) NOT NULL,
          idade INT NOT NULL DEFAULT 0,
          cpf VARCHAR(20) DEFAULT NULL,
          sexo VARCHAR(20) DEFAULT \'masculino\',
          doencas TEXT,
          medicamentos_usados TEXT,
          alergias TEXT,
          historico_clinico TEXT,
          observacoes TEXT,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          KEY idx_pacientes_farmacia (farmacia_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    try {
        $conn->exec('ALTER TABLE pacientes ADD COLUMN cpf VARCHAR(20) DEFAULT NULL AFTER idade');
    } catch (PDOException $e) {
        if (!isset($e->errorInfo[1]) || (int) $e->errorInfo[1] !== 1060) {
            throw $e;
        }
    }
    try {
        $conn->exec("ALTER TABLE pacientes ADD COLUMN peso DECIMAL(5,2) NULL COMMENT 'kg' AFTER sexo");
    } catch (PDOException $e) {
        if (!isset($e->errorInfo[1]) || (int) $e->errorInfo[1] !== 1060) {
            throw $e;
        }
    }
    try {
        $conn->exec("ALTER TABLE pacientes ADD COLUMN altura INT NULL COMMENT 'cm' AFTER peso");
    } catch (PDOException $e) {
        if (!isset($e->errorInfo[1]) || (int) $e->errorInfo[1] !== 1060) {
            throw $e;
        }
    }
}

/**
 * Colunas extras em medicamentos para a calculadora de dose (idempotente).
 */
function ensureMedicamentosCalculadoraColumns(PDO $conn)
{
    $stmts = [
        "ALTER TABLE medicamentos ADD COLUMN dose_mg_kg DECIMAL(8,3) NULL COMMENT 'dose em mg por kg do paciente'",
        "ALTER TABLE medicamentos ADD COLUMN dose_minima DECIMAL(8,2) NULL COMMENT 'mg'",
        "ALTER TABLE medicamentos ADD COLUMN dose_maxima DECIMAL(8,2) NULL COMMENT 'mg'",
        'ALTER TABLE medicamentos ADD COLUMN dose_adulto VARCHAR(100) NULL',
        'ALTER TABLE medicamentos ADD COLUMN dose_pediatrica VARCHAR(100) NULL',
        'ALTER TABLE medicamentos ADD COLUMN dose_geriatrica VARCHAR(100) NULL',
        "ALTER TABLE medicamentos ADD COLUMN idade_minima INT NULL COMMENT 'anos'",
        'ALTER TABLE medicamentos ADD COLUMN via_administracao VARCHAR(50) NULL',
    ];
    foreach ($stmts as $sql) {
        try {
            $conn->exec($sql);
        } catch (PDOException $e) {
            if (!isset($e->errorInfo[1]) || (int) $e->errorInfo[1] !== 1060) {
                throw $e;
            }
        }
    }
}
