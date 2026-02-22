<?php

declare(strict_types=1);

namespace Epsicube\Support\Concerns;

use Closure;
use Epsicube\Support\Enums\ConditionState;

abstract class Condition
{
    protected array $skips = [];

    public ?ConditionState $resultState = null;

    private ?string $resultMessage = null;

    abstract public function name(): string;

    abstract protected function check(): bool;

    public function group(): string
    {
        return 'Others';
    }

    public function successMessage(): ?string
    {
        return null;
    }

    public function failMessage(): ?string
    {
        return null;
    }

    public function skipMessage(): ?string
    {
        return null;
    }

    public function run(): ConditionState
    {
        if ($this->resultState !== null) {
            return $this->resultState;
        }

        foreach ($this->skips as $skip) {
            if ($skip()) {
                $this->resultState = ConditionState::SKIPPED;
                $this->resultMessage = $this->skipMessage();

                return $this->resultState;
            }
        }

        if ($this->check()) {
            $this->resultState = ConditionState::VALID;
            $this->resultMessage = $this->successMessage();
        } else {
            $this->resultState = ConditionState::INVALID;
            $this->resultMessage = $this->failMessage();
        }

        return $this->resultState;
    }

    public function getMessage(): ?string
    {
        return $this->resultMessage;
    }

    public function skipWhen(Closure $callback): static
    {
        $this->skips[] = $callback;

        return $this;
    }

    public function when(Closure $callback): static
    {
        return $this->skipWhen(fn () => ! $callback());
    }
}
