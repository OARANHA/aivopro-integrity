<?php

declare(strict_types=1);

namespace AiVoPro\Integrity\Checks;

use AiVoPro\Integrity\Reports\Check;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class AuthenticationCheck
{
    public function __construct(
        private Client $client,
        private string $apiUrl,
        private string $apiKey
    ) {
    }

    public function execute(): Check
    {
        $startTime = microtime(true);

        try {
            // Tentar endpoint de validação
            $response = $this->client->get('/auth/validate', [
                'headers' => [
                    'X-API-Key' => $this->apiKey,
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
            ]);

            $duration = (microtime(true) - $startTime) * 1000;
            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            if ($statusCode === 200) {
                return new Check(
                    name: 'authentication',
                    passed: true,
                    message: 'Credenciais válidas',
                    data: [
                        'valid' => true,
                        'key_prefix' => substr($this->apiKey, 0, 8) . '...',
                        'user' => $body['user'] ?? null,
                        'permissions' => $body['permissions'] ?? [],
                    ],
                    duration: $duration
                );
            }

            if ($statusCode === 401 || $statusCode === 403) {
                return new Check(
                    name: 'authentication',
                    passed: false,
                    message: 'Credenciais inválidas ou sem permissão',
                    data: [
                        'valid' => false,
                        'status_code' => $statusCode,
                    ],
                    duration: $duration
                );
            }

            // Se o endpoint não existe, tentar endpoint protegido genérico
            if ($statusCode === 404) {
                return $this->validateWithProtectedEndpoint($startTime);
            }

            return new Check(
                name: 'authentication',
                passed: false,
                message: sprintf('Resposta inesperada: %d', $statusCode),
                data: ['status_code' => $statusCode],
                duration: $duration
            );
        } catch (GuzzleException $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            $statusCode = $e->getCode();

            if ($statusCode === 401 || $statusCode === 403) {
                return new Check(
                    name: 'authentication',
                    passed: false,
                    message: 'Credenciais inválidas',
                    data: ['valid' => false, 'error' => $e->getMessage()],
                    duration: $duration
                );
            }

            return new Check(
                name: 'authentication',
                passed: false,
                message: sprintf('Erro na validação: %s', $e->getMessage()),
                data: ['error' => $e->getMessage()],
                duration: $duration
            );
        }
    }

    private function validateWithProtectedEndpoint(float $startTime): Check
    {
        try {
            // Tentar endpoints protegidos comuns
            $endpoints = ['/api/user', '/api/me', '/user/profile'];

            foreach ($endpoints as $endpoint) {
                try {
                    $response = $this->client->get($endpoint, [
                        'headers' => ['X-API-Key' => $this->apiKey],
                    ]);

                    $duration = (microtime(true) - $startTime) * 1000;

                    if ($response->getStatusCode() === 200) {
                        return new Check(
                            name: 'authentication',
                            passed: true,
                            message: 'Credenciais válidas (verificado via endpoint protegido)',
                            data: ['valid' => true, 'method' => 'protected_endpoint'],
                            duration: $duration
                        );
                    }
                } catch (GuzzleException $e) {
                    if ($e->getCode() === 401) {
                        $duration = (microtime(true) - $startTime) * 1000;
                        return new Check(
                            name: 'authentication',
                            passed: false,
                            message: 'Credenciais inválidas',
                            data: ['valid' => false],
                            duration: $duration
                        );
                    }
                    continue;
                }
            }

            $duration = (microtime(true) - $startTime) * 1000;
            return new Check(
                name: 'authentication',
                passed: false,
                message: 'Não foi possível validar credenciais',
                data: ['error' => 'No validation endpoint available'],
                duration: $duration
            );
        } catch (\Throwable $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            return new Check(
                name: 'authentication',
                passed: false,
                message: sprintf('Erro: %s', $e->getMessage()),
                data: ['error' => $e->getMessage()],
                duration: $duration
            );
        }
    }
}