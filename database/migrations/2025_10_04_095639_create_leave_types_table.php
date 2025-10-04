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
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('encashable')->default(false);
            $table->boolean('carry_forward_allowed')->default(false);
            $table->integer('max_carry_forward_days')->nullable();
            $table->integer('accrual_days_per_year')->nullable();
            $table->enum('accrual_frequency', ['monthly', 'quarterly', 'yearly'])->default('yearly');
            $table->boolean('requires_approval')->default(true);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};