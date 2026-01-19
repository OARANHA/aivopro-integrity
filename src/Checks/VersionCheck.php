<?php

declare(strict_types=1);

namespace AiVoPro\Integrity\Checks;

use AiVoPro\Integrity\Reports\Check;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\SimpleCache\CacheInterface;

class VersionCheck
{
    private const CACHE_KEY = '28facil_api_version';
    private const CACHE_TTL = 3600; // 1 hora

    public function __construct(
        private Client $client,
        private string $apiUrl,
        private ?CacheInterface $cache = null
    ) {
    }

    public function execute(): Check
    {
        $startTime = microtime(true);

        // Tentar cache primeiro
        if ($this->cache) {
            try {
                $cached = $this->cache->get(self::CACHE_KEY);
                if ($cached) {
                    $duration = (microtime(true) - $startTime) * 1000;
                    return new Check(
                        name: 'version',
                        passed: true,
                        message: sprintf('Versão %s (cached)', $cached['version']),
                        data: array_merge($cached, ['cached' => true]),
                        duration: $duration
                    );
                }
            } catch (\Throwable $e) {
                // Ignora erros de cache
            }
        }

        try {
            $response = $this->client->get('/version');
            $duration = (microtime(true) - $startTime) * 1000;

            if ($response->getStatusCode() !== 200) {
                // Tentar endpoint alternativo
                $response = $this->client->get('/');
            }

            $body = json_decode($response->getBody()->getContents(), true);
            $version = $body['version'] ?? $body['api_version'] ?? 'unknown';

            $data = [
                'version' => $version,
                'api_name' => $body['name'] ?? '28Fácil API',
                'environment' => $body['environment'] ?? 'production',
            ];

            // Salvar no cache
            if ($this->cache && $version !== 'unknown') {
                try {
                    $this->cache->set(self::CACHE_KEY, $data, self::CACHE_TTL);
                } catch (\Throwable $e) {
                    // Ignora erros de cache
                }
            }

            return new Check(
                name: 'version',
                passed: true,
                message: sprintf('Versão %s detectada', $version),
                data: $data,
                duration: $duration
            );
        } catch (GuzzleException $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            return new Check(
                name: 'version',
                passed: false,
                message: sprintf('Não foi possível obter versão: %s', $e->getMessage()),
                data: ['error' => $e->getMessage()],
                duration: $duration
            );
        }
    }
}