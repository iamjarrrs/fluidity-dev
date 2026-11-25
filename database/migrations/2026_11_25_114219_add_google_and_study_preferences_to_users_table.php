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
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('email');
            $table->text('google_access_token')->nullable()->after('google_id');
            $table->text('google_refresh_token')->nullable()->after('google_access_token');
            $table->timestamp('token_expires_at')->nullable()->after('google_refresh_token');
            $table->string('calendar_id')->nullable()->after('token_expires_at');
            $table->time('study_window_start')->default('08:00')->after('calendar_id');
            $table->time('study_window_end')->default('22:00')->after('study_window_start');
            $table->unsignedTinyInteger('max_daily_sessions')->default(2)->after('study_window_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'google_id',
                'google_access_token',
                'google_refresh_token',
                'token_expires_at',
                'calendar_id',
                'study_window_start',
                'study_window_end',
                'max_daily_sessions',
            ]);
        });
    }
};
