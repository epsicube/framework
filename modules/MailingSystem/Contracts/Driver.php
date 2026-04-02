<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Contracts;

use Epsicube\Schemas\Schema;
use Epsicube\Support\Contracts\HasLabel;
use Epsicube\Support\Contracts\Registrable;
use EpsicubeModules\MailingSystem\Models\Outbox;
use Illuminate\Mail\Mailer;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Email;

interface Driver extends HasLabel, Registrable
{
    public function inputSchema(Schema $schema): void;

    public function build(array $configuration = []): Mailer;

    public function configureMail(Email $email, Outbox $model): void;

    public function handleResponse(SentMessage $sentMessage, Outbox $outbox): void;
}
