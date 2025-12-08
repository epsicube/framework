<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\ExecutionPlatform\Activities;

use Epsicube\Schemas\Properties\ArrayProperty;
use Epsicube\Schemas\Properties\ObjectProperty;
use Epsicube\Schemas\Properties\StringProperty;
use Epsicube\Schemas\Schema;
use EpsicubeModules\ExecutionPlatform\Contracts\Activity;
use EpsicubeModules\MailingSystem\Contracts\Mailer;
use EpsicubeModules\MailingSystem\Facades\Mailers;

class ListMailers implements Activity
{
    /**
     * {@inheritDoc}
     */
    public function identifier(): string
    {
        return 'epsicube-mail::list-mailers';
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

    public function inputSchema(Schema $schema): void {}

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
    public function outputSchema(Schema $schema): void
    {
        $schema->append([
            'mailers' => ArrayProperty::make()->items(ObjectProperty::make()->properties([
                'identifier' => StringProperty::make()->required(),
                'name'       => StringProperty::make()->required(),
            ])),
        ]);
    }
}
