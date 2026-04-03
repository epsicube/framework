<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Models;

use Carbon\CarbonImmutable;
use EpsicubeModules\MailingSystem\Enums\MessageEngagement;
use EpsicubeModules\MailingSystem\Enums\MessageStatus;
use EpsicubeModules\MailingSystem\Enums\MessageType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property string $recipient
 * @property string $type
 * @property MessageStatus|null $status
 * @property MessageEngagement|null $engagement
 * @property int $opened_count
 * @property int $clicked_count
 * @property array|null $meta
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 *
 * RELATIONS
 * @property int $outbox_id
 * @property-read Outbox $outbox
 */
class Message extends Model
{
    protected $table = 'mail_messages';

    protected static $unguarded = true;

    protected function casts(): array
    {
        return [
            'meta'       => 'json',
            'type'       => MessageType::class,
            'status'     => MessageStatus::class,
            'engagement' => MessageEngagement::class,
        ];
    }

    public function outbox(): BelongsTo
    {
        return $this->belongsTo(Outbox::class);
    }
}
