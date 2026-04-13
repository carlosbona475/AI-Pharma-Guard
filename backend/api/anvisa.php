<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

$q = trim($_GET['q'] ?? '');
if ($q === '') {
    echo json_encode(['resultados' => [], 'total' => 0]);
    exit;
}

// Verificar cache (24h)
$cacheFile = sys_get_temp_dir() . '/anvisa_' . md5($q) . '.json';
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 86400) {
    echo file_get_contents($cacheFile);
    exit;
}

// Tentar API da ANVISA (endpoint correto 2025)
$url = 'https://consultas.anvisa.gov.br/api/consulta/medicamentos'
     . '?count=20'
     . '&filter%5BnomeProduto%5D=' . urlencode($q);

$ctx = stream_context_create([
    'http' => [
        'method'  => 'GET',
        'timeout' => 10,
        'header'  => [
            'User-Agent: Mozilla/5.0',
            'Accept: application/json',
            'Authorization: Guest',
            'Referer: https://consultas.anvisa.gov.br/'
        ]
    ],
    'ssl' => [
        'verify_peer'      => false,
        'verify_peer_name' => false
    ]
]);

$response = @file_get_contents($url, false, $ctx);

// Se a ANVISA falhar, tentar endpoint alternativo
if (!$response) {
    $url2 = 'https://consultas.anvisa.gov.br/api/consulta/bulario'
          . '?count=20'
          . '&filter%5BnomeProduto%5D=' . urlencode($q);
    $response = @file_get_contents($url2, false, $ctx);
}

// Se ainda falhar, usar busca local no interacoes.json
if (!$response) {
    $localFile = __DIR__ . '/../../interacoes.json';
    if (file_exists($localFile)) {
        $local = json_decode(file_get_contents($localFile), true);
        $resultados = [];
        foreach ($local as $item) {
            $nome = strtolower($item['nome'] ?? '');
            $pa   = strtolower($item['principio_ativo'] ?? $item['nome'] ?? '');
            if (str_contains($nome, strtolower($q)) ||
                str_contains($pa,  strtolower($q))) {
                $resultados[] = [
                    'nome'             => $item['nome'] ?? '',
                    'principio_ativo'  => $item['principio_ativo'] ?? $item['nome'] ?? '',
                    'classe_terapeutica' => $item['classe'] ?? $item['classe_farmacologica'] ?? '',
                    'laboratorio'      => 'Base local',
                    'registro'         => '',
                    'situacao'         => 'ativo',
                    'tipo'             => 'generico'
                ];
            }
        }
        $resultado = ['resultados' => $resultados, 'total' => count($resultados)];
        echo json_encode($resultado);
        exit;
    }
    echo json_encode(['resultados' => [], 'total' => 0,
                      'erro' => 'ANVISA indisponível']);
    exit;
}

$data = json_decode($response, true);
$resultados = [];

// Parsear resposta da ANVISA
$content = $data['content'] ?? $data['resultado'] ?? $data ?? [];
foreach ($content as $item) {
    $resultados[] = [
        'nome'              => $item['nomeProduto']      ?? $item['nome'] ?? '',
        'principio_ativo'   => $item['principioAtivo']   ?? $item['principio_ativo'] ?? '',
        'classe_terapeutica'=> $item['classeTerapeutica'] ?? '',
        'laboratorio'       => $item['empresa']          ?? $item['laboratorio'] ?? '',
        'registro'          => $item['numRegistro']      ?? '',
        'situacao'          => strtolower($item['situacaoRegistro'] ?? 'ativo'),
        'tipo'              => strtolower($item['categoriaRegulatoria'] ?? 'generico')
    ];
}

$resultado = ['resultados' => $resultados, 'total' => count($resultados)];

// Salvar cache
file_put_contents($cacheFile, json_encode($resultado));

echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
