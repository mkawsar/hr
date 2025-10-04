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
        Schema::table('attendance_entries', function (Blueprint $table) {
            // Add address fields for clock in and clock out locations
            $table->text('clock_in_address')->nullable()->after('clock_in_location_id');
            $table->text('clock_out_address')->nullable()->after('clock_out_location_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_entries', function (Blueprint $table) {
            $table->dropColumn(['clock_in_address', 'clock_out_address']);
        });
    }
};
