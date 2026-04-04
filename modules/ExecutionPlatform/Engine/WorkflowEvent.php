<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Engine;

use DateTimeImmutable;
use DateTimeInterface;
use EpsicubeModules\ExecutionPlatform\Enum\WorkflowEventType;
use JsonSerializable;

/**
 * @internal
 */
class WorkflowEvent implements JsonSerializable
{
    public function __construct(
        public WorkflowEventType $type,
        public mixed $data = null,
        public ?string $name = null,
        public ?int $callIndex = null,
        public ?int $tick = null,
        public ?DateTimeImmutable $createdAt = null
    ) {
        $this->createdAt ??= new DateTimeImmutable;
    }

    public function jsonSerialize(): array
    {
        return [
            'type'       => $this->type->value,
            'data'       => $this->data,
            'name'       => $this->name,
            'callIndex'  => $this->callIndex,
            'tick'       => $this->tick,
            'created_at' => $this->createdAt->format(DateTimeInterface::ATOM),
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            WorkflowEventType::from($data['type']),
            $data['data'],
            $data['name'] ?? null,
            $data['callIndex'] ?? null,
            $data['tick'] ?? null,
            isset($data['created_at']) ? new DateTimeImmutable($data['created_at']) : null
        );
    }
}
