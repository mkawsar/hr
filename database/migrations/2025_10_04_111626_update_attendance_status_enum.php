<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite, we need to recreate the table to modify the ENUM
        if (DB::getDriverName() === 'sqlite') {
            // Create a new table with updated ENUM
            Schema::create('attendance_new', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->date('date');
                $table->time('clock_in')->nullable();
                $table->time('clock_out')->nullable();
                $table->decimal('clock_in_latitude', 10, 8)->nullable();
                $table->decimal('clock_in_longitude', 11, 8)->nullable();
                $table->decimal('clock_out_latitude', 10, 8)->nullable();
                $table->decimal('clock_out_longitude', 11, 8)->nullable();
                $table->foreignId('clock_in_location_id')->nullable()->constrained('locations')->onDelete('set null');
                $table->foreignId('clock_out_location_id')->nullable()->constrained('locations')->onDelete('set null');
                $table->enum('source', ['office', 'remote', 'mobile'])->default('office');
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
                ])->default('present');
                $table->integer('late_minutes')->default(0);
                $table->integer('early_minutes')->default(0);
                $table->decimal('deduction_amount', 8, 2)->default(0);
                $table->foreignId('adjusted_by')->nullable()->constrained('users')->onDelete('set null');
                $table->text('adjustment_reason')->nullable();
                $table->timestamp('adjusted_at')->nullable();
                $table->timestamps();

                $table->index(['date', 'status']);
            });

            // Copy data from old table to new table
            DB::statement('INSERT INTO attendance_new SELECT * FROM attendance');

            // Drop old table
            Schema::dropIfExists('attendance');

            // Rename new table
            Schema::rename('attendance_new', 'attendance');
        } else {
            // For MySQL/PostgreSQL, we can modify the ENUM directly
            DB::statement("ALTER TABLE attendance MODIFY COLUMN status ENUM(
                'full_present', 
                'late_in', 
                'early_out', 
                'late_in_early_out',
                'present', 
                'late', 
                'early_leave', 
                'absent', 
                'half_day'
            ) DEFAULT 'present'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // Recreate table with original ENUM
            Schema::create('attendance_old', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->date('date');
                $table->time('clock_in')->nullable();
                $table->time('clock_out')->nullable();
                $table->decimal('clock_in_latitude', 10, 8)->nullable();
                $table->decimal('clock_in_longitude', 11, 8)->nullable();
                $table->decimal('clock_out_latitude', 10, 8)->nullable();
                $table->decimal('clock_out_longitude', 11, 8)->nullable();
                $table->foreignId('clock_in_location_id')->nullable()->constrained('locations')->onDelete('set null');
                $table->foreignId('clock_out_location_id')->nullable()->constrained('locations')->onDelete('set null');
                $table->enum('source', ['office', 'remote', 'mobile'])->default('office');
                $table->enum('status', ['present', 'absent', 'late', 'early_leave', 'half_day'])->default('present');
                $table->integer('late_minutes')->default(0);
                $table->integer('early_minutes')->default(0);
                $table->decimal('deduction_amount', 8, 2)->default(0);
                $table->foreignId('adjusted_by')->nullable()->constrained('users')->onDelete('set null');
                $table->text('adjustment_reason')->nullable();
                $table->timestamp('adjusted_at')->nullable();
                $table->timestamps();

                $table->index(['date', 'status']);
            });

            // Copy data back (only valid statuses)
            DB::statement("INSERT INTO attendance_old SELECT * FROM attendance WHERE status IN ('present', 'absent', 'late', 'early_leave', 'half_day')");

            // Drop new table
            Schema::dropIfExists('attendance');

            // Rename old table
            Schema::rename('attendance_old', 'attendance');
        } else {
            // For MySQL/PostgreSQL, revert ENUM
            DB::statement("ALTER TABLE attendance MODIFY COLUMN status ENUM('present', 'absent', 'late', 'early_leave', 'half_day') DEFAULT 'present'");
        }
    }
};