<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SPP rate per academic year (monthly amount) + the period in which it applies.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_rates', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 20)->unique();   // e.g. 2026/2027
            $table->unsignedBigInteger('amount');            // SPP per month
            $table->date('effective_from');
            $table->date('effective_to');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_rates');
    }
};
