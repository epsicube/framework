<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Mails;

use EpsicubeModules\MailingSystem\Contracts\Driver;
use EpsicubeModules\MailingSystem\Enums\MessageType;
use EpsicubeModules\MailingSystem\Enums\OutboxStatus;
use EpsicubeModules\MailingSystem\Models\Mailer;
use EpsicubeModules\MailingSystem\Models\Outbox;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\RawMessage;
use Throwable;

class TrackedTransport implements TransportInterface
{
    private const int MAX_RAW_MESSAGE_BYTES = 4_294_967_295;

    public function __construct(
        protected TransportInterface $transport,
        protected Mailer $mailerModel,
        protected Driver $driver
    ) {}

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        $message = clone $message;
        $envelope = $envelope !== null ? clone $envelope : Envelope::create($message);
        $email = MessageConverter::toEmail($message);

        // Generate database outbox + messages entries
        $outbox = DB::transaction(function () use ($email, $envelope, $message) {
            $outboxPayload = [
                'mailer_id' => $this->mailerModel->id,
                'subject'   => $email->getSubject(),
                'status'    => OutboxStatus::PENDING,
            ];

            if (($rawMessage = $this->serializeRawMessage($message)) !== null) {
                $outboxPayload['raw_message'] = $rawMessage;
            }

            try {
                $outbox = Outbox::create($outboxPayload);
            } catch (QueryException) {
                // When payloads are too large for DB insert constraints, skip raw message only.
                unset($outboxPayload['raw_message']);
                $outbox = Outbox::create($outboxPayload);
            }

            $recipientData = collect($envelope->getRecipients())->map(fn (Address $recipient) => [
                'recipient' => $recipient->getAddress(),
                'type'      => $this->determineRecipientType($email, $recipient->getAddress()),
                'meta'      => (object) [],
            ])->toArray();

            $outbox->messages()->createMany($recipientData);

            return $outbox;
        });

        // Send using initial transport
        try {
            $this->driver->configureMail($email, $outbox);
            $sentMessage = $this->transport->send($email, $envelope);

            $outbox->update([
                'status'     => OutboxStatus::SENT,
                'message_id' => $sentMessage->getMessageId(),
            ]);
        } catch (Throwable $e) {
            $outbox->update(['status' => OutboxStatus::ERROR]);

            throw $e;
        }

        $this->driver->handleResponse($sentMessage, $outbox);

        return $sentMessage;
    }

    public function __toString(): string
    {
        return (string) $this->transport;
    }

    protected function serializeRawMessage(RawMessage $message): ?string
    {
        $rawMessage = '';
        $rawMessageSize = 0;

        foreach ($message->toIterable() as $chunk) {
            $chunkSize = mb_strlen($chunk);

            if ($rawMessageSize + $chunkSize > self::MAX_RAW_MESSAGE_BYTES) {
                return null;
            }

            $rawMessage .= $chunk;
            $rawMessageSize += $chunkSize;
        }

        return $rawMessage;
    }

    protected function determineRecipientType(Email $email, string $address): MessageType
    {
        foreach (['To', 'Cc', 'Bcc'] as $type) {
            $header = $email->getHeaders()->get($type);
            if (! $header) {
                continue;
            }

            foreach ($header->getAddresses() as $headerAddress) {
                if ($headerAddress->getAddress() === $address) {
                    return MessageType::from(mb_strtolower($type));
                }
            }
        }

        return MessageType::TO;
    }
}
