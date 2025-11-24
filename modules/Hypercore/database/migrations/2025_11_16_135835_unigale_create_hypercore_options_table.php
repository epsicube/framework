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
        Schema::create('hypercore_options', function (Blueprint $table) {
            $table->id();

            $table->string('module_identifier', 255)->nullable()->index();

            $table->string('key', 255)->index();
            $table->jsonb('value')->nullable();
            $table->boolean('autoload')->default(false)->index();

            $table->foreignId('tenant_id')->index()
                ->constrained('tenants')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('_lock_module_identifier')
                ->invisible()
                ->storedAs("CASE WHEN module_identifier IS NULL THEN '__GLOBAL__RESERVED__' ELSE module_identifier END")
                ->comment('one module per identifier');

            $table->unique(['tenant_id', '_lock_module_identifier', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hypercore_options');
    }
};
