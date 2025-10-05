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
        Schema::create('earned_leave_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., 'default', '2024', '2025'
            $table->text('description')->nullable();
            $table->integer('working_days_per_earned_leave')->default(15); // How many working days needed for 1 earned leave
            $table->integer('max_earned_leave_days')->default(40); // Maximum earned leave days allowed
            $table->boolean('include_weekends')->default(false); // Whether to include weekends in calculation
            $table->boolean('include_holidays')->default(false); // Whether to include holidays in calculation
            $table->boolean('include_absent_days')->default(false); // Whether to include absent days in calculation
            $table->boolean('active')->default(true); // Whether this configuration is active
            $table->integer('year')->nullable(); // Specific year this config applies to (null for all years)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('earned_leave_configs');
    }
};