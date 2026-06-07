<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('execution_signals', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('execution_id')->constrained('executions')->cascadeOnDelete();
            $table->string('name');
            $table->jsonb('payload')->nullable();
            $table->timestampTz('created_at', 6)->useCurrent()->index();
            $table->timestampTz('updated_at', 6)->useCurrentOnUpdate()->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('execution_signals');
    }
};
