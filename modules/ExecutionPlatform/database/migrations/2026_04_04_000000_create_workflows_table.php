<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflows', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('workflow_class');
            $table->json('input')->nullable();
            $table->string('status')->index(); // RUNNING, COMPLETED, FAILED, CANCELLED, TERMINATED, TIMED_OUT, CONTINUED_AS_NEW
            $table->json('result')->nullable();
            $table->string('parent_id')->nullable()->index(); // for future sub-workflows
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflows');
    }
};
