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
        Schema::create('hypercore_modules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('core_id')->nullable()->index()
                ->constrained('cores')->on('id')
                ->cascadeOnUpdate()->cascadeOnDelete();

            $table->string('identifier', 255);
            $table->boolean('enabled')->default(true)->index();

            // data_version  <- etat_seeder
            // schema_version  <- etat_migration

            // Internal
            $table->unsignedBigInteger('_lock_core_id')
                ->invisible()
                ->storedAs("CASE WHEN core_id IS NULL THEN 0 ELSE core_id END")
                ->comment('one option per core');

            $table->unique(['_lock_core_id', 'identifier']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hypercore_modules');
    }
};
