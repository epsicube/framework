<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Mails;

use EpsicubeModules\MailingSystem\Contracts\Driver;
use EpsicubeModules\MailingSystem\Enums\MessageType;
use EpsicubeModules\MailingSystem\Enums\OutboxStatus;
use EpsicubeModules\MailingSystem\Models\Mailer as MailerModel;
use EpsicubeModules\MailingSystem\Models\Outbox as OutboxModel;
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
    public function __construct(
        protected TransportInterface $transport,
        protected MailerModel $mailerModel,
        protected Driver $driver
    ) {}

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        $message = clone $message;
        $envelope = $envelope !== null ? clone $envelope : Envelope::create($message);
        $email = MessageConverter::toEmail($message);

        $campaignId = $email->getHeaders()->get('X-Epsicube-Campaign-ID')?->getBody();
        $email->getHeaders()->remove('X-Epsicube-Campaign-ID');

        // Generate database outbox + messages entries
        $outbox = DB::transaction(function () use ($email, $envelope) {
            $outbox = OutboxModel::create([
                'mailer_id' => $this->mailerModel->id,
                //                'campaign_id' => $campaignId ? (int) $campaignId : null,
                'subject' => $email->getSubject(),
                'status'  => OutboxStatus::PENDING,
            ]);

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
