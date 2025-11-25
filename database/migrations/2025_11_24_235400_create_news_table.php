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
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->foreignId('categoryId')->nullable()->constrained('category')->onDelete('cascade');
            $table->text('gambar')->nullable();
            $table->text('content')->nullable();
            $table->foreignId('authorId')->nullable()->constrained('users')->onDelete('cascade');
            $table->integer('views')->nullable();
            $table->string('linkYoutube')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    
        Schema::create('comment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('userId')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('newsId')->nullable()->constrained('news')->onDelete('cascade');
            $table->integer('rating')->nullable();
            $table->timestamps();
        });
    
        Schema::create('notification', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->foreignId('newsId')->nullable()->constrained('news')->onDelete('cascade');
            $table->text('gambar')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news');
        Schema::dropIfExists('comment');
        Schema::dropIfExists('notification');
    }
};
