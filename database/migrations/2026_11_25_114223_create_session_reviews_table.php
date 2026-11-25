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
        Schema::create('session_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_session_id')->constrained()->cascadeOnDelete();
            $table->boolean('pomodoro_completed')->default(true);
            $table->text('generated_question')->nullable()->comment('Pertanyaan konseptual dari Gemini');
            $table->longText('student_explanation')->nullable()->comment('Penjelasan konsep bahasa mahasiswa (Feynman)');
            $table->longText('ai_feedback')->nullable()->comment('Feedback suportif dari Gemini');
            $table->unsignedTinyInteger('score_awarded')->nullable()->comment('Skor 1-5');
            $table->unsignedSmallInteger('time_spent_minutes')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_reviews');
    }
};
