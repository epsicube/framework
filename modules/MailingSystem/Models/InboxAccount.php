<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Models;

use DirectoryTree\ImapEngine\Laravel\Facades\Imap;
use DirectoryTree\ImapEngine\MailboxInterface;
use Epsicube\Support\Facades\Epsicube;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property string $name
 * @property bool $is_active
 * @property string $host
 * @property int $port
 * @property string|null $encryption
 * @property string $username
 * @property string $password
 * @property string $folder
 */
class InboxAccount extends Model
{
    protected $table = 'mail_inbox_accounts';

    protected static $unguarded = true;

    protected $hidden = ['password'];

    protected static function boot()
    {
        static::created(function () {
            if (class_exists(Epsicube::class)) {
                Epsicube::terminateWorker();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'password'  => 'encrypted',
            'port'      => 'integer',
        ];
    }

    public function toMailbox(): MailboxInterface
    {
        return Imap::build([
            'host'       => $this->host,
            'port'       => $this->port,
            'username'   => $this->username,
            'password'   => $this->password,
            'timeout'    => 8,
            'encryption' => $this->encryption,
        ]);
    }
}
