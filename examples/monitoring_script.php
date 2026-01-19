<?php

/**
 * Script de Monitoramento Contínuo
 * Use com cron job: */5 * * * * php monitoring_script.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use AiVoPro\Integrity\IntegrityManager;

// Configurações
$config = [
    'api_url' => getenv('API_URL') ?: 'https://api.28facil.com.br',
    'api_key' => getenv('API_KEY'),
    'alert_email' => getenv('ALERT_EMAIL') ?: 'admin@28facil.com.br',
    'log_file' => __DIR__ . '/../var/monitoring.log',
    'alert_file' => __DIR__ . '/../var/last_alert.txt',
    'alert_cooldown' => 300, // 5 minutos entre alertas
];

// Função de log
function logMessage(string $message, string $logFile): void
{
    $timestamp = date('Y-m-d H:i:s');
    $line = "[{$timestamp}] {$message}\n";
    
    @mkdir(dirname($logFile), 0755, true);
    file_put_contents($logFile, $line, FILE_APPEND);
}

// Função de alerta
function sendAlert(string $email, string $subject, string $message, array $config): void
{
    // Verificar cooldown
    if (file_exists($config['alert_file'])) {
        $lastAlert = (int) file_get_contents($config['alert_file']);
        if (time() - $lastAlert < $config['alert_cooldown']) {
            return; // Dentro do cooldown, não enviar
        }
    }
    
    // Enviar email (você pode substituir por webhook, Slack, etc)
    $headers = "From: monitoring@28facil.com.br\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    if (@mail($email, $subject, $message, $headers)) {
        file_put_contents($config['alert_file'], (string) time());
        logMessage("Alerta enviado para {$email}", $config['log_file']);
    }
}

// Executar monitoramento
try {
    $manager = new IntegrityManager(
        apiUrl: $config['api_url'],
        apiKey: $config['api_key'],
        timeout: 10.0,
        throwExceptions: false
    );
    
    $report = $manager->audit();
    $status = $report->getStatus();
    
    logMessage("Status: {$status} | Tempo: {$report->getResponseTime()}ms", $config['log_file']);
    
    // Se não estiver saudável, enviar alerta
    if (!$report->isHealthy()) {
        $subject = "⚠️ [28Fácil] API com status: {$status}";
        $message = "A API 28Fácil está com problemas!\n\n";
        $message .= "Status: {$status}\n";
        $message .= "Hora: " . $report->getTimestamp()->format('Y-m-d H:i:s') . "\n\n";
        $message .= "Checagens com falha:\n";
        
        foreach ($report->getChecks() as $check) {
            if (!$check->isPassed()) {
                $message .= "- {$check->getName()}: {$check->getMessage()}\n";
            }
        }
        
        $message .= "\nJSON completo:\n" . $report->toJson();
        
        sendAlert($config['alert_email'], $subject, $message, $config);
    }
    
    // Retornar 0 se saudável, 1 se não
    exit($report->isHealthy() ? 0 : 1);
    
} catch (\Throwable $e) {
    $errorMsg = "Erro crítico no monitoramento: " . $e->getMessage();
    logMessage($errorMsg, $config['log_file']);
    
    sendAlert(
        $config['alert_email'],
        "⚠️ [28Fácil] Erro no monitoramento",
        $errorMsg . "\n\n" . $e->getTraceAsString(),
        $config
    );
    
    exit(2);
}
