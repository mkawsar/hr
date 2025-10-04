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
        Schema::create('office_times', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->time('break_start_time')->nullable();
            $table->time('break_end_time')->nullable();
            $table->integer('break_duration_minutes')->default(0);
            $table->integer('working_hours_per_day')->default(8);
            $table->json('working_days'); // ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']
            $table->integer('late_grace_minutes')->default(15); // Grace period for late arrival
            $table->integer('early_grace_minutes')->default(15); // Grace period for early departure
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('office_times');
    }
};