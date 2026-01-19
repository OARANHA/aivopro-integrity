<?php

declare(strict_types=1);

namespace AiVoPro\Integrity;

use AiVoPro\Integrity\Checks\AuthenticationCheck;
use AiVoPro\Integrity\Checks\DependenciesCheck;
use AiVoPro\Integrity\Checks\HealthCheck;
use AiVoPro\Integrity\Checks\PerformanceCheck;
use AiVoPro\Integrity\Checks\VersionCheck;
use AiVoPro\Integrity\Reports\AuditReport;
use AiVoPro\Integrity\Reports\Check;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

class IntegrityManager
{
    private Client $client;
    private ?CacheInterface $cache = null;
    private string $apiUrl;
    private ?string $apiKey;
    private bool $throwExceptions;
    private ?string $lastError = null;

    public function __construct(
        string $apiUrl = 'https://api.28facil.com.br',
        ?string $apiKey = null,
        float $timeout = 5.0,
        int $retries = 2,
        bool $throwExceptions = false,
        ?CacheInterface $cache = null
    ) {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->apiKey = $apiKey;
        $this->throwExceptions = $throwExceptions;

        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'timeout' => $timeout,
            'connect_timeout' => $timeout / 2,
            'http_errors' => false,
            'headers' => array_filter([
                'User-Agent' => '28Facil-Integrity/1.0',
                'Accept' => 'application/json',
                'X-API-Key' => $this->apiKey,
            ]),
        ]);

        if ($cache === null) {
            $adapter = new FilesystemAdapter('28facil_integrity', 300);
            $this->cache = new Psr16Cache($adapter);
        } else {
            $this->cache = $cache;
        }
    }

    /**
     * Executa auditoria completa do sistema
     */
    public function audit(): AuditReport
    {
        $startTime = microtime(true);
        $checks = [];

        try {
            // Health Check
            $healthCheck = new HealthCheck($this->client, $this->apiUrl);
            $checks[] = $healthCheck->execute();

            // Version Check
            $versionCheck = new VersionCheck($this->client, $this->apiUrl, $this->cache);
            $checks[] = $versionCheck->execute();

            // Performance Check
            $perfCheck = new PerformanceCheck($this->client, $this->apiUrl);
            $checks[] = $perfCheck->execute();

            // Authentication Check (se API key fornecida)
            if ($this->apiKey) {
                $authCheck = new AuthenticationCheck($this->client, $this->apiUrl, $this->apiKey);
                $checks[] = $authCheck->execute();
            }

            // Dependencies Check
            $depsCheck = new DependenciesCheck($this->client, $this->apiUrl);
            $checks[] = $depsCheck->execute();
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            if ($this->throwExceptions) {
                throw $e;
            }
        }

        $duration = (microtime(true) - $startTime) * 1000;

        return new AuditReport($checks, $duration);
    }

    /**
     * Verificação rápida de saúde
     */
    public function isHealthy(): bool
    {
        try {
            $check = new HealthCheck($this->client, $this->apiUrl);
            $result = $check->execute();
            return $result->isPassed();
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Verifica versão da API
     */
    public function checkVersion(): Check
    {
        $check = new VersionCheck($this->client, $this->apiUrl, $this->cache);
        return $check->execute();
    }

    /**
     * Valida autenticação
     */
    public function checkAuthentication(?string $apiKey = null): Check
    {
        $key = $apiKey ?? $this->apiKey;
        if (!$key) {
            throw new \InvalidArgumentException('API key é obrigatória para checagem de autenticação');
        }

        $check = new AuthenticationCheck($this->client, $this->apiUrl, $key);
        return $check->execute();
    }

    /**
     * Verifica dependências
     */
    public function checkDependencies(): Check
    {
        $check = new DependenciesCheck($this->client, $this->apiUrl);
        return $check->execute();
    }

    /**
     * Verifica performance
     */
    public function checkPerformance(): Check
    {
        $check = new PerformanceCheck($this->client, $this->apiUrl);
        return $check->execute();
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }
}