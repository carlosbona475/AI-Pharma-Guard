<?php
/**
 * Calculadora de dose por paciente (mg/kg + limites e alertas clínicos).
 */
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Use POST.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../config/database.php';

requireAuthJson();

$farmaciaId = (int) ($_SESSION['farmacia_id'] ?? 0);
if ($farmaciaId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sessão inválida.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
$data = $raw ? json_decode($raw, true) : null;
$pacienteId = isset($data['paciente_id']) ? (int) $data['paciente_id'] : 0;
$medicamentoId = isset($data['medicamento_id']) ? (int) $data['medicamento_id'] : 0;

if ($pacienteId <= 0 || $medicamentoId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Informe paciente_id e medicamento_id válidos.'], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * @return 'pediatrico'|'adulto'|'geriatrico'
 */
function classificarPacientePorIdade(int $idade): string
{
    if ($idade < 12) {
        return 'pediatrico';
    }
    if ($idade >= 60) {
        return 'geriatrico';
    }

    return 'adulto';
}

function nfloat($v): ?float
{
    if ($v === null || $v === '') {
        return null;
    }
    if (!is_numeric($v)) {
        return null;
    }

    return (float) $v;
}

try {
    $conn = getConnection();
    ensurePacientesTable($conn);
    ensureMedicamentosCalculadoraColumns($conn);

    $stmt = $conn->prepare('
        SELECT id, nome, idade, sexo, peso, altura
        FROM pacientes
        WHERE id = ? AND farmacia_id = ?
        LIMIT 1
    ');
    $stmt->execute([$pacienteId, $farmaciaId]);
    $pac = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$pac) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Paciente não encontrado.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $conn->prepare('
        SELECT id, nome, classe_farmacologica, contraindicacoes,
               dose_mg_kg, dose_minima, dose_maxima,
               dose_adulto, dose_pediatrica, dose_geriatrica,
               idade_minima, via_administracao
        FROM medicamentos
        WHERE id = ? AND farmacia_id = ?
        LIMIT 1
    ');
    $stmt->execute([$medicamentoId, $farmaciaId]);
    $med = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$med) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Medicamento não encontrado.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $idade = (int) $pac['idade'];
    $classificacao = classificarPacientePorIdade($idade);

    $peso = nfloat($pac['peso'] ?? null);
    $temPeso = $peso !== null && $peso > 0;

    $doseMgKg = nfloat($med['dose_mg_kg'] ?? null);
    $doseMin = nfloat($med['dose_minima'] ?? null);
    $doseMax = nfloat($med['dose_maxima'] ?? null);

    $alertas = [];
    $observacao = '';

    if ($classificacao === 'pediatrico' && trim((string) ($med['dose_pediatrica'] ?? '')) === '') {
        $alertas[] = 'Atenção: dose pediátrica não especificada';
    }
    if ($classificacao === 'geriatrico') {
        $alertas[] = 'Atenção: paciente idoso — considerar redução de 25-30%';
    }
    if (!$temPeso) {
        $alertas[] = 'Peso não informado — usando dose padrão';
    }

    $podeMgKg = $temPeso && $doseMgKg !== null && $doseMgKg > 0;

    $doseCalculada = 0.0;
    $doseRecomendada = '';

    if ($podeMgKg) {
        $bruta = $peso * $doseMgKg;
        if ($doseMax !== null && $bruta > $doseMax) {
            $alertas[] = 'ALERTA: dose calculada excede o máximo recomendado';
        }
        $doseCalculada = $bruta;
        if ($doseMin !== null) {
            $doseCalculada = max($doseCalculada, $doseMin);
        }
        if ($doseMax !== null) {
            $doseCalculada = min($doseCalculada, $doseMax);
        }
        $doseCalculada = round($doseCalculada, 3);

        $via = trim((string) ($med['via_administracao'] ?? ''));
        $doseRecomendada = $doseCalculada . ' mg';
        if ($via !== '') {
            $doseRecomendada .= ' — ' . $via;
        }
        $observacao = 'Dose estimada por mg/kg (' . $doseMgKg . ' mg/kg × ' . $peso . ' kg), respeitando dose mínima/máxima cadastrada quando informadas.';
    } else {
        $textoDose = '';
        if ($classificacao === 'pediatrico') {
            $textoDose = trim((string) ($med['dose_pediatrica'] ?? ''));
        } elseif ($classificacao === 'geriatrico') {
            $textoDose = trim((string) ($med['dose_geriatrica'] ?? ''));
        } else {
            $textoDose = trim((string) ($med['dose_adulto'] ?? ''));
        }
        if ($textoDose === '') {
            $textoDose = trim((string) ($med['dose_adulto'] ?? ''));
        }

        if ($doseMin !== null) {
            $doseCalculada = round((float) $doseMin, 3);
        } elseif ($doseMax !== null) {
            $doseCalculada = round((float) $doseMax * 0.5, 3);
        } else {
            $doseCalculada = 0.0;
        }

        $doseRecomendada = $textoDose !== '' ? $textoDose : ($doseCalculada > 0 ? $doseCalculada . ' mg (referência mínima/máxima)' : 'Consulte bulário / prescrição médica');
        $observacao = 'Sem cálculo por mg/kg (peso ou dose mg/kg ausente). Valor numérico exibido usa referência mínima cadastrada ou estimativa conservadora.';
    }

    $contra = trim((string) ($med['contraindicacoes'] ?? ''));

    echo json_encode([
        'paciente' => [
            'nome'           => $pac['nome'],
            'idade'          => $idade,
            'peso'           => $peso,
            'classificacao'  => $classificacao,
        ],
        'medicamento' => [
            'nome'               => $med['nome'],
            'via_administracao'  => $med['via_administracao'] ?? '',
        ],
        'resultado' => [
            'dose_calculada_mg'       => (float) $doseCalculada,
            'dose_recomendada'        => $doseRecomendada,
            'classificacao_paciente'  => $classificacao,
            'alertas'                 => array_values(array_unique($alertas)),
            'contraindicacoes'        => $contra,
            'observacao'              => $observacao,
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao calcular dose.',
        'error'   => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
