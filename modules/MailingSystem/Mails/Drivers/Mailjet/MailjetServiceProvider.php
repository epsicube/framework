<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Mails\Drivers\Mailjet;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Transport\TransportInterface;

class MailjetServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Mail::extend('mailjet+api', function (array $config): TransportInterface {
            return new MailjetApiTransport(
                $config['public_key'],
                $config['private_key'],
                sandbox: filter_var($config['sandbox'] ?? false, FILTER_VALIDATE_BOOL)
            );
        });
    }
}
