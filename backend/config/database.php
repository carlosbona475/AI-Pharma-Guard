<?php
/**
 * Conexão MySQL via PDO (Hostinger / local).
 * Credenciais vêm de variáveis de ambiente ou do arquivo .env na raiz do projeto.
 * Charset utf8mb4 (compatível com utf8; emojis e acentuação).
 */

/**
 * Carrega chave=valor do arquivo .env na raiz do projeto (uma vez por requisição).
 * Não sobrescreve variáveis já definidas no servidor.
 */
function ai_guard_load_dotenv(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;

    $root = dirname(__DIR__, 2);
    $path = $root . DIRECTORY_SEPARATOR . '.env';
    if (!is_readable($path)) {
        return;
    }

    $lines = @file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || (isset($line[0]) && $line[0] === '#')) {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));
        if ($key === '') {
            continue;
        }
        if ($val !== '' && isset($val[0])) {
            if ($val[0] === '"' && substr($val, -1) === '"') {
                $val = stripcslashes(substr($val, 1, -1));
            } elseif ($val[0] === "'" && substr($val, -1) === "'") {
                $val = stripcslashes(substr($val, 1, -1));
            }
        }
        if (getenv($key) === false) {
            putenv(sprintf('%s=%s', $key, $val));
            $_ENV[$key] = $val;
        }
    }
}

function getConnection()
{
    ai_guard_load_dotenv();

    $host = getenv('DB_HOST');
    if ($host === false || $host === '') {
        $host = 'localhost';
    }

    $port = getenv('DB_PORT');
    if ($port === false || $port === '') {
        $port = '3306';
    }

    $dbname = getenv('DB_NAME');
    if ($dbname === false) {
        $dbname = '';
    }

    $user = getenv('DB_USER');
    if ($user === false) {
        $user = '';
    }

    $password = getenv('DB_PASSWORD');
    if ($password === false) {
        $password = '';
    }

    if ($dbname === '' || $user === '') {
        http_response_code(500);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'message' => 'Configuração do banco incompleta. Copie .env.example para .env na raiz do projeto e defina DB_NAME e DB_USER (e DB_PASSWORD).',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $host,
        $port,
        $dbname
    );

    try {
        $pdo = new PDO(
            $dsn,
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
        $pdo->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');

        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao conectar ao banco. Verifique o .env e se o MySQL está acessível.',
            'error'   => $e->getMessage(),
        ], JSON_UNESCAPED_UNICODE);
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
