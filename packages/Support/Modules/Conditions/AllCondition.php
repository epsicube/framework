<?php

declare(strict_types=1);

namespace Epsicube\Support\Modules\Conditions;

use Epsicube\Support\Concerns\Condition;
use Epsicube\Support\Enums\ConditionState;

class AllCondition extends Condition
{
    private array $conditions;

    private array $failedMessages = [];

    public function __construct(Condition ...$conditions)
    {
        $this->conditions = $conditions;
    }

    public function name(): string
    {
        return '('.implode(' AND ', array_map(fn ($c) => $c->name(), $this->conditions)).')';
    }

    protected function check(): bool
    {
        $this->failedMessages = [];
        $isValid = true;

        foreach ($this->conditions as $condition) {
            $conditionState = $condition->run();
            if ($conditionState !== ConditionState::INVALID) {
                continue;
            }

            $this->failedMessages[] = $condition->getMessage();
            $isValid = false;
        }

        return $isValid;
    }

    public function successMessage(): ?string
    {
        return 'All conditions met: '.implode(', ', array_map(fn ($c) => $c->name(), $this->conditions));
    }

    public function failMessage(): ?string
    {
        return 'Multiple conditions failed: '.implode(' AND ', array_filter($this->failedMessages));
    }
}
