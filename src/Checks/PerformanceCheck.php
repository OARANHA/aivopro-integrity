<?php

declare(strict_types=1);

namespace AiVoPro\Integrity\Checks;

use AiVoPro\Integrity\Reports\Check;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class PerformanceCheck
{
    private const MAX_RESPONSE_TIME_MS = 2000; // 2 segundos
    private const WARNING_THRESHOLD_MS = 1000; // 1 segundo

    public function __construct(
        private Client $client,
        private string $apiUrl
    ) {
    }

    public function execute(): Check
    {
        $measurements = [];

        // Fazer 3 requisições para média
        for ($i = 0; $i < 3; $i++) {
            $startTime = microtime(true);

            try {
                $response = $this->client->get('/health');
                $duration = (microtime(true) - $startTime) * 1000;

                if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                    $measurements[] = $duration;
                }
            } catch (GuzzleException $e) {
                // Continua para próxima medição
                continue;
            }

            // Pequeno delay entre requisições
            usleep(100000); // 100ms
        }

        if (empty($measurements)) {
            return new Check(
                name: 'performance',
                passed: false,
                message: 'Não foi possível medir performance',
                data: ['error' => 'Todas as requisições falharam']
            );
        }

        $avgTime = array_sum($measurements) / count($measurements);
        $minTime = min($measurements);
        $maxTime = max($measurements);

        $status = $this->evaluatePerformance($avgTime);

        return new Check(
            name: 'performance',
            passed: $avgTime < self::MAX_RESPONSE_TIME_MS,
            message: sprintf(
                'Tempo médio de resposta: %.2fms (%s)',
                $avgTime,
                $status
            ),
            data: [
                'average_ms' => round($avgTime, 2),
                'min_ms' => round($minTime, 2),
                'max_ms' => round($maxTime, 2),
                'measurements' => count($measurements),
                'status' => $status,
            ],
            duration: $avgTime
        );
    }

    private function evaluatePerformance(float $avgTime): string
    {
        if ($avgTime < self::WARNING_THRESHOLD_MS) {
            return 'excelente';
        } elseif ($avgTime < self::MAX_RESPONSE_TIME_MS) {
            return 'bom';
        } else {
            return 'lento';
        }
    }
}