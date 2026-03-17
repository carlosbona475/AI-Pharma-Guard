<?php
header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    exit;
}

function sendJson($data) {
    ob_end_clean();
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function sendError($message, $code = 500) {
    http_response_code($code);
    sendJson(['success' => false, 'error' => $message]);
    exit;
}

// Resposta padrão para erros de banco
function sendDbError() {
    http_response_code(500);
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Erro interno'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/db.php';
$conn = getConnection();

// SaaS multi-farmácia: usar farmácia da sessão ou padrão 1 (evita NOT NULL no INSERT)
$farmacia_id = isset($_SESSION['farmacia_id']) ? (int) $_SESSION['farmacia_id'] : 1;

/**
 * Normaliza o nível de risco vindo do banco (baixo/medio/alto ou LEVE/MODERADA/GRAVE)
 * para um dos três valores: leve, moderada, grave.
 */
function normalizarNivelRisco($nivel) {
    $n = mb_strtolower((string) $nivel, 'UTF-8');
    if ($n === 'grave' || $n === 'alto') {
        return 'grave';
    }
    if ($n === 'moderada' || $n === 'medio' || $n === 'médio') {
        return 'moderada';
    }
    return 'leve';
}

/**
 * Verifica interações entre os medicamentos informados.
 * Retorna array de interações com dados dos medicamentos A e B.
 *
 * @param PDO   $conn
 * @param int   $farmacia_id
 * @param int[] $idsMedicamentos
 * @return array
 */
function verificarInteracoes(PDO $conn, $farmacia_id, array $idsMedicamentos) {
    $ids = array_values(array_unique(array_filter(array_map('intval', $idsMedicamentos))));
    if (count($ids) < 2) {
        return [];
    }

    $interacoes = [];
    try {
        $stmt = $conn->prepare("
            SELECT i.id,
                   i.farmacia_id,
                   i.medicamento_a AS \"medicamentoA\",
                   i.medicamento_b AS \"medicamentoB\",
                   i.tipo_interacao,
                   i.nivel_risco,
                   i.recomendacao,
                   ma.nome AS \"nomeA\",
                   mb.nome AS \"nomeB\"
            FROM interacoes i
            JOIN medicamentos ma ON ma.id = i.medicamento_a
            JOIN medicamentos mb ON mb.id = i.medicamento_b
            WHERE i.farmacia_id = ?
              AND ((i.medicamento_a = ? AND i.medicamento_b = ?) OR (i.medicamento_a = ? AND i.medicamento_b = ?))
        ");
        for ($i = 0; $i < count($ids); $i++) {
            for ($j = $i + 1; $j < count($ids); $j++) {
                $a = $ids[$i];
                $b = $ids[$j];
                $stmt->execute([$farmacia_id, $a, $b, $b, $a]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $row) {
                    $row['nivel_risco_normalizado'] = normalizarNivelRisco($row['nivel_risco'] ?? '');
                    $interacoes[] = $row;
                }
            }
        }
    } catch (PDOException $e) {
        sendDbError();
    }
    return $interacoes;
}

/**
 * Verifica possíveis alergias cruzando lista de medicamentos do paciente
 * com o texto livre de alergias do prontuário.
 *
 * @param array  $medicamentosPaciente Array de ['id' => int, 'nome' => string]
 * @param string $alergiasTexto
 * @return array Array de alertas
 */
function verificarAlergias(array $medicamentosPaciente, $alergiasTexto) {
    $alergiasTexto = mb_strtolower((string) $alergiasTexto, 'UTF-8');
    if ($alergiasTexto === '') {
        return [];
    }

    // Quebra alergias em termos (vírgula, ponto e vírgula, barra)
    $termos = preg_split('/[;,\/]| e /u', $alergiasTexto);
    $termosLimpos = [];
    foreach ($termos as $t) {
        $t = trim($t);
        if ($t !== '') {
            $termosLimpos[] = $t;
        }
    }
    if (!$termosLimpos) {
        return [];
    }

    $alertas = [];
    foreach ($medicamentosPaciente as $med) {
        $nomeMed = mb_strtolower($med['nome'], 'UTF-8');
        foreach ($termosLimpos as $termo) {
            if ($termo === '') {
                continue;
            }
            // Se o nome do medicamento aparece na alergia ou vice-versa
            if (mb_strpos($alergiasTexto, $nomeMed, 0, 'UTF-8') !== false ||
                mb_strpos($nomeMed, $termo, 0, 'UTF-8') !== false ||
                mb_strpos($alergiasTexto, $termo, 0, 'UTF-8') !== false) {
                $alertas[] = [
                    'medicamento_id' => $med['id'],
                    'medicamento' => $med['nome'],
                    'termo_alergia' => $termo,
                    'mensagem' => 'Possível alergia relacionada a "' . $termo . '" para o medicamento ' . $med['nome'],
                ];
                break;
            }
        }
    }

    return $alertas;
}

/**
 * Calcula o score de risco clínico a partir das interações e alertas de alergia.
 *
 * @param array $interacoes
 * @param array $alertasAlergia
 * @return array ['score' => int, 'nivel' => string, 'recomendacao' => string]
 */
function calcularScoreRisco(array $interacoes, array $alertasAlergia) {
    $score = 0;

    foreach ($interacoes as $i) {
        $nivel = isset($i['nivel_risco_normalizado'])
            ? $i['nivel_risco_normalizado']
            : normalizarNivelRisco($i['nivel_risco'] ?? '');
        if ($nivel === 'grave') {
            $score += 50;
        } elseif ($nivel === 'moderada') {
            $score += 20;
        } else {
            $score += 5;
        }
    }

    // Cada alerta de alergia soma 40 pontos
    $score += count($alertasAlergia) * 40;

    // Limitar score máximo a algo razoável (ex.: 200)
    if ($score > 200) {
        $score = 200;
    }

    if ($score <= 20) {
        $nivel = 'BAIXO';
        $recomendacao = 'Manter acompanhamento e revisar periodicamente a prescrição.';
    } elseif ($score <= 60) {
        $nivel = 'MODERADO';
        $recomendacao = 'Reavaliar a prescrição, monitorar o paciente e considerar ajustes.';
    } elseif ($score <= 100) {
        $nivel = 'ALTO';
        $recomendacao = 'Revisar a prescrição com a equipe médica e considerar ajustes imediatos.';
    } else {
        $nivel = 'CRÍTICO';
        $recomendacao = 'Interromper ou ajustar o tratamento imediatamente e acionar a equipe clínica.';
    }

    return [
        'score' => $score,
        'nivel' => $nivel,
        'recomendacao' => $recomendacao,
    ];
}

// ---- ESTATÍSTICAS (Dashboard) ----
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'estatisticas') {
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM pacientes WHERE farmacia_id = ?");
        $stmt->execute([$farmacia_id]);
        $pacientes = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM medicamentos WHERE farmacia_id = ?");
        $stmt->execute([$farmacia_id]);
        $medicamentos = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM interacoes WHERE farmacia_id = ?");
        $stmt->execute([$farmacia_id]);
        $interacoes = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        sendJson([
            'pacientes' => $pacientes,
            'medicamentos' => $medicamentos,
            'interacoes_cadastradas' => $interacoes
        ]);
    } catch (PDOException $e) {
        sendDbError();
    }
}

// ---- BUSCAR PACIENTE (para exportação de prontuário, exige sessão) ----
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'buscar_paciente') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id <= 0) {
        http_response_code(400);
        sendJson(['success' => false, 'message' => 'ID inválido.']);
    }
    if ($farmacia_id <= 0) {
        http_response_code(401);
        sendJson(['success' => false, 'message' => 'Faça login para acessar.']);
    }
    try {
        $stmt = $conn->prepare('SELECT id, nome, idade, sexo, doencas, medicamentos_usados, alergias, historico_clinico, observacoes, created_at FROM pacientes WHERE id = ? AND farmacia_id = ? LIMIT 1');
        $stmt->execute([$id, $farmacia_id]);
        $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        sendError('Erro ao buscar paciente.', 500);
    }
    if (!$paciente) {
        http_response_code(404);
        sendJson(['success' => false, 'message' => 'Paciente não encontrado.']);
    }
    sendJson(['success' => true, 'paciente' => $paciente]);
}

