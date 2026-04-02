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
        Schema::create('mail_mailers', function (Blueprint $table) {
            $table->id();

            $table->string('name');

            $table->string('driver', 32);
            $table->jsonb('configuration')->nullable();

            $table->string('from_email', 254);
            $table->string('from_name', 64)->nullable();
        });

        Schema::create('mail_campaigns', function (Blueprint $table) {
            $table->id();

            $table->timestampTz('created_at')->useCurrent()->index();
            $table->timestampTz('updated_at')->nullable()->useCurrentOnUpdate();
        });

        Schema::create('mail_outbox', function (Blueprint $table) {
            $table->id();

            $table->foreignId('mailer_id')->nullable()->index()
                ->constrained('mail_mailers', 'id')->nullOnDelete()->cascadeOnUpdate();

            $table->foreignId('campaign_id')->nullable()->index()
                ->constrained('mail_campaigns', 'id')->cascadeOnDelete()->cascadeOnUpdate();

            $table->string('subject')->nullable();
            $table->string('internal_id', 64)->unique()->index();
            $table->string('message_id', 255)->nullable()->index();

            $table->string('status', 32)->default('pending')->index();
            $table->jsonb('meta')->nullable();

            $table->timestampTz('created_at')->useCurrent()->index();
            $table->timestampTz('updated_at')->nullable()->useCurrentOnUpdate();
        });

        Schema::create('mail_messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('outbox_id')->index()
                ->constrained('mail_outbox', 'id')->cascadeOnDelete()->cascadeOnUpdate();

            $table->string('recipient', 255)->index();
            $table->string('type', 10)->default('to'); // to, cc, bcc

            $table->string('message_id', 255)->nullable()->index(); // ID externe spécifique par destinataire si possible

            $table->string('status', 32)->default('pending')->index();
            $table->jsonb('meta')->nullable();

            $table->timestampTz('created_at')->useCurrent()->index();
            $table->timestampTz('updated_at')->nullable()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mail_messages');
        Schema::dropIfExists('mail_outbox');
        Schema::dropIfExists('mail_campaigns');
        Schema::dropIfExists('mail_mailers');
    }
};
