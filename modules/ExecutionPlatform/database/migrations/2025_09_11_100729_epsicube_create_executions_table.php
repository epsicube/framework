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

            //            $table->foreignId('user_id')->nullable();
            //            $table->foreign('user_id')
            //                ->references('id')->on('windy_users')
            //                ->cascadeOnUpdate()->nullOnDelete();

            $table->string('workflow_type');
            $table->jsonb('input')->nullable();
            $table->jsonb('output')->nullable();
            $table->text('last_error')->nullable();

            $table->string('note')->nullable();
            $table->enum('status', ['QUEUED', 'SCHEDULED', 'PROCESSING', 'CANCELED', 'FAILED', 'COMPLETED'])
                ->default('QUEUED')
                ->index();

            $table->uuid('_workflow_id')->nullable();
            $table->uuid('_run_id')->nullable();

            // Timestamps / Dates
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
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
