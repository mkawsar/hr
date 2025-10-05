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
            // Composite index for user_id and date - most common query pattern
            $table->index(['user_id', 'date'], 'idx_daily_attendance_user_date');
            
            // Index for status filtering
            $table->index(['user_id', 'status'], 'idx_daily_attendance_user_status');
            
            // Index for date range queries
            $table->index('date', 'idx_daily_attendance_date');
        });

        Schema::table('leave_applications', function (Blueprint $table) {
            // Composite index for user_id and status - common for filtering pending applications
            $table->index(['user_id', 'status'], 'idx_leave_applications_user_status');
            
            // Index for approved_by queries
            $table->index('approved_by', 'idx_leave_applications_approved_by');
            
            // Index for date range queries
            $table->index(['start_date', 'end_date'], 'idx_leave_applications_date_range');
        });

        Schema::table('attendance_entries', function (Blueprint $table) {
            // Composite index for user_id and date
            $table->index(['user_id', 'date'], 'idx_attendance_entries_user_date');
            
            // Index for late/early minutes filtering
            $table->index(['user_id', 'late_minutes'], 'idx_attendance_entries_late');
            $table->index(['user_id', 'early_minutes'], 'idx_attendance_entries_early');
        });

        Schema::table('users', function (Blueprint $table) {
            // Composite index for role and status filtering
            $table->index(['role_id', 'status'], 'idx_users_role_status');
            
            // Index for manager_id queries (for subordinates)
            $table->index('manager_id', 'idx_users_manager');
            
            // Index for department filtering
            $table->index('department_id', 'idx_users_department');
        });

        Schema::table('leave_balances', function (Blueprint $table) {
            // Composite index for user_id and year
            $table->index(['user_id', 'year'], 'idx_leave_balances_user_year');
            
            // Index for leave_type_id filtering
            $table->index('leave_type_id', 'idx_leave_balances_leave_type');
        });

        Schema::table('holidays', function (Blueprint $table) {
            // Index for date queries and year filtering
            $table->index('date', 'idx_holidays_date');
            $table->index(['date', 'active'], 'idx_holidays_date_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_attendance', function (Blueprint $table) {
            $table->dropIndex('idx_daily_attendance_user_date');
            $table->dropIndex('idx_daily_attendance_user_status');
            $table->dropIndex('idx_daily_attendance_date');
        });

        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropIndex('idx_leave_applications_user_status');
            $table->dropIndex('idx_leave_applications_approved_by');
            $table->dropIndex('idx_leave_applications_date_range');
        });

        Schema::table('attendance_entries', function (Blueprint $table) {
            $table->dropIndex('idx_attendance_entries_user_date');
            $table->dropIndex('idx_attendance_entries_late');
            $table->dropIndex('idx_attendance_entries_early');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_role_status');
            $table->dropIndex('idx_users_manager');
            $table->dropIndex('idx_users_department');
        });

        Schema::table('leave_balances', function (Blueprint $table) {
            $table->dropIndex('idx_leave_balances_user_year');
            $table->dropIndex('idx_leave_balances_leave_type');
        });

        Schema::table('holidays', function (Blueprint $table) {
            $table->dropIndex('idx_holidays_date');
            $table->dropIndex('idx_holidays_date_active');
        });
    }
};