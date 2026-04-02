<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property-read int $id
 * @property int|null $mailer_id
 * @property int|null $campaign_id
 * @property string|null $subject
 * @property string $internal_id
 * @property string|null $message_id
 * @property string $status
 * @property array|null $meta
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 *
 * RELATIONS
 * @property-read Mailer|null $mailer
 * @property-read Message[] $messages
 */
class Outbox extends Model
{
    protected $table = 'mail_outbox';

    protected static $unguarded = true;

    protected function casts(): array
    {
        return [
            'meta' => 'json',
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
}
