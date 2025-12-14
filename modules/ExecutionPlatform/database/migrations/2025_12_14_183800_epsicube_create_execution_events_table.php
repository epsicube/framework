<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('execution_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('execution_id')->nullable();
            $table->foreign('execution_id')
                ->references('id')->on('executions')
                ->cascadeOnUpdate()->nullOnDelete();

            $table->string('activity_type')->nullable();
            $table->enum('event_type', ['ACTIVITY', 'TIMER', 'SIDEEFFECT'])->default('ACTIVITY');

            $table->unsignedSmallInteger('tries')->default(1);
            $table->jsonb('input')->nullable();
            $table->jsonb('output')->nullable();
            $table->text('output_error')->nullable();

            $table->enum('status', ['QUEUED', 'SCHEDULED', 'PROCESSING', 'CANCELED', 'FAILED', 'COMPLETED'])
                ->default('QUEUED')
                ->index();

            $table->uuid('_run_id')->nullable()->index();

            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('execution_events');
    }
};
