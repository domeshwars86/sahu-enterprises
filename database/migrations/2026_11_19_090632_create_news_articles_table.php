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
        Schema::create('news_articles', function (Blueprint $table) {
            $table->id();
            $table->char('external_id', 64)->unique();
            $table->string('slug', 180)->unique();
            $table->string('title', 500);
            $table->text('source_url');
            $table->string('source_name', 120);
            $table->string('category', 32);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('fetched_at');
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['category', 'is_published', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_articles');
    }
};
