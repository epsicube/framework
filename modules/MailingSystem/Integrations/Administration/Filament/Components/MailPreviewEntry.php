<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Filament\Components;

use EpsicubeModules\MailingSystem\Integrations\Administration\Support\FormatsMailDates;
use Filament\Actions\Action;
use Filament\Infolists\Components\Entry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Support\Concerns\CanBeContained;
use Filament\Support\Icons\Heroicon;
use PhpMimeMailParser\Parser;
use Throwable;

class MailPreviewEntry extends Entry
{
    use CanBeContained, FormatsMailDates;

    protected string $view = 'epsicube-mail::filament.components.mail-preview-entry';

    protected ?array $parsedMessage = null;

    public function getParsedMessage(): array
    {
        if ($this->parsedMessage !== null) {
            return $this->parsedMessage;
        }

        $eml = $this->getState();
        $htmlContent = '';
        $headers = [];
        $messageHeader = [];
        $attachments = [];
        $error = null;

        try {
            if (filled($eml)) {
                $parser = new Parser;
                $parser->setText((string) $eml);

                $htmlContent = $parser->getMessageBody('htmlEmbedded')
                    ?: $parser->getMessageBody('html')
                    ?: nl2br(e($parser->getMessageBody()));
                $headers = $parser->getHeaders();
                $messageHeader = [
                    'subject' => $parser->getHeader('subject') ?: __('No subject'),
                    'from'    => $parser->getHeader('from'),
                    'to'      => $parser->getHeader('to'),
                    'date'    => $this->formatMailDate($parser->getHeader('date')),
                ];
                $attachments = $parser->getAttachments();
            }
        } catch (Throwable $e) {
            report($e);

            $error = $e->getMessage();
            $htmlContent = '<html><body>'.e($error).'</body></html>';
        }

        return $this->parsedMessage = [
            'htmlContent'   => $htmlContent,
            'headers'       => $headers,
            'messageHeader' => $messageHeader,
            'attachments'   => $attachments,
            'error'         => $error,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return array_map(
            fn (mixed $value) => is_array($value) ? implode("\n", $value) : $value,
            $this->getParsedMessage()['headers']
        );
    }

    public function getDefaultActions(): array
    {
        return [
            fn (self $component) => Action::make('viewHeaders')
                ->label(__('View headers'))
                ->icon(Heroicon::OutlinedCodeBracket)
                ->link()
                ->visible(! empty($component->getHeaders()))
                ->modalHeading(__('Headers'))
                ->modalIcon(Heroicon::OutlinedCodeBracket)
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('Close'))
                ->slideOver()
                ->schema([
                    KeyValueEntry::make('headers')
                        ->hiddenLabel()
                        ->keyLabel(__('Name'))
                        ->valueLabel(__('Value'))
                        ->state($component->getHeaders())
                        ->columnSpanFull(),
                ]),
        ];
    }
}
