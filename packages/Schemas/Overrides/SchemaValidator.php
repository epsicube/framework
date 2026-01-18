<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Overrides;

use Illuminate\Validation\Validator;
use Override;

class SchemaValidator extends Validator
{
    /**
     * Custom override to allow validating empty string
     */
    #[Override]
    protected function presentOrRuleIsImplicit($rule, $attribute, $value): bool
    {
        if (is_string($value) && mb_trim($value) === '') {
            return true;
        }

        return parent::presentOrRuleIsImplicit($rule, $attribute, $value);
    }
}
