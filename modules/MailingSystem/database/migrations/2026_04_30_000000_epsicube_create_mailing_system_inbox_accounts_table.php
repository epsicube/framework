<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_inbox_accounts', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->boolean('is_active')->default(true)->index();

            $table->string('host');
            $table->unsignedInteger('port')->default(993);
            $table->string('encryption', 16)->nullable()->default('ssl');
            $table->string('username');
            $table->text('password');
            $table->string('folder', 255)->default('INBOX');

            $table->timestampTz('created_at')->useCurrent()->index();
            $table->timestampTz('updated_at')->nullable()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_inbox_accounts');
    }
};
