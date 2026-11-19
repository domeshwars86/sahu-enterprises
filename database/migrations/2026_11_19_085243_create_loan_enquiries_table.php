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
        Schema::create('loan_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 24)->unique();
            $table->uuid('idempotency_key')->unique();
            $table->text('customer_name');
            $table->text('mobile_number');
            $table->text('email')->nullable();
            $table->text('city');
            $table->string('loan_category', 40)->index();
            $table->decimal('requested_amount', 12, 2);
            $table->string('preferred_tenure', 40);
            $table->text('employment_category')->nullable();
            $table->string('vehicle_type', 40)->nullable();
            $table->string('vehicle_condition', 20)->nullable();
            $table->decimal('vehicle_price', 12, 2)->nullable();
            $table->decimal('down_payment', 12, 2)->nullable();
            $table->text('customer_notes')->nullable();
            $table->string('preferred_callback_time', 30)->nullable();
            $table->string('enquiry_source', 40)->default('website');
            $table->boolean('contact_consent');
            $table->timestamp('consented_at');
            $table->boolean('marketing_consent')->default(false);
            $table->string('privacy_notice_version', 40);
            $table->timestamps();

            $table->index(['loan_category', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_enquiries');
    }
};
