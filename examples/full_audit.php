<?php

/**
 * Exemplo de Auditoria Completa
 */

require_once __DIR__ . '/../vendor/autoload.php';

use AiVoPro\Integrity\IntegrityManager;

// Configuração
$apiUrl = getenv('API_URL') ?: 'https://api.28facil.com.br';
$apiKey = getenv('API_KEY') ?: null;

echo "=== AUDITORIA COMPLETA 28FÁCIL API ===\n";
echo "URL: {$apiUrl}\n";
echo "API Key: " . ($apiKey ? 'Configurada' : 'Não configurada') . "\n\n";

// Criar manager
$manager = new IntegrityManager(
    apiUrl: $apiUrl,
    apiKey: $apiKey,
    timeout: 10.0,
    throwExceptions: false
);

// Executar auditoria
echo "Executando checagens...\n\n";
$report = $manager->audit();

// Status Geral
echo "=== RESULTADO GERAL ===\n\n";
echo "Status: " . strtoupper($report->getStatus()) . "\n";
echo "Saúde: " . ($report->isHealthy() ? '✓ Saudável' : '✗ Com problemas') . "\n";
echo "Versão: " . ($report->getVersion() ?? 'N/A') . "\n";
echo "Tempo Total: " . $report->getResponseTime() . "ms\n";
echo "Data/Hora: " . $report->getTimestamp()->format('Y-m-d H:i:s') . "\n\n";

// Checagens Individuais
echo "=== CHECAGENS INDIVIDUAIS ===\n\n";

foreach ($report->getChecks() as $check) {
    $icon = $check->isPassed() ? '✓' : '✗';
    $status = $check->isPassed() ? 'PASSOU' : 'FALHOU';
    
    echo "[{$icon}] " . strtoupper($check->getName()) . " - {$status}\n";
    echo "    Mensagem: " . $check->getMessage() . "\n";
    
    if ($check->getDuration()) {
        echo "    Duração: " . $check->getDuration() . "ms\n";
    }
    
    $data = $check->getData();
    if (!empty($data)) {
        echo "    Dados: " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
    
    echo "\n";
}

// Exportar JSON
echo "=== JSON EXPORT ===\n\n";
echo $report->toJson() . "\n\n";

// Status de saída
exit($report->isHealthy() ? 0 : 1);
