<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 80)->unique();
            $table->string('slug', 80)->unique();
            $table->json('permissions');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('admin_role_id')->nullable()->after('id')->constrained('admin_roles')->restrictOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('must_change_password')->default(false);
            $table->text('two_factor_secret')->nullable();
            $table->boolean('two_factor_enabled')->default(false);
            $table->timestamp('last_login_at')->nullable();
        });

        Schema::table('loan_enquiries', function (Blueprint $table): void {
            $table->string('status', 40)->default('new')->change();
            $table->foreignId('assigned_user_id')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('priority')->default(2)->index();
            $table->char('name_lookup', 64)->nullable()->index();
            $table->char('mobile_lookup', 64)->nullable()->index();
            $table->char('email_lookup', 64)->nullable()->index();
            $table->char('city_lookup', 64)->nullable()->index();
        });

        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->char('contact_fingerprint', 64)->nullable()->index();
            $table->string('communication_preference', 32)->nullable();
            $table->timestamp('privacy_request_at')->nullable();
            $table->timestamps();
        });

        Schema::table('loan_enquiries', function (Blueprint $table): void {
            $table->foreignId('customer_id')->nullable()->after('assigned_user_id')->constrained('customers')->nullOnDelete();
        });

        Schema::create('enquiry_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('loan_enquiry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('old_status', 40)->nullable();
            $table->string('new_status', 40);
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->index(['loan_enquiry_id', 'created_at']);
        });

        Schema::create('enquiry_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('loan_enquiry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('follow_ups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('loan_enquiry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('purpose', 120);
            $table->text('notes')->nullable();
            $table->timestamp('scheduled_at')->index();
            $table->string('priority', 20)->default('normal');
            $table->string('status', 24)->default('pending')->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('crm_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('loan_enquiry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->timestamp('due_at')->nullable()->index();
            $table->string('priority', 20)->default('normal');
            $table->string('status', 24)->default('pending')->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('loan_applications', function (Blueprint $table): void {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('loan_enquiry_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('product_slug', 100);
            $table->string('lender_name', 160)->nullable();
            $table->string('lender_reference', 160)->nullable();
            $table->decimal('requested_amount', 14, 2)->nullable();
            $table->string('stage', 40)->default('preparation')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->text('staff_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100)->index();
            $table->string('entity_type', 120)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('metadata')->nullable();
            $table->char('ip_fingerprint', 64)->nullable();
            $table->timestamps();
            $table->index(['entity_type', 'entity_id']);
        });

        Schema::create('loan_products', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name', 160);
            $table->string('category', 80);
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(false)->index();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('lending_partners', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 160)->unique();
            $table->string('institution_category', 80)->nullable();
            $table->text('authorized_contact')->nullable();
            $table->json('supported_products')->nullable();
            $table->text('internal_notes')->nullable();
            $table->boolean('is_verified')->default(false)->index();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('educational_videos', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 180);
            $table->string('url', 2048);
            $table->string('publisher', 160)->nullable();
            $table->text('description')->nullable();
            $table->string('category', 80)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_visible')->default(false)->index();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('educational_articles', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 180)->unique();
            $table->string('title', 180);
            $table->longText('body');
            $table->string('status', 24)->default('draft')->index();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('business_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 120)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('business_settings');
        Schema::dropIfExists('educational_articles');
        Schema::dropIfExists('educational_videos');
        Schema::dropIfExists('lending_partners');
        Schema::dropIfExists('loan_products');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('loan_applications');
        Schema::dropIfExists('crm_tasks');
        Schema::dropIfExists('follow_ups');
        Schema::dropIfExists('enquiry_notes');
        Schema::dropIfExists('enquiry_status_histories');
        Schema::table('loan_enquiries', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('customer_id');
            $table->dropConstrainedForeignId('assigned_user_id');
            $table->dropColumn(['priority', 'name_lookup', 'mobile_lookup', 'email_lookup', 'city_lookup']);
            $table->string('status', 24)->default('new')->change();
        });
        Schema::dropIfExists('customers');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('admin_role_id');
            $table->dropColumn(['is_active', 'must_change_password', 'two_factor_secret', 'two_factor_enabled', 'last_login_at']);
        });
        Schema::dropIfExists('admin_roles');
    }
};
