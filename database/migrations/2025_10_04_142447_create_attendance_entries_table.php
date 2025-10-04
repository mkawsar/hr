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
        Schema::create('attendance_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_attendance_id')->constrained('daily_attendance')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date');
            
            // Individual entry times
            $table->timestamp('clock_in')->nullable();
            $table->timestamp('clock_out')->nullable();
            
            // Location data for this specific entry
            $table->decimal('clock_in_latitude', 10, 8)->nullable();
            $table->decimal('clock_in_longitude', 11, 8)->nullable();
            $table->decimal('clock_out_latitude', 10, 8)->nullable();
            $table->decimal('clock_out_longitude', 11, 8)->nullable();
            $table->foreignId('clock_in_location_id')->nullable()->constrained('locations')->onDelete('set null');
            $table->foreignId('clock_out_location_id')->nullable()->constrained('locations')->onDelete('set null');
            
            // Entry-specific calculations
            $table->decimal('working_hours', 5, 2)->nullable();
            $table->integer('late_minutes')->default(0);
            $table->integer('early_minutes')->default(0);
            $table->decimal('deduction_amount', 8, 2)->default(0);
            
            // Entry status
            $table->enum('entry_status', [
                'complete', 
                'clock_in_only', 
                'clock_out_only', 
                'incomplete'
            ])->default('incomplete');
            
            // Source of this entry
            $table->enum('source', ['office', 'remote', 'mobile', 'manual'])->default('office');
            
            // Notes for this specific entry
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['daily_attendance_id']);
            $table->index(['user_id', 'date']);
            $table->index(['clock_in', 'clock_out']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_entries');
    }
};