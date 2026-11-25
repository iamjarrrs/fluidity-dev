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
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedTinyInteger('difficulty_weight')->default(3)->comment('1: Sangat Mudah s.d. 5: Sangat Berat');
            $table->string('module_file_path')->nullable();
            $table->longText('ai_summary')->nullable();
            $table->unsignedTinyInteger('mastery_level')->default(1)->comment('1: Belum Paham s.d. 5: Sangat Paham');
            $table->decimal('ease_factor', 4, 2)->default(2.50);
            $table->unsignedInteger('repetition_count')->default(0);
            $table->unsignedInteger('interval_days')->default(0);
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamp('next_review_due')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