// ---- LISTAR PACIENTES (com paginação) ----
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'listar_pacientes') {
    $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(100, (int) $_GET['limit'])) : 20;
    $offset = ($page - 1) * $limit;

    try {
        $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM pacientes WHERE farmacia_id = ?');
        $stmt->execute([$farmacia_id]);
        $total = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $conn->prepare('SELECT * FROM pacientes WHERE farmacia_id = ? ORDER BY nome LIMIT ? OFFSET ?');
        $stmt->bindValue(1, $farmacia_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        sendJson([
            'data' => $pacientes,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    } catch (PDOException $e) {
        sendDbError();
    }
}

// ---- CADASTRAR PACIENTE ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'cadastrar_paciente') {
    $raw = file_get_contents('php://input');
    $data = $raw ? json_decode($raw, true) : null;
    if (!$data || empty(trim($data['nome'] ?? ''))) {
        sendJson(['success' => false, 'message' => 'Dados inválidos']);
        exit;
    }
    try {
        $alergiasBrutas = isset($data['alergias']) ? (string) $data['alergias'] : '';
        $alergiasNormalizadas = trim(preg_replace('/\s+/', ' ', mb_strtolower($alergiasBrutas, 'UTF-8')));

        $stmt = $conn->prepare("INSERT INTO pacientes (farmacia_id, nome, idade, sexo, doencas, medicamentos_usados, alergias, historico_clinico, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $farmacia_id,
            trim($data['nome']),
            isset($data['idade']) ? (int) $data['idade'] : 0,
            isset($data['sexo']) ? $data['sexo'] : 'masculino',
            isset($data['doencas']) ? $data['doencas'] : '',
            isset($data['medicamentos']) ? $data['medicamentos'] : '',
            $alergiasNormalizadas,
            isset($data['historico_clinico']) ? $data['historico_clinico'] : '',
            isset($data['observacoes']) ? $data['observacoes'] : '',
        ]);
        sendJson(['success' => true, 'id' => (int) $conn->lastInsertId()]);
        exit;
    } catch (PDOException $e) {
        sendDbError();
    }
}

