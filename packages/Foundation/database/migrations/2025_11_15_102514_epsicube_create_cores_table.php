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
        Schema::create('cores', function (Blueprint $table): void {
            $table->id();
            $table->string('identifier', 64)->unique();
            $table->string('key', 64)->unique();
            $table->string('name', 150);

            // Url
            $table->string('scheme', 5)->nullable()->comment('null=any');
            $table->string('domain');
            $table->string('path', 64)->nullable();

            // Localization
            $table->string('locale', 35)->comment('IETF (BCP-47)');
            $table->string('timezone', 32)->default('UTC');

            // Extras
            $table->boolean('debug')->default(false);
            $table->boolean('maintenance')->default(false);
            $table->jsonb('config_overrides')->nullable();

            // Internal
            $table->jsonb('_maintenance_data')->nullable()->default(null);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cores');
    }
};
