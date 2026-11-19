<?php

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
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 40)->unique();
            $table->uuid('idempotency_key')->unique();
            $table->text('name');
            $table->text('mobile_number');
            $table->text('email')->nullable();
            $table->string('subject', 40);
            $table->longText('message');
            $table->boolean('privacy_consent');
            $table->string('privacy_notice_version', 32);
            $table->timestamp('consented_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