// ---- LISTAR MEDICAMENTOS ----
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'listar_medicamentos') {
    try {
        $stmt = $conn->prepare("SELECT * FROM medicamentos WHERE farmacia_id = ? ORDER BY nome");
        $stmt->execute([$farmacia_id]);
        $medicamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendJson($medicamentos);
    } catch (PDOException $e) {
        sendDbError();
    }
}

// ---- CADASTRAR MEDICAMENTO ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'cadastrar_medicamento') {
    $raw = file_get_contents('php://input');
    $data = $raw ? json_decode($raw, true) : null;
    if (!$data || empty(trim($data['nome'] ?? ''))) {
        sendError('Dados inválidos', 400);
    }
    try {
        $stmt = $conn->prepare("INSERT INTO medicamentos (farmacia_id, nome, classe_farmacologica, dose, indicacao, contraindicacoes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $farmacia_id,
            trim($data['nome']),
            isset($data['classe']) ? $data['classe'] : '',
            isset($data['dose']) ? $data['dose'] : '',
            isset($data['indicacao']) ? $data['indicacao'] : '',
            isset($data['contraindicacoes']) ? $data['contraindicacoes'] : ''
        ]);
        sendJson(['message' => 'Medicamento cadastrado com sucesso!', 'id' => (int) $conn->lastInsertId()]);
    } catch (PDOException $e) {
        sendDbError();
    }
}

