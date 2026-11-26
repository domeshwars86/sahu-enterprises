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
        Schema::table('loan_applications', function (Blueprint $table): void {
            $table->foreignId('lending_partner_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_applications', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('lending_partner_id');
        });
    }
};
