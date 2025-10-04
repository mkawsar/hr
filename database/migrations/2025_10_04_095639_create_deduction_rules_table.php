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
        Schema::create('deduction_rules', function (Blueprint $table) {
            $table->id();
            $table->integer('threshold_minutes');
            $table->decimal('deduction_value', 8, 2);
            $table->string('deduction_unit')->default('hours'); // hours, days, payroll_units
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deduction_rules');
    }
};