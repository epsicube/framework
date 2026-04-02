<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Mails\Drivers\Mailjet;

use const FILTER_VALIDATE_BOOL;
use const FILTER_VALIDATE_EMAIL;
use const JSON_THROW_ON_ERROR;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use JsonException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SensitiveParameter;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\FailedMessageEvent;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\Event\SentMessageEvent;
use Symfony\Component\Mailer\Exception\RuntimeException;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\RawMessage;
use Throwable;

use function array_key_exists;
use function count;
use function in_array;
use function is_array;
use function is_resource;
use function sprintf;

class MailjetApiTransport implements TransportInterface
{
    private const string HOST = 'api.mailjet.com';

    private const string API_VERSION = '3.1';

    private const array FORBIDDEN_HEADERS = [
        'date', 'x-csa-complaints', 'message-id', 'x-mj-statisticscontactslistid',
        'domainkey-status', 'received-spf', 'authentication-results', 'received',
        'from', 'sender', 'subject', 'to', 'cc', 'bcc', 'reply-to', 'return-path', 'delivered-to', 'dkim-signature',
        'x-feedback-id', 'x-mailjet-segmentation', 'list-id', 'x-mj-mid', 'x-mj-errormessage',
        'x-mailjet-debug', 'user-agent', 'x-mailer', 'x-mj-workflowid',
    ];

    private const array HEADER_TO_MESSAGE = [
        'x-mj-templatelanguage'         => ['TemplateLanguage', 'bool'],
        'x-mj-templateid'               => ['TemplateID', 'int'],
        'x-mj-templateerrorreporting'   => ['TemplateErrorReporting', 'templateerrorreporting'],
        'x-mj-templateerrordeliver'     => ['TemplateErrorDeliver', 'bool'],
        'x-mj-vars'                     => ['Variables', 'json'],
        'x-mj-customid'                 => ['CustomID', 'string'],
        'x-mj-eventpayload'             => ['EventPayload', 'string'],
        'x-mailjet-campaign'            => ['CustomCampaign', 'string'],
        'x-mailjet-deduplicatecampaign' => ['DeduplicateCampaign', 'bool'],
        'x-mailjet-prio'                => ['Priority', 'int'],
        'x-mailjet-trackclick'          => ['TrackClicks', 'enabled_bool'],
        'x-mailjet-trackopen'           => ['TrackOpens', 'enabled_bool'],
    ];

    private LoggerInterface $logger;

    public function __construct(
        private readonly string $publicKey,
        #[SensitiveParameter] private readonly string $privateKey,
        private readonly ?EventDispatcherInterface $dispatcher = null,
        ?LoggerInterface $logger = null,
        private readonly bool $sandbox = false,
    ) {
        $this->logger = $logger ?? new NullLogger;
    }

    public function __toString(): string
    {
        return sprintf('mailjet+api://%s', self::HOST.($this->sandbox ? '?sandbox=true' : ''));
    }

    public function send(RawMessage $message, ?Envelope $envelope = null): ?MailjetSentMessage
    {
        $message = clone $message;
        $envelope = $envelope !== null ? clone $envelope : Envelope::create($message);

        if (! $this->dispatcher) {
            $sentMessage = new MailjetSentMessage($message, $envelope);
            $this->doSend($sentMessage);

            return $sentMessage;
        }

        $event = new MessageEvent($message, $envelope, (string) $this);
        $this->dispatcher->dispatch($event);
        if ($event->isRejected()) {
            return null;
        }

        $envelope = $event->getEnvelope();
        $message = $event->getMessage();
        $sentMessage = new MailjetSentMessage($message, $envelope);

        try {
            $this->doSend($sentMessage);
        } catch (Throwable $error) {
            $this->dispatcher->dispatch(new FailedMessageEvent($message, $error));

            throw $error;
        }

        $this->dispatcher->dispatch(new SentMessageEvent($sentMessage));

        return $sentMessage;

    }

    protected function doSend(MailjetSentMessage $message): void
    {
        try {
            $response = $this->doSendHttp($message);
            $message->appendDebug($response->body() ?? '');
        } catch (TransportException $e) {
            $e->appendDebug($e->getDebug());

            throw $e;
        }
    }

    protected function doSendHttp(MailjetSentMessage $message): Response
    {
        try {
            $email = MessageConverter::toEmail($message->getOriginalMessage());
        } catch (Exception $e) {
            throw new RuntimeException(sprintf('Unable to send message with the "%s" transport: ', __CLASS__).$e->getMessage(), 0, $e);
        }

        return $this->doSendApi($message, $email, $message->getEnvelope());
    }

