<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->enum('audience', ['all', 'students', 'instructors'])->default('all');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('audience');
            $table->index('is_active');
        });

        Schema::create('announcement_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('title');
            $table->text('body');
            $table->timestamps();

            $table->unique(['announcement_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_translations');
        Schema::dropIfExists('announcements');
    }
};
