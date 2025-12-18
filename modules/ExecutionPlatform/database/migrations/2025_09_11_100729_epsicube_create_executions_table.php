<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('executions', function (Blueprint $table) {
            $table->id();

            $table->enum('execution_type', ['WORKFLOW', 'ACTIVITY'])->index();
            $table->string('target')->comment('workflow identifier or activity identifier');

            $table->jsonb('input')->nullable();
            $table->jsonb('output')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedBigInteger('execution_time_ns')->nullable();

            $table->string('note')->nullable();
            $table->enum('status', ['QUEUED', 'SCHEDULED', 'PROCESSING', 'CANCELED', 'FAILED', 'COMPLETED'])
                ->default('QUEUED')
                ->index();

            $table->uuid('_idempotency_key')->index();
            $table->uuid('_run_id')->nullable();

            // Timestamps / Dates
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();

            $table->timestampTz('started_at', 6)->nullable()->index();
            $table->timestampTz('completed_at', 6)->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('executions');
    }
};