// ---- LISTAR INTERAÇÕES ----
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'listar_interacoes') {
    try {
        $stmt = $conn->prepare("
            SELECT i.id, i.farmacia_id, i.medicamento_a AS \"medicamentoA\", i.medicamento_b AS \"medicamentoB\",
                   i.tipo_interacao, i.nivel_risco, i.recomendacao,
                   ma.nome AS \"nomeA\", mb.nome AS \"nomeB\"
            FROM interacoes i
            JOIN medicamentos ma ON ma.id = i.medicamento_a
            JOIN medicamentos mb ON mb.id = i.medicamento_b
            WHERE i.farmacia_id = ?
            ORDER BY i.nivel_risco DESC, ma.nome
        ");
        $stmt->execute([$farmacia_id]);
        $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendJson($lista);
    } catch (PDOException $e) {
        sendDbError();
    }
}

// ---- CADASTRAR INTERAÇÃO ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'cadastrar_interacao') {
    $raw = file_get_contents('php://input');
    $data = $raw ? json_decode($raw, true) : null;
    $medA = $data ? (int) (isset($data['medicamentoA']) ? $data['medicamentoA'] : 0) : 0;
    $medB = $data ? (int) (isset($data['medicamentoB']) ? $data['medicamentoB'] : 0) : 0;
    if ($medA <= 0 || $medB <= 0 || $medA === $medB) {
        sendError('Selecione dois medicamentos diferentes.', 400);
    }
    $nivel = isset($data['nivel_risco']) && in_array($data['nivel_risco'], ['baixo', 'medio', 'alto']) ? $data['nivel_risco'] : 'medio';
    $tipo = isset($data['tipo_interacao']) ? $data['tipo_interacao'] : '';
    $recomendacao = isset($data['recomendacao']) ? $data['recomendacao'] : '';
    try {
        $stmt = $conn->prepare("INSERT INTO interacoes (farmacia_id, medicamento_a, medicamento_b, tipo_interacao, nivel_risco, recomendacao) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$farmacia_id, $medA, $medB, $tipo, $nivel, $recomendacao]);
        sendJson(['message' => 'Interação cadastrada com sucesso!', 'id' => (int) $conn->lastInsertId()]);
    } catch (PDOException $e) {
        sendDbError();
    }
}

// ---- VERIFICAR INTERAÇÕES ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'verificar_interacoes') {
    $raw = file_get_contents('php://input');
    $data = $raw ? json_decode($raw, true) : null;
    $medicamentos = ($data && isset($data['medicamentos'])) ? $data['medicamentos'] : [];
    $ids = array_values(array_unique(array_filter(array_map(function ($m) {
        return is_array($m) ? (int) (isset($m['id']) ? $m['id'] : 0) : (int) $m;
    }, $medicamentos))));

    $interacoes = verificarInteracoes($conn, $farmacia_id, $ids);
    sendJson($interacoes);
}

