<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Models;

use Epsicube\Schemas\Schema;
use EpsicubeModules\MailingSystem\Facades\Drivers;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property string $name
 *
 * DRIVER
 * @property string $driver
 * @property array|null $configuration
 *
 * SENDER
 * @property string $from_email
 * @property string|null $from_name
 */
class Mailer extends Model
{
    protected $table = 'mail_mailers';

    public $timestamps = false;

    protected static $unguarded = true;

    protected function casts(): array
    {
        return [
            'configuration' => 'json',
        ];
    }

    public function toMailer(): MailerContract
    {
        $driver = Drivers::get($this->driver);

        // Configuration
        $schema = Schema::create('mailerConfig');
        $driver->inputSchema($schema);
        $mailer = $driver->build($schema->validated($this->configuration ?? []));

        // Sender
        $mailer->alwaysFrom($this->from_email, $this->from_name ?? null);

        /** @noinspection PhpUndefinedMethodInspection */
        return $mailer->track($this, $driver);
    }
}
