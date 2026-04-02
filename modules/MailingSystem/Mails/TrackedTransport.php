<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Mails;

use EpsicubeModules\MailingSystem\Contracts\Driver;
use EpsicubeModules\MailingSystem\Models\Mailer as MailerModel;
use EpsicubeModules\MailingSystem\Models\Outbox as OutboxModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
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

        // --- PHASE 1 : Persistance initiale (Atomique) ---
        $outbox = DB::transaction(function () use ($email, $envelope, $campaignId) {
            $outbox = OutboxModel::create([
                'mailer_id'   => $this->mailerModel->id,
                'campaign_id' => $campaignId ? (int) $campaignId : null,
                'subject'     => $email->getSubject(),
                'internal_id' => Str::uuid7()->toString(),
                'status'      => 'pending',
            ]);

            $recipientData = collect($envelope->getRecipients())->map(fn ($recipient) => [
                'recipient'  => $recipient->getAddress(),
                'type'       => $this->determineRecipientType($email, $recipient->getAddress()),
                'status'     => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ])->toArray();

            $outbox->messages()->createMany($recipientData);

            return $outbox;
        });

        // --- PHASE 2 : Envoi (Hors transaction) ---
        try {
            $this->driver->configureMail($email, $outbox);
            $sentMessage = $this->transport->send($email, $envelope);

            // --- PHASE 3 : Mise à jour finale (Atomique) ---
            DB::transaction(function () use ($outbox, $sentMessage) {
                $externalId = $sentMessage->getMessageId();

                $outbox->update(['message_id' => $externalId, 'status' => 'sent']);
                $outbox->messages()->update(['message_id' => $externalId, 'status' => 'sent']);
            });

            return $sentMessage;

        } catch (Throwable $e) {
            DB::transaction(function () use ($outbox) {
                $outbox->update(['status' => 'error']);
                $outbox->messages()->update(['status' => 'error']);
            });

            throw $e;
        }
    }

    public function __toString(): string
    {
        return (string) $this->transport;
    }

    protected function determineRecipientType(Email $email, string $address): string
    {
        foreach (['To', 'Cc', 'Bcc'] as $type) {
            $header = $email->getHeaders()->get($type);
            if (! $header) {
                continue;
            }

            foreach ($header->getAddresses() as $headerAddress) {
                if ($headerAddress->getAddress() === $address) {
                    return mb_strtolower($type);
                }
            }
        }

        return 'to';
    }
}