// ---- ANALISAR RISCO CLÍNICO DO PACIENTE ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'analisar_risco_paciente') {
    $raw = file_get_contents('php://input');
    $data = $raw ? json_decode($raw, true) : null;
    $pacienteId = $data && isset($data['paciente_id']) ? (int) $data['paciente_id'] : 0;

    if ($pacienteId <= 0) {
        sendError('paciente_id inválido.', 400);
    }

    try {
        $stmt = $conn->prepare("SELECT * FROM pacientes WHERE farmacia_id = ? AND id = ? LIMIT 1");
        $stmt->execute([$farmacia_id, $pacienteId]);
        $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$paciente) {
            sendError('Paciente não encontrado.', 404);
        }
    } catch (PDOException $e) {
        sendDbError();
    }

    // Montar lista de medicamentos do paciente a partir do texto livre
    $medsTexto = (string) ($paciente['medicamentos_usados'] ?? '');
    $medicamentosPaciente = [];
    $idsMedicamentos = [];

    if (trim($medsTexto) !== '') {
        $termosMeds = preg_split('/[;,\/]/u', $medsTexto);
        foreach ($termosMeds as $termo) {
            $termo = trim($termo);
            if ($termo === '') {
                continue;
            }
            try {
                $stmtMed = $conn->prepare("SELECT id, nome FROM medicamentos WHERE farmacia_id = ? AND LOWER(nome) LIKE LOWER(?) LIMIT 1");
                $stmtMed->execute([$farmacia_id, '%' . $termo . '%']);
                $med = $stmtMed->fetch(PDO::FETCH_ASSOC);
                if ($med && !in_array((int) $med['id'], $idsMedicamentos, true)) {
                    $idsMedicamentos[] = (int) $med['id'];
                    $medicamentosPaciente[] = [
                        'id' => (int) $med['id'],
                        'nome' => $med['nome'],
                        'termo_origem' => $termo,
                    ];
                }
            } catch (PDOException $e) {
                sendDbError();
            }
        }
    }

    $interacoes = [];
    if (count($idsMedicamentos) >= 2) {
        $interacoes = verificarInteracoes($conn, $farmacia_id, $idsMedicamentos);
    }

    $alertasAlergia = verificarAlergias($medicamentosPaciente, $paciente['alergias'] ?? '');
    $scoreInfo = calcularScoreRisco($interacoes, $alertasAlergia);

    $resultado = [
        'score' => $scoreInfo['score'],
        'nivel' => $scoreInfo['nivel'],
        'interacoes' => $interacoes,
        'alertas' => $alertasAlergia,
        'recomendacao' => $scoreInfo['recomendacao'],
        'paciente' => [
            'id' => (int) $paciente['id'],
            'nome' => $paciente['nome'],
            'doencas' => $paciente['doencas'],
            'medicamentos_usados' => $paciente['medicamentos_usados'],
            'alergias' => $paciente['alergias'],
            'historico_clinico' => $paciente['historico_clinico'],
        ],
    ];

    sendJson($resultado);
}

