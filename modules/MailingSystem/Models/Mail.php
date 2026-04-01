<?php

namespace EpsicubeModules\MailingSystem\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 */
class Mail extends Model
{
    protected $table = 'mails';
    protected static $unguarded = true;
}
