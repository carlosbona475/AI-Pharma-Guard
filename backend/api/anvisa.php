<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
ini_set('display_errors', '0');

$q = trim($_GET['q'] ?? '');
if ($q === '') {
    echo json_encode(['resultados' => [], 'total' => 0]);
    exit;
}

// Cache 24h
$cacheFile = sys_get_temp_dir() . '/anvisa_' . md5($q) . '.json';
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 86400) {
    echo file_get_contents($cacheFile);
    exit;
}

function fazerCurl($url, $headers = []) {
    if (!function_exists('curl_init')) return false;
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_HTTPHEADER     => array_merge([
            'Accept: application/json',
            'Accept-Language: pt-BR,pt;q=0.9',
            'Referer: https://consultas.anvisa.gov.br/',
            'Origin: https://consultas.anvisa.gov.br',
            'Authorization: Guest'
        ], $headers)
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($httpCode === 200 && $response) ? $response : false;
}

function parsearAnvisa($response, $q) {
    $data = json_decode($response, true);
    if (!$data) return [];

    $resultados = [];
    $content = $data['content']
            ?? $data['resultado']
            ?? $data['data']
            ?? (is_array($data) ? $data : []);

    foreach ($content as $item) {
        if (!is_array($item)) continue;
        $resultados[] = [
            'nome'               => $item['nomeProduto']
                                 ?? $item['nome']
                                 ?? $q,
            'principio_ativo'    => $item['principioAtivo']
                                 ?? $item['principio_ativo']
                                 ?? '',
            'classe_terapeutica' => $item['classeTerapeutica']
                                 ?? $item['classe']
                                 ?? '',
            'laboratorio'        => $item['empresa']
                                 ?? $item['laboratorio']
                                 ?? '',
            'registro'           => $item['numRegistro']
                                 ?? $item['registro']
                                 ?? '',
            'situacao'           => strtolower(
                                     $item['situacaoRegistro']
                                  ?? $item['situacao']
                                  ?? 'ativo'),
            'tipo'               => strtolower(
                                     $item['categoriaRegulatoria']
                                  ?? $item['tipo']
                                  ?? 'generico')
        ];
    }
    return $resultados;
}

// Tentar 3 endpoints diferentes da ANVISA
$endpoints = [
    'https://consultas.anvisa.gov.br/api/consulta/medicamentos?count=20&filter%5BnomeProduto%5D=' . urlencode($q),
    'https://consultas.anvisa.gov.br/api/consulta/medicamentos?count=20&filter%5BprinciipioAtivo%5D=' . urlencode($q),
    'https://consultas.anvisa.gov.br/api/bulario/q?count=20&nomeProduto=' . urlencode($q),
];

$resultados = [];
foreach ($endpoints as $url) {
    $response = fazerCurl($url);
    if ($response) {
        $resultados = parsearAnvisa($response, $q);
        if (count($resultados) > 0) break;
    }
}

// Se ANVISA falhou — usar base local interacoes.json
if (empty($resultados)) {
    $localPaths = [
        __DIR__ . '/../../interacoes.json',
        __DIR__ . '/../../../interacoes.json',
        __DIR__ . '/../../medicamentos.json',
    ];
    foreach ($localPaths as $path) {
        if (!file_exists($path)) continue;
        $local = json_decode(file_get_contents($path), true);
        if (!is_array($local)) continue;
        foreach ($local as $item) {
            $nome = strtolower($item['nome'] ?? '');
            $pa   = strtolower($item['principio_ativo']
                             ?? $item['substancia']
                             ?? $item['nome'] ?? '');
            if (str_contains($nome, strtolower($q)) ||
                str_contains($pa,   strtolower($q))) {
                $resultados[] = [
                    'nome'               => $item['nome'] ?? $q,
                    'principio_ativo'    => $item['principio_ativo']
                                        ?? $item['substancia']
                                        ?? '',
                    'classe_terapeutica' => $item['classe']
                                        ?? $item['classe_farmacologica']
                                        ?? '',
                    'laboratorio'        => 'Base local',
                    'registro'           => '',
                    'situacao'           => 'ativo',
                    'tipo'               => 'generico',
                    'fonte'              => 'local'
                ];
            }
        }
        if (!empty($resultados)) break;
    }
}

// Se ainda vazio — retornar busca RxNav como fallback
if (empty($resultados)) {
    $rxUrl = 'https://rxnav.nlm.nih.gov/REST/drugs.json?name='
           . urlencode($q);
    $rxResponse = fazerCurl($rxUrl);
    if ($rxResponse) {
        $rxData = json_decode($rxResponse, true);
        $grupos = $rxData['drugGroup']['conceptGroup'] ?? [];
        foreach ($grupos as $grupo) {
            foreach ($grupo['conceptProperties'] ?? [] as $prop) {
                $resultados[] = [
                    'nome'               => $prop['name'] ?? $q,
                    'principio_ativo'    => $prop['synonym'] ?? '',
                    'classe_terapeutica' => $grupo['tty'] ?? '',
                    'laboratorio'        => 'RxNav/NIH',
                    'registro'           => $prop['rxcui'] ?? '',
                    'situacao'           => 'ativo',
                    'tipo'               => 'generico',
                    'fonte'              => 'rxnav'
                ];
            }
        }
    }
}

$resultado = [
    'resultados' => $resultados,
    'total'      => count($resultados),
    'fonte'      => empty($resultados) ? 'nenhuma' :
                   ($resultados[0]['fonte'] ?? 'anvisa')
];

// Salvar cache
if (!empty($resultados)) {
    file_put_contents($cacheFile,
        json_encode($resultado, JSON_UNESCAPED_UNICODE));
}

echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