// ---- VERIFICAR INTERAÇÕES PACIENTE (por nome; interações globais) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'verificar_interacoes_paciente') {
    $raw = file_get_contents('php://input');
    $data = $raw ? json_decode($raw, true) : null;
    $pacienteId = isset($data['paciente_id']) ? (int) $data['paciente_id'] : 0;
    $medicamentoNovo = isset($data['medicamento_novo']) ? trim((string) $data['medicamento_novo']) : '';
    $medicamentosTexto = isset($data['medicamentos_texto']) ? trim((string) $data['medicamentos_texto']) : '';

    $listaNomes = [];
    if ($pacienteId > 0) {
        if ($medicamentoNovo === '') {
            sendError('medicamento_novo é obrigatório quando paciente_id é informado.', 400);
        }
    try {
        $stmt = $conn->prepare('SELECT medicamentos_usados FROM pacientes WHERE farmacia_id = ? AND id = ? LIMIT 1');
        $stmt->execute([$farmacia_id, $pacienteId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        sendDbError();
    }
        if (!$row) {
            sendJson(['alertas' => [], 'total' => 0]);
        }
        $medsTexto = (string) ($row['medicamentos_usados'] ?? '');
        $listaNomes = array_filter(array_map('trim', preg_split('/[;,\/]/u', $medsTexto)));
        $listaNomes = array_unique(array_merge([$medicamentoNovo], $listaNomes));
    } elseif ($medicamentosTexto !== '') {
        $listaNomes = array_unique(array_filter(array_map('trim', preg_split('/[;,\/]/u', $medicamentosTexto))));
    } else {
        sendError('Informe paciente_id + medicamento_novo ou medicamentos_texto.', 400);
    }
    $listaNomes = array_values($listaNomes);

    $alertas = [];
    try {
        $stmt = $conn->prepare('SELECT medicamento_a_nome AS "medicamentoA", medicamento_b_nome AS "medicamentoB", tipo_interacao, nivel_risco, recomendacao FROM interacoes_globais');
        $stmt->execute();
        $todas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        sendDbError();
    }

    for ($i = 0; $i < count($listaNomes); $i++) {
        for ($j = $i + 1; $j < count($listaNomes); $j++) {
            $a = mb_strtolower($listaNomes[$i], 'UTF-8');
            $b = mb_strtolower($listaNomes[$j], 'UTF-8');
            foreach ($todas as $inter) {
                $medA = mb_strtolower((string) ($inter['medicamentoA'] ?? ''), 'UTF-8');
                $medB = mb_strtolower((string) ($inter['medicamentoB'] ?? ''), 'UTF-8');
                $match = (mb_strpos($medA, $a) !== false && mb_strpos($medB, $b) !== false) ||
                    (mb_strpos($medA, $b) !== false && mb_strpos($medB, $a) !== false);
                if ($match) {
                    $alertas[] = [
                        'medicamentoA' => $inter['medicamentoA'],
                        'medicamentoB' => $inter['medicamentoB'],
                        'nivel_risco' => $inter['nivel_risco'],
                        'recomendacao' => $inter['recomendacao'],
                        'tipo_interacao' => $inter['tipo_interacao'] ?? '',
                    ];
                }
            }
        }
    }

    $ordem = ['grave' => 0, 'moderada' => 1, 'medio' => 1, 'baixo' => 2, 'leve' => 2];
    usort($alertas, function ($x, $y) use ($ordem) {
        $ox = $ordem[mb_strtolower($x['nivel_risco'] ?? '', 'UTF-8')] ?? 3;
        $oy = $ordem[mb_strtolower($y['nivel_risco'] ?? '', 'UTF-8')] ?? 3;
        return $ox - $oy;
    });

    sendJson(['alertas' => $alertas, 'total' => count($alertas)]);
}

// ---- VERIFICAR ALERGIA (paciente + medicamento) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'verificar_alergia') {
    $raw = file_get_contents('php://input');
    $data = $raw ? json_decode($raw, true) : null;
    $pacienteId = isset($data['paciente_id']) ? (int) $data['paciente_id'] : 0;
    $medicamentoNome = isset($data['medicamento_nome']) ? trim((string) $data['medicamento_nome']) : '';

    if ($pacienteId <= 0 || $medicamentoNome === '') {
        sendJson(['alerta' => false, 'mensagem' => 'paciente_id e medicamento_nome são obrigatórios.'], 400);
    }

    try {
        $stmt = $conn->prepare('SELECT alergias FROM pacientes WHERE farmacia_id = ? AND id = ? LIMIT 1');
        $stmt->execute([$farmacia_id, $pacienteId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        sendDbError();
    }

    if (!$row) {
        sendJson(['alerta' => false, 'mensagem' => 'Paciente não encontrado.']);
    }

    $alergiasTexto = mb_strtolower((string) ($row['alergias'] ?? ''), 'UTF-8');
    $medNorm = mb_strtolower($medicamentoNome, 'UTF-8');

    if ($alergiasTexto === '') {
        sendJson(['alerta' => false, 'mensagem' => 'Nenhuma alergia encontrada para este medicamento.']);
    }

    // Nome do medicamento aparece no texto de alergias?
    if (mb_strpos($alergiasTexto, $medNorm, 0, 'UTF-8') !== false) {
        sendJson([
            'alerta' => true,
            'mensagem' => 'Paciente possui alergia registrada a: ' . $medicamentoNome . '.',
        ]);
    }

    // Algum termo de alergia (ex.: "Dipirona") coincide com o medicamento ou é substring?
    $termos = preg_split('/[;,\/]| e /u', $alergiasTexto);
    foreach ($termos as $t) {
        $t = trim($t);
        if ($t === '') continue;
        $t = mb_strtolower($t, 'UTF-8');
        if (mb_strpos($medNorm, $t, 0, 'UTF-8') !== false || mb_strpos($t, $medNorm, 0, 'UTF-8') !== false) {
            sendJson([
                'alerta' => true,
                'mensagem' => 'Paciente possui alergia registrada a: ' . $medicamentoNome . '.',
            ]);
        }
    }

    sendJson(['alerta' => false, 'mensagem' => 'Nenhuma alergia encontrada para este medicamento.']);
}

sendError('Ação não reconhecida', 400);
