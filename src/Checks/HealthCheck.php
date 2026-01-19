<?php

declare(strict_types=1);

namespace AiVoPro\Integrity\Checks;

use AiVoPro\Integrity\Reports\Check;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class HealthCheck
{
    public function __construct(
        private Client $client,
        private string $apiUrl
    ) {
    }

    public function execute(): Check
    {
        $startTime = microtime(true);

        try {
            $response = $this->client->get('/health');
            $duration = (microtime(true) - $startTime) * 1000;

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            if ($statusCode >= 200 && $statusCode < 300) {
                return new Check(
                    name: 'health',
                    passed: true,
                    message: 'API está respondendo normalmente',
                    data: [
                        'status_code' => $statusCode,
                        'response' => $body,
                    ],
                    duration: $duration
                );
            }

            return new Check(
                name: 'health',
                passed: false,
                message: sprintf('API retornou status %d', $statusCode),
                data: ['status_code' => $statusCode],
                duration: $duration
            );
        } catch (GuzzleException $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            return new Check(
                name: 'health',
                passed: false,
                message: sprintf('Falha na conexão: %s', $e->getMessage()),
                data: ['error' => $e->getMessage()],
                duration: $duration
            );
        }
    }
}