<?php

declare(strict_types=1);

namespace Epsicube\Support\Modules\Conditions;

use Epsicube\Support\Concerns\Condition;
use Epsicube\Support\Enums\ConditionState;

class AnyCondition extends Condition
{
    private array $conditions;

    private array $failedMessages = [];

    private ?Condition $winningCondition = null;

    public function __construct(Condition ...$conditions)
    {
        $this->conditions = $conditions;
    }

    public function name(): string
    {
        return implode(' OR ', array_map(fn ($c) => $c->name(), $this->conditions));
    }

    protected function check(): bool
    {
        $this->failedMessages = [];
        $this->winningCondition = null;

        foreach ($this->conditions as $condition) {
            $state = $condition->run();

            if ($state === ConditionState::SKIPPED) {
                continue;
            }

            if ($state === ConditionState::VALID) {
                $this->winningCondition = $condition;

                return true;
            }

            $this->failedMessages[] = $condition->getMessage();
        }

        return false;
    }

    public function successMessage(): ?string
    {
        return $this->winningCondition?->getMessage() ?? 'One of the conditions met the requirements.';
    }

    public function failMessage(): ?string
    {
        return 'None of the conditions met: '.implode(' AND ', array_filter($this->failedMessages));
    }
}
