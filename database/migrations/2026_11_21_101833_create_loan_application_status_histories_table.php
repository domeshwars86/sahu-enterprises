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
        Schema::create('loan_application_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('loan_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('old_stage', 40)->nullable();
            $table->string('new_stage', 40);
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->index(['loan_application_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_application_status_histories');
    }
};
