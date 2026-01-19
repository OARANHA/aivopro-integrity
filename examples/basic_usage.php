<?php

/**
 * Exemplo Básico de Uso do 28Fácil Integrity Manager
 */

require_once __DIR__ . '/../vendor/autoload.php';

use AiVoPro\Integrity\IntegrityManager;

// Exemplo 1: Health Check Simples
echo "=== HEALTH CHECK SIMPLES ===\n\n";

$manager = new IntegrityManager('https://api.28facil.com.br');

if ($manager->isHealthy()) {
    echo "✓ API está saudável!\n";
} else {
    echo "✗ API com problemas: " . $manager->getLastError() . "\n";
}

echo "\n";

// Exemplo 2: Verificação de Versão
echo "=== VERIFICAÇÃO DE VERSÃO ===\n\n";

$versionCheck = $manager->checkVersion();
if ($versionCheck->isPassed()) {
    $data = $versionCheck->getData();
    echo "✓ " . $versionCheck->getMessage() . "\n";
    echo "   API: {$data['api_name']}\n";
    echo "   Ambiente: {$data['environment']}\n";
} else {
    echo "✗ " . $versionCheck->getMessage() . "\n";
}

echo "\n";

// Exemplo 3: Verificação de Performance
echo "=== VERIFICAÇÃO DE PERFORMANCE ===\n\n";

$perfCheck = $manager->checkPerformance();
if ($perfCheck->isPassed()) {
    $data = $perfCheck->getData();
    echo "✓ " . $perfCheck->getMessage() . "\n";
    echo "   Média: {$data['average_ms']}ms\n";
    echo "   Mínimo: {$data['min_ms']}ms\n";
    echo "   Máximo: {$data['max_ms']}ms\n";
    echo "   Status: {$data['status']}\n";
} else {
    echo "✗ " . $perfCheck->getMessage() . "\n";
}

echo "\n";
