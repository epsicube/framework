<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Models;

use EpsicubeModules\MailingSystem\Enums\OutboxStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property-read int $id
 * @property string|null $subject
 * @property string|null $message_id
 * @property OutboxStatus $status
 * @property array|null $meta
 * @property string|null $raw_message
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 *
 * RELATIONS
 * @property int|null $mailer_id
 * @property-read Mailer|null $mailer
 * @property-read Collection<Message> $messages
 */
class Outbox extends Model
{
    protected $table = 'mail_outbox';

    protected static $unguarded = true;

    protected function casts(): array
    {
        return [
            'status' => OutboxStatus::class,
            'meta'   => 'json',
        ];
    }

    public function mailer(): BelongsTo
    {
        return $this->belongsTo(Mailer::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function toMessages(): HasMany
    {
        return $this->messages()->where('type', 'to');
    }

    public function ccMessages(): HasMany
    {
        return $this->messages()->where('type', 'cc');
    }

    public function bccMessages(): HasMany
    {
        return $this->messages()->where('type', 'bcc');
    }
}
