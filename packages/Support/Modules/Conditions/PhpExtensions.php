<?php

declare(strict_types=1);

namespace Epsicube\Support\Modules\Conditions;

use Epsicube\Support\Concerns\Condition;

class PhpExtensions extends Condition
{
    /** @var string[] */
    private array $extensions;

    /** @var string[] */
    private array $missing = [];

    public function __construct(string ...$extensions)
    {
        $this->extensions = $extensions;
    }

    public function group(): string
    {
        return 'Environment';
    }

    public function name(): string
    {
        return 'Extensions ['.implode(', ', $this->extensions).']';
    }

    public function check(): bool
    {
        $this->missing = array_filter(
            $this->extensions,
            fn (string $ext) => ! extension_loaded($ext)
        );

        return empty($this->missing);
    }

    public function successMessage(): string
    {
        $list = implode(', ', $this->extensions);

        return "All required PHP extensions are loaded: [{$list}].";
    }

    public function failMessage(): string
    {
        $missingList = implode(', ', $this->missing);

        return "Missing or disabled PHP extensions: [{$missingList}].";
    }
}
