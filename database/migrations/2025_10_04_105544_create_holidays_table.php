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
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('date');
            $table->string('type')->default('national'); // national, regional, company
            $table->boolean('recurring')->default(false); // Recurring holiday (like Christmas)
            $table->boolean('active')->default(true);
            $table->timestamps();

            // Performance indexes
            $table->index(['date', 'active'], 'idx_holidays_date_active');
            $table->index(['type', 'active']);
            $table->index('date', 'idx_holidays_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};