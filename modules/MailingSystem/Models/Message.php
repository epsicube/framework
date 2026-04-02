<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property int $outbox_id
 * @property string $recipient
 * @property string $type
 * @property string|null $message_id
 * @property string $status
 * @property array|null $meta
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 *
 * RELATIONS
 * @property-read Outbox $outbox
 */
class Message extends Model
{
    protected $table = 'mail_messages';

    protected static $unguarded = true;

    protected function casts(): array
    {
        return [
            'meta' => 'json',
        ];
    }

    public function outbox(): BelongsTo
    {
        return $this->belongsTo(Outbox::class);
    }
}
