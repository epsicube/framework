<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\ExecutionPlatform\Activities;

use Epsicube\Schemas\Properties\ArrayProperty;
use Epsicube\Schemas\Properties\ObjectProperty;
use Epsicube\Schemas\Properties\StringProperty;
use Epsicube\Schemas\Schema;
use EpsicubeModules\ExecutionPlatform\Contracts\Activity;
use EpsicubeModules\MailingSystem\Contracts\MailTemplate;
use EpsicubeModules\MailingSystem\Facades\Templates;

class ListTemplates implements Activity
{
    /**
     * {@inheritDoc}
     */
    public function identifier(): string
    {
        return 'epsicube-mail::list-templates';
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

    public function inputSchema(Schema $schema): void {}

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
    public function outputSchema(Schema $schema): void
    {
        $schema->append([
            'templates' => ArrayProperty::make()->items(ObjectProperty::make()->properties([
                'identifier' => StringProperty::make()->required(),
                'name'       => StringProperty::make()->required(),
            ])),
        ]);
    }
}
