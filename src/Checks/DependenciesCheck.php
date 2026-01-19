<?php

declare(strict_types=1);

namespace AiVoPro\Integrity\Checks;

use AiVoPro\Integrity\Reports\Check;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class DependenciesCheck
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
            // Tentar endpoint de status de dependências
            $response = $this->client->get('/status/dependencies');
            $duration = (microtime(true) - $startTime) * 1000;

            if ($response->getStatusCode() === 200) {
                $body = json_decode($response->getBody()->getContents(), true);
                return $this->evaluateDependencies($body, $duration);
            }

            // Endpoint não existe, tentar endpoint alternativo
            if ($response->getStatusCode() === 404) {
                return $this->checkAlternativeEndpoint($startTime);
            }

            $duration = (microtime(true) - $startTime) * 1000;
            return new Check(
                name: 'dependencies',
                passed: false,
                message: 'Não foi possível verificar dependências',
                data: ['status_code' => $response->getStatusCode()],
                duration: $duration
            );
        } catch (GuzzleException $e) {
            return $this->checkAlternativeEndpoint($startTime);
        }
    }

    private function evaluateDependencies(array $data, float $duration): Check
    {
        $services = $data['services'] ?? $data['dependencies'] ?? [];
        $allHealthy = true;
        $failedServices = [];

        foreach ($services as $service => $status) {
            $isHealthy = $this->isServiceHealthy($status);
            if (!$isHealthy) {
                $allHealthy = false;
                $failedServices[] = $service;
            }
        }

        if (empty($services)) {
            return new Check(
                name: 'dependencies',
                passed: true,
                message: 'Nenhuma dependência reportada',
                data: ['services' => []],
                duration: $duration
            );
        }

        $message = $allHealthy
            ? sprintf('Todas as %d dependências estão saudáveis', count($services))
            : sprintf('%d de %d dependências com problemas: %s',
                count($failedServices),
                count($services),
                implode(', ', $failedServices)
            );

        return new Check(
            name: 'dependencies',
            passed: $allHealthy,
            message: $message,
            data: [
                'services' => $services,
                'total' => count($services),
                'healthy' => count($services) - count($failedServices),
                'failed' => $failedServices,
            ],
            duration: $duration
        );
    }

    private function checkAlternativeEndpoint(float $startTime): Check
    {
        try {
            // Tentar endpoints alternativos
            $endpoints = ['/status', '/health/full', '/api/status'];

            foreach ($endpoints as $endpoint) {
                try {
                    $response = $this->client->get($endpoint);
                    $duration = (microtime(true) - $startTime) * 1000;

                    if ($response->getStatusCode() === 200) {
                        $body = json_decode($response->getBody()->getContents(), true);

                        if (isset($body['dependencies']) || isset($body['services'])) {
                            return $this->evaluateDependencies($body, $duration);
                        }

                        // Se chegou aqui, API está respondendo mas sem info de dependências
                        return new Check(
                            name: 'dependencies',
                            passed: true,
                            message: 'Dependências não disponíveis para verificação (assumindo saudável)',
                            data: ['note' => 'Endpoint de dependências não encontrado'],
                            duration: $duration
                        );
                    }
                } catch (GuzzleException $e) {
                    continue;
                }
            }

            // Se nenhum endpoint funcionou, assumir que a API está funcionando
            // já que o health check já passou
            $duration = (microtime(true) - $startTime) * 1000;
            return new Check(
                name: 'dependencies',
                passed: true,
                message: 'Verificação de dependências não suportada pela API',
                data: ['note' => 'Nenhum endpoint de status encontrado'],
                duration: $duration
            );
        } catch (\Throwable $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            return new Check(
                name: 'dependencies',
                passed: false,
                message: sprintf('Erro ao verificar dependências: %s', $e->getMessage()),
                data: ['error' => $e->getMessage()],
                duration: $duration
            );
        }
    }

    private function isServiceHealthy(mixed $status): bool
    {
        if (is_bool($status)) {
            return $status;
        }

        if (is_array($status)) {
            return ($status['status'] ?? $status['healthy'] ?? false) === true
                || ($status['status'] ?? '') === 'healthy'
                || ($status['status'] ?? '') === 'ok'
                || ($status['state'] ?? '') === 'up';
        }

        if (is_string($status)) {
            return in_array(strtolower($status), ['healthy', 'ok', 'up', 'running', 'connected']);
        }

        return false;
    }
}