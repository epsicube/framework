<?php

declare(strict_types=1);

namespace Epsicube\Support\Modules\Conditions;

use Composer\Semver\Semver;
use Epsicube\Support\Concerns\Condition;
use UnexpectedValueException;

class PhpVersion extends Condition
{
    private string $currentVersion;

    private bool $invalidConstraint = false;

    /**
     * @param  string  $constraints  Exemple: '^8.2', '~8.1.0', '>=8.2'
     */
    public function __construct(private readonly string $constraints)
    {
        $this->currentVersion = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION;
    }

    public function group(): string
    {
        return 'Environment';
    }

    public function name(): string
    {
        return "PHP [{$this->constraints}]";
    }

    public function check(): bool
    {
        try {
            return Semver::satisfies($this->currentVersion, $this->constraints);
        } catch (UnexpectedValueException) {
            $this->invalidConstraint = true;

            return false;
        }
    }

    public function successMessage(): string
    {
        return "PHP version '{$this->currentVersion}' matches requirement '{$this->constraints}'";
    }

    public function failMessage(): string
    {
        if ($this->invalidConstraint) {
            return "Invalid PHP version constraint provided: '{$this->constraints}'.";
        }

        return "PHP version {$this->constraints} is required. Current version is {$this->currentVersion}.";
    }
}
