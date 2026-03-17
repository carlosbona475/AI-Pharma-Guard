<?php
/**
 * Exportação do prontuário completo do paciente em PDF.
 * GET ?paciente_id=1 — exige sessão ativa (farmacia_id). Paciente deve pertencer à farmácia.
 */
session_start();

function sendJsonError($message, $code = 400) {
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

$farmacia_id = isset($_SESSION['farmacia_id']) ? (int) $_SESSION['farmacia_id'] : 0;
if ($farmacia_id <= 0) {
    sendJsonError('Faça login para exportar o prontuário.', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonError('Método não permitido.', 405);
}

$paciente_id = isset($_GET['paciente_id']) ? (int) $_GET['paciente_id'] : 0;
if ($paciente_id <= 0) {
    sendJsonError('paciente_id inválido.', 400);
}

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!is_file($autoload)) {
    sendJsonError('Dependência dompdf não instalada. Execute: composer install', 500);
}
require_once $autoload;

require_once __DIR__ . '/config/database.php';
$conn = getConnection();

// Buscar paciente somente da farmácia logada (segurança)
try {
    $stmt = $conn->prepare(
        'SELECT nome, idade, sexo, doencas, medicamentos_usados, alergias, historico_clinico, observacoes, created_at
         FROM pacientes WHERE id = ? AND farmacia_id = ? LIMIT 1'
    );
    $stmt->execute([$paciente_id, $farmacia_id]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    sendJsonError('Erro ao buscar paciente.', 500);
}

if (!$p) {
    sendJsonError('Paciente não encontrado ou não pertence à sua farmácia.', 404);
}

// Função auxiliar para escapar HTML e exibir texto ou "—"
function h($s) {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
function txt($s) {
    $t = trim((string) $s);
    return $t === '' ? '—' : $t;
}

$dataGeracao = date('d/m/Y H:i');
$dataCadastro = !empty($p['created_at']) ? date('d/m/Y', strtotime($p['created_at'])) : '—';
$temAlergias = trim((string) ($p['alergias'] ?? '')) !== '';

$html = '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>Prontuário</title></head><body style="font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; margin: 20px;">';

// Cabeçalho
$html .= '<div style="border-bottom: 2px solid #2563eb; padding-bottom: 12px; margin-bottom: 20px;">';
$html .= '<div style="font-size: 18px; font-weight: bold; color: #2563eb;">AI Pharma Guard</div>';
$html .= '<h1 style="font-size: 16px; margin: 8px 0 4px 0;">Prontuário Farmacêutico</h1>';
$html .= '<div style="color: #64748b; font-size: 10px;">Data de geração: ' . h($dataGeracao) . '</div>';
$html .= '</div>';

// Dados do paciente
$html .= '<p><strong>Nome completo:</strong> ' . h($p['nome']) . '</p>';
$html .= '<p><strong>Idade:</strong> ' . h($p['idade']) . ' &nbsp; <strong>Sexo:</strong> ' . h(txt($p['sexo'])) . '</p>';
$html .= '<p><strong>Data de cadastro:</strong> ' . h($dataCadastro) . '</p>';
$html .= '<p style="margin-bottom: 24px;">&nbsp;</p>';

// Seções com título destacado
$html .= '<p style="font-size: 12px; font-weight: bold; color: #1e3a8a; margin-bottom: 6px;">Doenças / Condições</p>';
$html .= '<p style="margin-bottom: 16px;">' . nl2br(h(txt($p['doencas']))) . '</p>';

$html .= '<p style="font-size: 12px; font-weight: bold; color: #1e3a8a; margin-bottom: 6px;">Medicamentos em uso</p>';
$html .= '<p style="margin-bottom: 16px;">' . nl2br(h(txt($p['medicamentos_usados']))) . '</p>';

$html .= '<p style="font-size: 12px; font-weight: bold; color: #1e3a8a; margin-bottom: 6px;">Alergias conhecidas</p>';
if ($temAlergias) {
    $html .= '<p style="margin-bottom: 16px; background: #fef2f2; border: 1px solid #dc2626; padding: 10px; color: #b91c1c;">' . nl2br(h($p['alergias'])) . '</p>';
} else {
    $html .= '<p style="margin-bottom: 16px;">' . h(txt($p['alergias'])) . '</p>';
}

$html .= '<p style="font-size: 12px; font-weight: bold; color: #1e3a8a; margin-bottom: 6px;">Histórico clínico</p>';
$html .= '<p style="margin-bottom: 16px;">' . nl2br(h(txt($p['historico_clinico']))) . '</p>';

$html .= '<p style="font-size: 12px; font-weight: bold; color: #1e3a8a; margin-bottom: 6px;">Observações farmacêuticas</p>';
$html .= '<p style="margin-bottom: 24px;">' . nl2br(h(txt($p['observacoes']))) . '</p>';

// Rodapé
$html .= '<div style="border-top: 1px solid #e2e8f0; padding-top: 12px; margin-top: 24px; color: #64748b; font-size: 10px;">';
$html .= 'Documento gerado pelo AI Pharma Guard &nbsp;|&nbsp; ' . h($dataGeracao);
$html .= '</div>';

$html .= '</body></html>';

try {
    $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
} catch (Exception $e) {
    sendJsonError('Erro ao gerar PDF.', 500);
}

$base = 'prontuario_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $p['nome']) . '_' . date('Y-m-d');
$nomeArquivo = substr($base, 0, 115) . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
echo $dompdf->output();
