<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('options', function (Blueprint $table) {
            $table->id();

            $table->foreignId('core_id')->nullable()->index()
                ->constrained('cores')->on('id')
                ->cascadeOnUpdate()->cascadeOnDelete();

            $table->string('module_identifier', 255)->nullable()->index();

            $table->string('key', 255)->index();
            $table->jsonb('value')->nullable();
            $table->boolean('autoload')->default(false)->index();

            $table->unsignedBigInteger('_lock_core_id')
                ->invisible()
                ->storedAs("CASE WHEN core_id IS NULL THEN 0 ELSE core_id END")
                ->comment('one option per [core,module,key]');

            $table->string('_lock_module_identifier')
                ->invisible()
                ->storedAs("CASE WHEN module_identifier IS NULL THEN '__GLOBAL__RESERVED__' ELSE module_identifier END")
                ->comment('one option per [core,module,key]');

            $table->unique([
                '_lock_core_id',
                '_lock_module_identifier',
                'key'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('options');
    }
};
