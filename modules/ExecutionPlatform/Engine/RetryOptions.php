<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Engine;

class RetryOptions
{
    public function __construct(
        public int $maxAttempts = 3,
        public array $nonRetryableExceptions = [],
        public int $initialInterval = 1,
        public float $backoffCoefficient = 2.0,
        public int $maximumInterval = 100,
        public ?int $timeout = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['maxAttempts'] ?? 3,
            $data['nonRetryableExceptions'] ?? [],
            $data['initialInterval'] ?? 1,
            $data['backoffCoefficient'] ?? 2.0,
            $data['maximumInterval'] ?? 100,
            $data['timeout'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'maxAttempts'            => $this->maxAttempts,
            'nonRetryableExceptions' => $this->nonRetryableExceptions,
            'initialInterval'        => $this->initialInterval,
            'backoffCoefficient'     => $this->backoffCoefficient,
            'maximumInterval'        => $this->maximumInterval,
            'timeout'                => $this->timeout,
        ];
    }
}
