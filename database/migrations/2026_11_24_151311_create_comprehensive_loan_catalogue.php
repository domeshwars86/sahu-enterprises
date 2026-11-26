<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->string('icon', 24)->default('↗');
            $table->json('enquiry_fields')->nullable();
            $table->boolean('is_enabled')->default(false)->index();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('category_loan_product', function (Blueprint $table): void {
            $table->foreignId('loan_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('loan_product_id')->constrained()->cascadeOnDelete();
            $table->primary(['loan_category_id', 'loan_product_id']);
        });

        Schema::table('loan_products', function (Blueprint $table): void {
            $table->text('eligibility')->nullable();
            $table->json('documentation')->nullable();
            $table->json('enquiry_fields')->nullable();
            $table->string('seo_title', 180)->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->string('calculator_key', 80)->nullable();
        });

        Schema::create('loan_product_faqs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('loan_product_id')->constrained()->cascadeOnDelete();
            $table->string('question', 240);
            $table->text('answer');
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
            $table->index(['loan_product_id', 'display_order']);
        });

        Schema::create('loan_product_related', function (Blueprint $table): void {
            $table->foreignId('loan_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_loan_product_id')->constrained('loan_products')->cascadeOnDelete();
            $table->primary(['loan_product_id', 'related_loan_product_id']);
        });

        Schema::create('lending_partner_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lending_partner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('loan_product_id')->constrained()->cascadeOnDelete();
            $table->string('partnership_status', 24)->default('pending');
            $table->boolean('is_available')->default(false);
            $table->text('internal_sourcing_info')->nullable();
            $table->timestamps();
            $table->unique(['lending_partner_id', 'loan_product_id']);
        });

        Schema::table('loan_enquiries', function (Blueprint $table): void {
            $table->foreignId('loan_product_id')->nullable()->after('customer_id')->constrained('loan_products')->nullOnDelete();
            $table->text('product_details')->nullable();
        });

        Schema::table('loan_applications', function (Blueprint $table): void {
            $table->foreignId('loan_product_id')->nullable()->after('loan_enquiry_id')->constrained('loan_products')->nullOnDelete();
            $table->decimal('verified_disbursement_amount', 14, 2)->nullable();
            $table->timestamp('verified_disbursed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('loan_applications', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('loan_product_id');
            $table->dropColumn(['verified_disbursement_amount', 'verified_disbursed_at']);
        });

        Schema::table('loan_enquiries', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('loan_product_id');
            $table->dropColumn('product_details');
        });

        Schema::dropIfExists('lending_partner_products');
        Schema::dropIfExists('loan_product_related');
        Schema::dropIfExists('loan_product_faqs');
        Schema::table('loan_products', function (Blueprint $table): void {
            $table->dropColumn(['eligibility', 'documentation', 'enquiry_fields', 'seo_title', 'seo_description', 'calculator_key']);
        });
        Schema::dropIfExists('category_loan_product');
        Schema::dropIfExists('loan_categories');
    }
};
