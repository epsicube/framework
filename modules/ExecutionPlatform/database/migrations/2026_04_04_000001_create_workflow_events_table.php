<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_events', function (Blueprint $table) {
            $table->id();
            $table->string('workflow_id')->index();
            $table->string('type'); // WorkflowStarted, ActivityScheduled, ActivityCompleted, SignalReceived, etc.
            $table->text('payload')->nullable();
            $table->string('target')->nullable(); // Activity class or Signal name
            $table->integer('index')->nullable(); // Execution index in workflow
            $table->integer('tick')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_events');
    }
};
