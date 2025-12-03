<?php

declare(strict_types=1);

namespace UniGaleModules\MailingSystem\Integrations\ExecutionPlatform\Activities;

use Illuminate\JsonSchema\JsonSchema;
use UniGaleModules\ExecutionPlatform\Contracts\Activity;
use UniGaleModules\MailingSystem\Contracts\MailTemplate;
use UniGaleModules\MailingSystem\Facades\Templates;

class ListTemplates implements Activity
{
    /**
     * {@inheritDoc}
     */
    public function identifier(): string
    {
        return 'unigale-mail::list-templates';
    }

    public function label(): string
    {
        return __('List Email Templates');
    }

    public function description(): string
    {
        return __('Return a list of registered email templates and provide details about them.');
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
            'templates' => array_values(array_map(fn (MailTemplate $t) => [
                'identifier' => $t->identifier(),
                'name'       => $t->label(),
                // TODO
            ], Templates::all())),
        ];
    }

    // TODO custom schema module
    public function outputSchema(): array
    {
        return [
            'templates' => JsonSchema::array()->items(JsonSchema::object([
                'identifier' => JsonSchema::string()->required(),
                'name'       => JsonSchema::string()->required(),
            ])),
        ];
    }
}
