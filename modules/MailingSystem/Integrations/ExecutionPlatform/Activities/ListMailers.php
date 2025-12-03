<?php

declare(strict_types=1);

namespace UniGaleModules\MailingSystem\Integrations\ExecutionPlatform\Activities;

use Illuminate\JsonSchema\JsonSchema;
use UniGaleModules\ExecutionPlatform\Contracts\Activity;
use UniGaleModules\MailingSystem\Contracts\Mailer;
use UniGaleModules\MailingSystem\Facades\Mailers;

class ListMailers implements Activity
{
    /**
     * {@inheritDoc}
     */
    public function identifier(): string
    {
        return 'unigale-mail::list-mailers';
    }

    public function label(): string
    {
        return __('List Email Mailers');
    }

    public function description(): string
    {
        return __('Return a list of registered email mailers and provide details about them.');
    }

    public static function make(): static
    {
        return new static;
    }

    // TODO custom schema module
    public function inputSchema(): array
    {
        return [];
    }

    public function handle(array $inputs = []): array
    {

        return [
            'mailers' => array_values(array_map(fn (Mailer $m) => [
                'identifier' => $m->identifier(),
                'name'       => $m->label(),
            ], Mailers::all())),
        ];
    }

    // TODO custom schema module
    public function outputSchema(): array
    {
        return [
            'mailers' => JsonSchema::array()->items(JsonSchema::object([
                'identifier' => JsonSchema::string()->required(),
                'name'       => JsonSchema::string()->required(),
            ])),
        ];
    }
}