    protected function doSendApi(MailjetSentMessage $sentMessage, Email $email, Envelope $envelope): Response
    {
        $sentMessage->setPayload($this->getPayload($email, $envelope));

        try {
            $response = Http::baseUrl('https://'.self::HOST.'/v'.self::API_VERSION)
                ->withBasicAuth($this->publicKey, $this->privateKey)
                ->asJson()
                ->post('/send', $sentMessage->getPayload());

        } catch (ConnectionException $e) {
            throw new TransportException(sprintf('Could not reach the remote Mailjet server: %s', $e->getMessage()));
        }

        try {
            /** @throws JsonException When JSON_THROW_ON_ERROR flag is used and JSON is invalid */
            $result = (fn () => $response->json(flags: JSON_THROW_ON_ERROR))();
            $sentMessage->setResult($result);
        } catch (JsonException $e) {
            $this->logger->error($e);
            throw new TransportException(sprintf('Unable to decode Mailjet response: "%s" (code %d).', $response->body(), $response->status()));
        }

        if ($response->failed()) {
            $errorDetails = $result['Messages'][0]['Errors'][0]['ErrorMessage'] ?? $response->body();

            throw new TransportException(sprintf('Unable to send an email: "%s" (code %d).', $errorDetails, $response->status()));
        }

        // The response needs to contains a 'Messages' key that is an array
        if (! array_key_exists('Messages', $result) || ! is_array($result['Messages']) || count($result['Messages']) === 0) {
            throw new TransportException(sprintf('Unable to send an email: "%s" malformed api response.', $response->body()));
        }

        $sentMessage->setMessageId((string) ($result['Messages'][0]['To'][0]['MessageID'] ?? ''));

        return $response;
    }

    private function getPayload(Email $email, Envelope $envelope): array
    {
        $html = $email->getHtmlBody();
        if (is_resource($html)) {
            $html = stream_get_contents($html, offset: 0);
        }
        [$attachments, $inlines, $html] = $this->prepareAttachments($email, $html);

        $message = [
            'From'               => $this->formatAddress($envelope->getSender()),
            'To'                 => $this->formatAddresses($this->getRecipients($email, $envelope)),
            'Subject'            => $email->getSubject(),
            'Attachments'        => $attachments,
            'InlinedAttachments' => $inlines,
        ];
        if ($emails = $email->getCc()) {
            $message['Cc'] = $this->formatAddresses($emails);
        }
        if ($emails = $email->getBcc()) {
            $message['Bcc'] = $this->formatAddresses($emails);
        }
        if ($emails = $email->getReplyTo()) {
            if (1 < $length = count($emails)) {
                throw new TransportException(sprintf('Mailjet\'s API only supports one Reply-To email, %d given.', $length));
            }
            $message['ReplyTo'] = $this->formatAddress($emails[0]);
        }
        if ($email->getTextBody()) {
            $message['TextPart'] = $email->getTextBody();
        }
        if ($html) {
            $message['HTMLPart'] = $html;
        }

        foreach ($email->getHeaders()->all() as $headerName => $header) {
            if ($convertConf = self::HEADER_TO_MESSAGE[$headerName] ?? false) {
                $message[$convertConf[0]] = $this->castCustomHeader($header->getBodyAsString(), $convertConf[1]);

                continue;
            }

            if (in_array($headerName, self::FORBIDDEN_HEADERS, true)) {
                continue;
            }

            $message['Headers'][$header->getName()] = $header->getBodyAsString();
        }

        return [
            'Messages'    => [$message],
            'SandBoxMode' => $this->sandbox,
        ];
    }

    /**
     * @return Address[]
     */
    protected function getRecipients(Email $email, Envelope $envelope): array
    {
        return array_filter($envelope->getRecipients(), fn (Address $address) => in_array($address, array_merge($email->getCc(), $email->getBcc()), true) === false);
    }

    private function formatAddresses(array $addresses): array
    {
        return array_map($this->formatAddress(...), $addresses);
    }

    private function formatAddress(Address $address): array
    {
        return [
            'Email' => $address->getAddress(),
            'Name'  => $address->getName(),
        ];
    }

    private function prepareAttachments(Email $email, ?string $html): array
    {
        $attachments = $inlines = [];
        foreach ($email->getAttachments() as $attachment) {
            $headers = $attachment->getPreparedHeaders();
            $filename = $headers->getHeaderParameter('Content-Disposition', 'filename');
            $formattedAttachment = [
                'ContentType'   => $attachment->getMediaType().'/'.$attachment->getMediaSubtype(),
                'Filename'      => $filename,
                'Base64Content' => $attachment->bodyToString(),
            ];
            if ($headers->getHeaderBody('Content-Disposition') === 'inline') {
                $formattedAttachment['ContentID'] = $attachment->hasContentId() ? $attachment->getContentId() : $filename;
                $inlines[] = $formattedAttachment;
            } else {
                $attachments[] = $formattedAttachment;
            }
        }

        return [$attachments, $inlines, $html];
    }

    private function castCustomHeader(string $value, string $type): mixed
    {
        return match ($type) {
            'bool'   => filter_var($value, FILTER_VALIDATE_BOOL),
            'int'    => (int) $value,
            'json'   => json_decode($value, true, 512, JSON_THROW_ON_ERROR),
            'string' => $value,

            'enabled_bool' => filter_var($value, FILTER_VALIDATE_BOOL) ? 'enabled' : 'disabled',
            // The API transport supports a richer address format than the SMTP relay. Use a special case to support both with BC.
            'templateerrorreporting' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false ? ['Email' => $value, 'Name' => ''] : $this->castCustomHeader($value, 'json'),
        };
    }
}
