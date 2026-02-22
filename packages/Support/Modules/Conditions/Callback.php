<?php

declare(strict_types=1);

namespace Epsicube\Support\Modules\Conditions;

use Closure;
use Epsicube\Support\Concerns\Condition;

class Callback extends Condition
{
    /**
     * @param  Closure(): bool  $callback
     * @param  string|Closure(): string  $successMessage
     * @param  string|Closure(): string  $failMessage
     */
    public function __construct(
        private readonly Closure $callback,
        private readonly string $name = 'Custom condition',
        private readonly string $group = 'Others',
        private readonly string|Closure $successMessage = 'Condition passed.',
        private readonly string|Closure $failMessage = 'Condition failed.',
    ) {}

    public function group(): string
    {
        return $this->group;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function check(): bool
    {
        return (bool) ($this->callback)();
    }

    public function successMessage(): string
    {
        return $this->resolveMessage($this->successMessage);
    }

    public function failMessage(): string
    {
        return $this->resolveMessage($this->failMessage);
    }

    private function resolveMessage(string|Closure $message): string
    {
        return value($message);
    }
}
