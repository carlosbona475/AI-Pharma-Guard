<?php
/**
 * Relatório de polifarmácia — pacientes com 5+ medicamentos (lista em texto)
 * ou 5+ registros em dispensacoes (se a tabela existir).
 */
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../config/database.php';

requireAuthJson();

$farmaciaId = (int) ($_SESSION['farmacia_id'] ?? 0);
if ($farmaciaId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Farmácia inválida na sessão.'], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * @return string[]
 */
function parseMedicamentosUsados(?string $text): array
{
    if ($text === null || trim($text) === '') {
        return [];
    }
    $parts = preg_split('/[,;\n\r]+/u', $text);
    $out = [];
    foreach ($parts as $p) {
        $t = trim($p);
        if ($t !== '') {
            $out[] = $t;
        }
    }
    return array_values(array_unique($out));
}

function tableExists(PDO $conn, string $name): bool
{
    $stmt = $conn->prepare('
        SELECT COUNT(*) AS c FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = ?
    ');
    $stmt->execute([$name]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return !empty($row['c']) && (int) $row['c'] > 0;
}

try {
    $conn = getConnection();

    $stmt = $conn->prepare('
        SELECT id, nome, idade, sexo, medicamentos_usados, created_at
        FROM pacientes
        WHERE farmacia_id = ?
        ORDER BY nome
    ');
    $stmt->execute([$farmaciaId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $dispPorPaciente = [];
    if (tableExists($conn, 'dispensacoes')) {
        try {
            $stmtD = $conn->prepare('
                SELECT paciente_id, COUNT(*) AS cnt
                FROM dispensacoes
                WHERE farmacia_id = ?
                GROUP BY paciente_id
            ');
            $stmtD->execute([$farmaciaId]);
            while ($r = $stmtD->fetch(PDO::FETCH_ASSOC)) {
                $dispPorPaciente[(int) $r['paciente_id']] = (int) $r['cnt'];
            }
        } catch (Throwable $e) {
            $dispPorPaciente = [];
        }
    }

    $pacientesOut = [];

    foreach ($rows as $p) {
        $id = (int) $p['id'];
        $meds = parseMedicamentosUsados(isset($p['medicamentos_usados']) ? (string) $p['medicamentos_usados'] : '');
        $nTexto = count($meds);
        $nDisp = isset($dispPorPaciente[$id]) ? $dispPorPaciente[$id] : 0;
        $total = max($nTexto, $nDisp);

        if ($total < 5) {
            continue;
        }

        $listaMed = $meds;
        if ($nDisp > $nTexto) {
            $listaMed[] = '(' . ($nDisp - $nTexto) . ' registro(s) em dispensações além da lista cadastrada)';
        }

        $criado = $p['created_at'] ?? null;
        $ultima = $criado ? date('c', strtotime((string) $criado)) : null;

        $pacientesOut[] = [
            'id'                   => $id,
            'nome'                 => $p['nome'],
            'idade'                => (int) $p['idade'],
            'sexo'                 => $p['sexo'] ?? '',
            'total_medicamentos'   => $total,
            'medicamentos'         => array_values($listaMed),
            'nivel_risco'          => ($total >= 8) ? 'muito_alto' : 'alto',
            'ultima_atualizacao'   => $ultima,
        ];
    }

    usort($pacientesOut, function ($a, $b) {
        return $b['total_medicamentos'] <=> $a['total_medicamentos'];
    });

    echo json_encode([
        'pacientes' => $pacientesOut,
        'total'     => count($pacientesOut),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao gerar relatório de polifarmácia.',
        'error'   => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
