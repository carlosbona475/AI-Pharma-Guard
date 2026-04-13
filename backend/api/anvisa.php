<?php
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
    echo json_encode(['error' => 'Método não permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
if ($q === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Parâmetro q é obrigatório'], JSON_UNESCAPED_UNICODE);
    exit;
}

function avPick(array $row, array $keys, $default = '')
{
    foreach ($keys as $k) {
        if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') {
            return $row[$k];
        }
    }
    return $default;
}

function normalizeTipo($value): string
{
    $v = mb_strtolower(trim((string) $value), 'UTF-8');
    if ($v === '') {
        return 'referencia';
    }
    if (strpos($v, 'gen') !== false) {
        return 'generico';
    }
    if (strpos($v, 'sim') !== false) {
        return 'similar';
    }
    return 'referencia';
}

function normalizeSituacao($value): string
{
    $v = mb_strtolower(trim((string) $value), 'UTF-8');
    if ($v === '') {
        return 'ativo';
    }
    if (strpos($v, 'cancel') !== false || strpos($v, 'susp') !== false || strpos($v, 'inativ') !== false) {
        return 'cancelado';
    }
    return 'ativo';
}

function mapAnvisaRow(array $row): array
{
    $nome = (string) avPick($row, ['nomeProduto', 'nome_produto', 'nomeMedicamento', 'nome'], '');
    $principio = (string) avPick($row, ['principioAtivo', 'principio_ativo', 'substancia', 'substanciaAtiva'], '');
    $classe = (string) avPick($row, ['classeTerapeutica', 'classe_terapeutica', 'categoriaRegulatoria', 'categoria'], '');
    $laboratorio = (string) avPick($row, ['razaoSocial', 'empresaDetentoraRegistro', 'laboratorio', 'detentorRegistro'], '');
    $registro = (string) avPick($row, ['numeroRegistro', 'registro', 'numeroProcesso'], '');
    $situacaoRaw = (string) avPick($row, ['situacaoRegistro', 'situacao', 'status'], '');
    $tipoRaw = (string) avPick($row, ['categoriaRegulatoria', 'tipoProduto', 'tipo', 'classeProduto'], '');

    return [
        'nome' => $nome,
        'principio_ativo' => $principio,
        'classe_terapeutica' => $classe,
        'laboratorio' => $laboratorio,
        'registro' => $registro,
        'situacao' => normalizeSituacao($situacaoRaw),
        'tipo' => normalizeTipo($tipoRaw),
    ];
}

function cachePathFor(string $q): string
{
    $tmpDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);
    return $tmpDir . DIRECTORY_SEPARATOR . 'anvisa_cache_' . md5($q) . '.json';
}

function readCache(string $q): ?array
{
    $path = cachePathFor($q);
    if (!is_file($path)) {
        return null;
    }
    if ((time() - filemtime($path)) > 86400) {
        return null;
    }
    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') {
        return null;
    }
    $data = json_decode($raw, true);
    if (!is_array($data) || !isset($data['resultados']) || !is_array($data['resultados'])) {
        return null;
    }
    return $data;
}

function saveCache(string $q, array $payload): void
{
    $path = cachePathFor($q);
    @file_put_contents($path, json_encode($payload, JSON_UNESCAPED_UNICODE));
}

function fetchAnvisa(string $q): ?array
{
    $url = 'https://consultas.anvisa.gov.br/api/consulta/medicamentos?count=10&filter%5BnomeProduto%5D=' . rawurlencode($q);
    $headers = [
        'Authorization: Guest',
        'Accept: application/json',
    ];

    $body = false;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false || $status < 200 || $status >= 300) {
            $body = false;
        }
    }

    if ($body === false) {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 15,
                'header' => implode("\r\n", $headers),
            ],
        ]);
        $body = @file_get_contents($url, false, $ctx);
    }

    if ($body === false || trim((string) $body) === '') {
        return null;
    }

    $json = json_decode($body, true);
    if (!is_array($json)) {
        return null;
    }

    $rows = [];
    if (isset($json['content']) && is_array($json['content'])) {
        $rows = $json['content'];
    } elseif (isset($json['items']) && is_array($json['items'])) {
        $rows = $json['items'];
    } elseif (isset($json[0])) {
        $rows = $json;
    }

    $resultados = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $mapped = mapAnvisaRow($row);
        if (trim($mapped['nome']) === '' && trim($mapped['principio_ativo']) === '') {
            continue;
        }
        $resultados[] = $mapped;
    }

    return [
        'resultados' => $resultados,
        'total' => count($resultados),
    ];
}

function fallbackLocal(string $q): array
{
    $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'interacoes.json';
    if (!is_file($path)) {
        return ['resultados' => [], 'total' => 0];
    }
    $raw = @file_get_contents($path);
    if ($raw === false || trim($raw) === '') {
        return ['resultados' => [], 'total' => 0];
    }
    $json = json_decode($raw, true);
    if (!is_array($json)) {
        return ['resultados' => [], 'total' => 0];
    }

    $needle = mb_strtolower($q, 'UTF-8');
    $out = [];
    foreach ($json as $row) {
        if (!is_array($row)) {
            continue;
        }
        $a = (string) avPick($row, ['medicamentoA', 'nomeA', 'nome'], '');
        $b = (string) avPick($row, ['medicamentoB', 'nomeB'], '');
        $combined = mb_strtolower($a . ' ' . $b, 'UTF-8');
        if ($needle !== '' && mb_strpos($combined, $needle) === false) {
            continue;
        }
        $nome = $a !== '' ? $a : $b;
        if ($nome === '') {
            continue;
        }
        $out[] = [
            'nome' => $nome,
            'principio_ativo' => '',
            'classe_terapeutica' => (string) avPick($row, ['tipo_interacao'], ''),
            'laboratorio' => 'Fallback local',
            'registro' => '',
            'situacao' => 'ativo',
            'tipo' => 'referencia',
        ];
        if (count($out) >= 10) {
            break;
        }
    }
    return ['resultados' => $out, 'total' => count($out)];
}

$cached = readCache($q);
if ($cached !== null) {
    echo json_encode($cached, JSON_UNESCAPED_UNICODE);
    exit;
}

$payload = fetchAnvisa($q);
if ($payload === null || !isset($payload['resultados'])) {
    $payload = fallbackLocal($q);
}

saveCache($q, $payload);
echo json_encode($payload, JSON_UNESCAPED_UNICODE);
