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
        Schema::create('daily_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date');
            
            // Daily summary fields
            $table->time('first_clock_in')->nullable();
            $table->time('last_clock_out')->nullable();
            $table->integer('total_entries')->default(0);
            $table->decimal('total_working_hours', 5, 2)->default(0);
            $table->integer('total_late_minutes')->default(0);
            $table->integer('total_early_minutes')->default(0);
            $table->decimal('total_deduction_amount', 8, 2)->default(0);
            
            // Daily status
            $table->enum('status', [
                'full_present', 
                'late_in', 
                'early_out', 
                'late_in_early_out', 
                'present', 
                'late', 
                'early_leave', 
                'absent', 
                'half_day'
            ])->default('absent');
            
            // Source and adjustment info
            $table->enum('source', ['office', 'remote', 'mobile', 'manual'])->default('office');
            $table->foreignId('adjusted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('adjustment_reason')->nullable();
            $table->timestamp('adjusted_at')->nullable();
            
            $table->timestamps();
            
            // Unique constraint - one record per user per day
            $table->unique(['user_id', 'date']);
            
            // Performance indexes
            $table->index(['date', 'status']);
            $table->index(['user_id', 'date'], 'idx_daily_attendance_user_date');
            $table->index(['user_id', 'status'], 'idx_daily_attendance_user_status');
            $table->index('date', 'idx_daily_attendance_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_attendance');
    }
};