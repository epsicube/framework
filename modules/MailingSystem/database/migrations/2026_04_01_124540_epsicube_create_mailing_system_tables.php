<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mails_campaigns', function (Blueprint $table) {
            $table->id();

            $table->timestampTz('created_at')->useCurrent()->index();
            $table->timestampTz('updated_at')->nullable()->useCurrentOnUpdate();
        });

        Schema::table('mails', function (Blueprint $table) {
            $table->id();

            $table->foreignId('campaign_id')->nullable()->index()
                ->constrained('mails_campaigns', 'id')->cascadeOnDelete()->cascadeOnUpdate();


            $table->string('message_id', 255);

            $table->timestampTz('created_at')->useCurrent()->index();
            $table->timestampTz('updated_at')->nullable()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mails');
        Schema::dropIfExists('mails_campaigns');
    }
};
