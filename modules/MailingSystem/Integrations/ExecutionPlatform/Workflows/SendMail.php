<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\ExecutionPlatform\Workflows;

use EpsicubeModules\ExecutionPlatform\Concerns\Workflow;
use EpsicubeModules\ExecutionPlatform\Contracts\HasInputSchema;
use EpsicubeModules\MailingSystem\Facades\Mailers;
use EpsicubeModules\MailingSystem\Facades\Templates;
use EpsicubeModules\MailingSystem\Integrations\ExecutionPlatform\Activities\SendMail as SendMailActivity;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

class SendMail extends Workflow implements HasInputSchema
{
    public function identifier(): string
    {
        return 'epsicube-mail::workflow-send-mail';
    }

    public function label(): string
    {
        return __('Send mail');
    }

    public function inputSchema(): array
    {
        return [
            Select::make('template')
                ->label(__('Template'))
                ->options(fn () => Templates::toIdentifierLabelMap())
                ->required()
                ->live()
                ->afterStateUpdated(function (Select $component) {
                    $component
                        ->getContainer()
                        ->getComponent('template_configuration', withHidden: true)
                        ->getChildSchema()
                        ->fill();
                }),

            Select::make('mailer')
                ->label(__('Mailer'))
                ->options(fn () => Mailers::toIdentifierLabelMap())
                ->required(),

            TextInput::make('subject')
                ->label(__('Subject'))
                ->required(),

            //            Section::make(__('Conditions'))
            //                ->key('conditions')
            //                ->columnSpanFull()
            //                ->schema([
            //                    Repeater::make('conditions')
            //                        ->label(__('Conditions'))
            //                        ->addActionLabel(__('Add condition'))
            //                        ->defaultItems(0)
            //                        ->schema([
            //                            TextInput::make('field')
            //                                ->label(__('Field name'))
            //                                ->placeholder(__('e.g. status'))
            //                                ->required(),
            //
            //                            Select::make('operator')
            //                                ->label(__('Operator'))
            //                                ->options([
            //                                    'equal'  => 'equals',
            //                                    'not_equal'  => 'not equals',
            //                                    'in'  => 'in',
            //                                ])
            //                                ->required()
            //                                ->native(false),
            //
            //                            TextInput::make('value')
            //                                ->label(__('Value'))
            //                                ->required(),
            //                        ])
            //                        ->columns(3),
            //                ]),

            Section::make(__('Recipients'))->key('recipients')->columnSpanFull()->schema([
                Repeater::make('to')
                    ->label(__('To'))
                    ->compact()
                    ->table([Repeater\TableColumn::make(__('Email'))])
                    ->simple(TextInput::make('email')->required())
                    ->addActionLabel(__('Add email'))
                    ->minItems(1),

                Repeater::make('cc')
                    ->label(__('CC'))
                    ->compact()
                    ->table([Repeater\TableColumn::make(__('Email'))])
                    ->simple(TextInput::make('email')->required())
                    ->addActionLabel(__('Add email'))
                    ->defaultItems(0),

                Repeater::make('bcc')
                    ->label(__('BCC'))
                    ->compact()
                    ->table([Repeater\TableColumn::make(__('Email'))])
                    ->simple(TextInput::make('email')->required())
                    ->addActionLabel(__('Add email'))
                    ->defaultItems(0),
            ])->collapsible(fn (string $operation) => $operation === 'edit')
                ->collapsed(fn (string $operation) => $operation === 'edit'),

            Section::make(__('Template configuration'))
                ->key('template_configuration')
                ->statePath('template_configuration')
                ->schema(function (Get $get) {
                    $templateName = $get('template');
                    if (blank($templateName)) {
                        return [];
                    }

                    $template = Templates::get($templateName);

                    if (! ($template instanceof \EpsicubeModules\MailingSystem\Contracts\HasInputSchema)) {
                        return [];
                    }

                    return $template->inputSchema();
                })->hiddenWhenAllChildComponentsHidden()->columnSpanFull()
                ->collapsible(fn (string $operation) => $operation === 'edit')
                ->collapsed(fn (string $operation) => $operation === 'edit'),
        ];
    }

    public function handle(array $input = []): mixed
    {
        return SendMailActivity::make()->handle($input);
    }
}
