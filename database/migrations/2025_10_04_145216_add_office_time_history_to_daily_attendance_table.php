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
        Schema::table('daily_attendance', function (Blueprint $table) {
            // Store the office time that was active when this attendance was recorded
            $table->foreignId('office_time_id')->nullable()->constrained('office_times')->onDelete('set null');
            $table->json('office_time_snapshot')->nullable(); // Store working days, start/end times
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_attendance', function (Blueprint $table) {
            $table->dropForeign(['office_time_id']);
            $table->dropColumn(['office_time_id', 'office_time_snapshot']);
        });
    }
};