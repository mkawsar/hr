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
        Schema::create('attendance', function (Blueprint $table) {
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

            $table->unique(['user_id', 'date']);
            $table->index(['date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance');
    }
};