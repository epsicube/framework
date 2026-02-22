<?php

declare(strict_types=1);

namespace Epsicube\Support\Modules;

use Closure;
use Composer\Semver\Semver;
use Epsicube\Support\Concerns\Condition as ConditionConcern;
use Epsicube\Support\Enums\ConditionState;
use Epsicube\Support\Enums\ModuleCondition;
use Epsicube\Support\Enums\ModuleStatus;
use Epsicube\Support\Facades\Modules;
use Epsicube\Support\Modules\Conditions\Callback;
use stdClass;

readonly class Support
{
    public function __construct(
        public ConditionConcern $condition,
        public ?Closure $whenPass = null,
        public ?Closure $whenFail = null,
        public ?Closure $whenSkipped = null,
    ) {}

    public static function for(
        ConditionConcern $condition,
        ?callable $whenPass = null,
        ?callable $whenFail = null,
        ?callable $whenSkipped = null,
    ): static {
        return new static(
            $condition,
            $whenPass ? $whenPass(...) : null,
            $whenFail ? $whenFail(...) : null,
            $whenSkipped ? $whenSkipped(...) : null,
        );
    }

    public static function forModule(
        string|array $identifiers,
        ?callable $whenPass = null,
        ?callable $whenFail = null,
        ModuleCondition $state = ModuleCondition::ACTIVE,
    ): static {
        $normalized = [];
        foreach ((array) $identifiers as $key => $value) {
            $normalized[is_int($key) ? $value : $key] = is_int($key) ? '*' : $value;
        }
        $names = array_map(function ($id) {
            $module = Modules::safeGet($id);

            return $module?->identity->name ?? $id;
        }, array_keys($normalized));

        $label = implode(', ', $names);
        $report = new stdClass;
        $report->errors = [];

        $condition = new Callback(
            callback: function () use ($normalized, $state, $report): bool {
                $report->errors = [];

                foreach ($normalized as $id => $version) {
                    $module = Modules::safeGet($id);
                    $moduleErrors = [];

                    $isValidState = match ($state) {
                        ModuleCondition::PRESENT => $module !== null,
                        ModuleCondition::ABSENT  => $module === null,
                        ModuleCondition::ENABLED,
                        ModuleCondition::ACTIVE   => $module !== null && $module->status === ModuleStatus::ENABLED,
                        ModuleCondition::DISABLED => $module !== null && $module->status === ModuleStatus::DISABLED,
                        ModuleCondition::INACTIVE => $module === null || $module->status === ModuleStatus::DISABLED,
                    };

                    if (! $isValidState) {
                        $moduleErrors[] = "is not {$state->name}";
                    }

                    if ($module !== null && $version !== '*') {
                        $actual = $module->version;
                        if (! Semver::satisfies($actual, $version)) {
                            $moduleErrors[] = "version [{$actual}] does not satisfy [{$version}]";
                        }
                    }

                    if (! empty($moduleErrors)) {
                        $report->errors[] = "module [{$id}] ".implode(' and ', $moduleErrors);
                    }
                }

                return empty($report->errors);
            },
            name: "Module {$label}",
            group: 'Modules',
            successMessage: "All required modules [{$label}] are valid.",
            failMessage: fn () => ! empty($report->errors)
                ? 'Condition failed: '.implode(', ', $report->errors).'.'
                : "One or more modules in [{$label}] are invalid."
        );

        return new static(
            $condition,
            $whenPass ? $whenPass(...) : null,
            $whenFail ? $whenFail(...) : null
        );
    }

    public function resolve(): ?Closure
    {
        return match ($this->condition->run()) {
            ConditionState::VALID   => $this->whenPass,
            ConditionState::INVALID => $this->whenFail,
            ConditionState::SKIPPED => $this->whenSkipped,
        };
    }
}
