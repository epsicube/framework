<?php

declare(strict_types=1);

namespace Epsicube\Support\Modules\Conditions;

use Closure;
use Epsicube\Support\Concerns\Condition;

class ClosureCondition extends Condition
{
    /**
     * @param  Closure():bool  $checkCallback
     */
    public function __construct(
        private readonly string $name,
        private readonly Closure $checkCallback,
        private readonly ?string $successMessage = null,
        private readonly ?string $failMessage = null,
        private readonly string $group = 'Others'
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function group(): string
    {
        return $this->group;
    }

    protected function check(): bool
    {
        return ($this->checkCallback)();
    }

    public function successMessage(): ?string
    {
        return $this->successMessage;
    }

    public function failMessage(): ?string
    {
        return $this->failMessage;
    }
}
