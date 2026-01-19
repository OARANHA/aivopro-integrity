<?php

declare(strict_types=1);

namespace AiVoPro\Integrity\Reports;

use DateTime;

class AuditReport
{
    private DateTime $timestamp;
    private array $checks;
    private float $duration;

    public function __construct(array $checks, float $duration)
    {
        $this->checks = $checks;
        $this->duration = $duration;
        $this->timestamp = new DateTime();
    }

    public function isHealthy(): bool
    {
        foreach ($this->checks as $check) {
            if (!$check->isPassed()) {
                return false;
            }
        }
        return !empty($this->checks);
    }

    public function getStatus(): string
    {
        if (empty($this->checks)) {
            return 'unknown';
        }

        $failed = 0;
        $total = count($this->checks);

        foreach ($this->checks as $check) {
            if (!$check->isPassed()) {
                $failed++;
            }
        }

        if ($failed === 0) {
            return 'healthy';
        } elseif ($failed < $total) {
            return 'degraded';
        } else {
            return 'down';
        }
    }

    public function getVersion(): ?string
    {
        foreach ($this->checks as $check) {
            if ($check->getName() === 'version' && $check->isPassed()) {
                $data = $check->getData();
                return $data['version'] ?? null;
            }
        }
        return null;
    }

    public function getResponseTime(): float
    {
        return round($this->duration, 2);
    }

    public function getTimestamp(): DateTime
    {
        return $this->timestamp;
    }

    public function getChecks(): array
    {
        return $this->checks;
    }

    public function isSuccess(): bool
    {
        return $this->isHealthy();
    }

    public function getErrorMessage(): ?string
    {
        $errors = [];
        foreach ($this->checks as $check) {
            if (!$check->isPassed()) {
                $errors[] = sprintf('%s: %s', $check->getName(), $check->getMessage());
            }
        }
        return empty($errors) ? null : implode('; ', $errors);
    }

    public function toArray(): array
    {
        return [
            'status' => $this->getStatus(),
            'healthy' => $this->isHealthy(),
            'version' => $this->getVersion(),
            'response_time_ms' => $this->getResponseTime(),
            'timestamp' => $this->timestamp->format('c'),
            'checks' => array_map(fn($check) => $check->toArray(), $this->checks),
        ];
    }

    public function toJson(int $flags = JSON_PRETTY_PRINT): string
    {
        return json_encode($this->toArray(), $flags);
    }
}