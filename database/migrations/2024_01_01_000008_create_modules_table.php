<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->integer('order')->default(0);
            $table->string('title');
            $table->timestamps();

            $table->index(['course_id', 'order']);
        });

        Schema::create('module_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('title');
            $table->timestamps();

            $table->unique(['module_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_translations');
        Schema::dropIfExists('modules');
    }
};
