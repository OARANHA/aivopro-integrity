<?php

declare(strict_types=1);

namespace AiVoPro\Integrity\Reports;

class Check
{
    public function __construct(
        private string $name,
        private bool $passed,
        private string $message,
        private array $data = [],
        private ?float $duration = null
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isPassed(): bool
    {
        return $this->passed;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getDuration(): ?float
    {
        return $this->duration;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'passed' => $this->passed,
            'message' => $this->message,
            'data' => $this->data,
            'duration_ms' => $this->duration ? round($this->duration, 2) : null,
        ];
    }
}