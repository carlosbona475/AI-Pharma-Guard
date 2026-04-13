<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
ini_set('display_errors', '0');

// Dicionário de tradução de severidade
function traduzirSeveridade($severity) {
    $mapa = [
        'high'         => 'grave',
        'severe'       => 'grave',
        'serious'      => 'grave',
        'moderate'     => 'moderada',
        'medium'       => 'moderada',
        'low'          => 'leve',
        'minor'        => 'leve',
        'insignificant'=> 'leve',
        'unknown'      => 'desconhecida'
    ];
    return $mapa[strtolower($severity)] ?? 'moderada';
}

// Dicionário de tradução de descrições comuns
function traduzirDescricao($texto) {
    $traducoes = [
        'increased risk of bleeding'    => 'risco aumentado de sangramento',
        'increased risk of toxicity'    => 'risco aumentado de toxicidade',
        'decreased effectiveness'       => 'redução da eficácia',
        'increased blood pressure'      => 'aumento da pressão arterial',
        'decreased blood pressure'      => 'redução da pressão arterial',
        'increased sedation'            => 'aumento da sedação',
        'cardiac arrhythmia'            => 'arritmia cardíaca',
        'increased risk of seizures'    => 'risco aumentado de convulsões',
        'serotonin syndrome'            => 'síndrome serotoninérgica',
        'QT prolongation'               => 'prolongamento do intervalo QT',
        'renal impairment'              => 'comprometimento renal',
        'hepatotoxicity'                => 'hepatotoxicidade',
        'increased anticoagulant effect'=> 'aumento do efeito anticoagulante',
        'hypoglycemia'                  => 'hipoglicemia',
        'hyperkalemia'                  => 'hipercalemia',
        'rhabdomyolysis'                => 'rabdomiólise',
        'increased CNS depression'      => 'aumento da depressão do SNC',
        'may increase the risk'         => 'pode aumentar o risco',
        'may decrease the effect'       => 'pode reduzir o efeito',
        'concurrent use'                => 'uso concomitante',
        'avoid combination'             => 'evitar combinação',
        'monitor closely'               => 'monitorar de perto',
        'use with caution'              => 'usar com cautela',
        'contraindicated'               => 'contraindicado'
    ];

    $resultado = $texto;
    foreach ($traducoes as $en => $pt) {
        $resultado = str_ireplace($en, $pt, $resultado);
    }
    return $resultado;
}

function fazerRequisicao($url) {
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'timeout' => 8,
            'header'  => [
                'User-Agent: AIGuardPharm/1.0',
                'Accept: application/json'
            ]
        ],
        'ssl' => [
            'verify_peer'      => false,
            'verify_peer_name' => false
        ]
    ]);
    return @file_get_contents($url, false, $ctx);
}

function buscarRxCUI($nomeMedicamento) {
    $url = 'https://rxnav.nlm.nih.gov/REST/rxcui.json'
         . '?name=' . urlencode($nomeMedicamento)
         . '&search=1';
    $response = fazerRequisicao($url);
    if (!$response) return null;
    $data = json_decode($response, true);
    return $data['idGroup']['rxnormId'][0] ?? null;
}

$action = $_GET['action'] ?? '';

// ---- Buscar RxCUI de um medicamento ----
if ($action === 'buscar_rxcui') {
    $nome = trim($_GET['nome'] ?? '');
    if (!$nome) {
        echo json_encode(['success' => false,
                          'message' => 'Nome obrigatório']);
        exit;
    }
    $rxcui = buscarRxCUI($nome);
    echo json_encode([
        'success' => (bool)$rxcui,
        'rxcui'   => $rxcui,
        'nome'    => $nome
    ]);
    exit;
}

// ---- Verificar interações entre dois medicamentos ----
if ($action === 'verificar') {
    $med1 = trim($_GET['med1'] ?? '');
    $med2 = trim($_GET['med2'] ?? '');

    if (!$med1 || !$med2) {
        echo json_encode(['success' => false,
                          'message' => 'Informe dois medicamentos']);
        exit;
    }

    // Verificar cache (12h)
    $cacheKey = md5($med1 . '_' . $med2);
    $cacheFile = sys_get_temp_dir() . '/rxnav_' . $cacheKey . '.json';
    if (file_exists($cacheFile) &&
        (time() - filemtime($cacheFile)) < 43200) {
        echo file_get_contents($cacheFile);
        exit;
    }

    // Buscar RxCUIs
    $rxcui1 = buscarRxCUI($med1);
    $rxcui2 = buscarRxCUI($med2);

    if (!$rxcui1 || !$rxcui2) {
        $resultado = [
            'success'    => true,
            'encontrado' => false,
            'message'    => 'Um ou mais medicamentos não encontrados na base RxNav.',
            'med1'       => ['nome' => $med1, 'rxcui' => $rxcui1],
            'med2'       => ['nome' => $med2, 'rxcui' => $rxcui2],
            'interacoes' => []
        ];
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Buscar interações entre os dois
    $url = 'https://rxnav.nlm.nih.gov/REST/interaction/list.json'
         . '?rxcuis=' . $rxcui1 . '+' . $rxcui2;
    $response = fazerRequisicao($url);

    $interacoes = [];
    if ($response) {
        $data = json_decode($response, true);
        $pairs = $data['fullInteractionTypeGroup'][0]
                      ['fullInteractionType'] ?? [];
        foreach ($pairs as $pair) {
            $interactionPairs = $pair['interactionPair'] ?? [];
            foreach ($interactionPairs as $ip) {
                $severidade = $ip['severity'] ?? 'unknown';
                $descricao  = $ip['description'] ?? '';
                $interacoes[] = [
                    'severidade'           => traduzirSeveridade($severidade),
                    'severidade_original'  => $severidade,
                    'descricao'            => traduzirDescricao($descricao),
                    'descricao_original'   => $descricao,
                    'medicamento_a'        => $ip['interactionConcept'][0]
                                              ['minConceptItem']['name'] ?? $med1,
                    'medicamento_b'        => $ip['interactionConcept'][1]
                                              ['minConceptItem']['name'] ?? $med2,
                    'fonte'                => 'RxNav/NIH'
                ];
            }
        }
    }

    $resultado = [
        'success'    => true,
        'encontrado' => count($interacoes) > 0,
        'med1'       => ['nome' => $med1, 'rxcui' => $rxcui1],
        'med2'       => ['nome' => $med2, 'rxcui' => $rxcui2],
        'interacoes' => $interacoes,
        'total'      => count($interacoes)
    ];

    file_put_contents($cacheFile,
                      json_encode($resultado, JSON_UNESCAPED_UNICODE));
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['success' => false,
                  'message' => 'Ação não reconhecida. Use action=verificar ou action=buscar_rxcui']);
