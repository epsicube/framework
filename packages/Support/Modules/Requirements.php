<?php

declare(strict_types=1);

namespace Epsicube\Support\Modules;

use Epsicube\Support\Concerns\Condition;
use Epsicube\Support\Enums\ConditionState;

class Requirements
{
    /** @var Condition[] */
    public array $conditions = [];

    public function add(Condition ...$conditions): self
    {
        $this->conditions = array_merge($this->conditions, $conditions);

        return $this;
    }

    public function passes(): bool
    {
        $valid = true;
        foreach ($this->conditions as $condition) {
            if ($condition->run() === ConditionState::INVALID) {
                $valid = false;
            }
        }

        return $valid;
    }

    public function check(): array
    {
        return [
            'valid'      => $this->passes(),
            'conditions' => $this->conditions,
        ];
    }
}
