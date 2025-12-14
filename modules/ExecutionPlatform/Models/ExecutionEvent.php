<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Models;

use Carbon\CarbonImmutable;
use EpsicubeModules\ExecutionPlatform\Enum\EventStatus;
use EpsicubeModules\ExecutionPlatform\Enum\EventType;
use EpsicubeModules\ExecutionPlatform\Facades\Activities;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * @property-read int $id
 * @property int|null $execution_id
 * @property string|null $activity_type
 * @property array|null $input
 * @property array|null $output
 * @property string|null $output_error
 * @property EventStatus $status
 * @property EventType $event_type
 * @property int $tries
 * @property string|null $_run_id
 * @property CarbonImmutable|null $started_at
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 */
class ExecutionEvent extends Model
{
    protected $table = 'execution_events';

    public $timestamps = true;

    protected static $unguarded = true;

    protected function casts(): array
    {
        return [
            'input'        => 'array',
            'output'       => 'array',
            'started_at'   => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'status'       => EventStatus::class,
            'event_type'   => EventType::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ExecutionEvent $model) {
            if (empty($model->tries)) {
                $model->tries = 1;
            }

            if (empty($model->event_type)) {
                $model->event_type = EventType::ACTIVITY;
            }

            if (empty($model->status)) {
                $model->status = EventStatus::QUEUED;
            }
        });
    }

    public function run(): static
    {
        if ($this->isDirty()) {
            $this->save();
            $this->refresh();
        }

        if (empty($this->_run_id)) {
            $this->_run_id = (string) Str::uuid();
        }

        static::getConnection()->transaction(function () {
            static::query()
                ->where('_run_id', $this->_run_id)
                ->lockForUpdate()
                ->get();

            $updated = static::query()
                ->whereKey($this->getKey())
                ->where('status', EventStatus::QUEUED)
                ->whereNotExists(function ($query) {
                    $query->from(DB::raw('(select * from `execution_events`) as sub'))
                        ->select(DB::raw(1))
                        ->where('sub._run_id', $this->_run_id)
                        ->whereIn('sub.status', [EventStatus::SCHEDULED, EventStatus::PROCESSING]);
                })
                ->update(['status' => EventStatus::SCHEDULED]);

            if (! $updated) {
                throw new Exception('Event cannot be scheduled, already in progress or not QUEUED.');
            }

            // Keep the local model instance up-to-date
            $this->fill(['status' => EventStatus::SCHEDULED])->syncOriginal();
        });

        $this->fill(['status' => EventStatus::PROCESSING, 'started_at' => now()])->save();

        try {
            $activity = Activities::get($this->activity_type);
            $result = $activity->handle($this->input ?? []);

            $this->fill([
                'status'       => EventStatus::COMPLETED,
                'output'       => $result,
                'completed_at' => now(),
            ])->save();
        } catch (Throwable $e) {
            report($e);
            $this->fill([
                'status'       => EventStatus::FAILED,
                'output_error' => $e->getMessage(),
                'completed_at' => now(),
            ])->save();
        }

        return $this;
    }
}
