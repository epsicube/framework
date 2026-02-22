<?php

declare(strict_types=1);

namespace Epsicube\Support\Modules;

use Epsicube\Support\Enums\ConditionState;

class Supports
{
    /** @var Support[] */
    public array $supports = [];

    public function add(Support ...$supports): static
    {
        $this->supports = array_merge($this->supports, $supports);

        return $this;
    }

    public function resolve(): array
    {
        return array_filter(
            array_map(fn (Support $support) => $support->resolve(), $this->supports)
        );
    }

    public function execute(): void
    {
        foreach ($this->resolve() as $supportCallback) {
            app()->call($supportCallback);
        }
    }

    public function check(): array
    {
        $results = [];
        $allValid = true;

        foreach ($this->supports as $support) {
            $state = $support->condition->run();

            if ($state === ConditionState::INVALID) {
                $allValid = false;
            }

            $results[] = [
                'name'    => $support->condition->name(),
                'group'   => $support->condition->group(),
                'status'  => $state->name,
                'message' => $state === ConditionState::VALID
                    ? $support->condition->successMessage()
                    : $support->condition->failMessage(),
            ];
        }

        return [
            'valid'   => $allValid,
            'results' => $results,
        ];
    }
}
