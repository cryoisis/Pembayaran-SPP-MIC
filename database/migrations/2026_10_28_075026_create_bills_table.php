<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// One row = one monthly SPP bill. `amount` is a snapshot of the rate, so later rate
// changes never alter bills that already exist.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('fee_rate_id')->constrained()->restrictOnDelete();
            $table->date('period');                          // first day of the billed month
            $table->unsignedBigInteger('amount');
            $table->timestamps();

            $table->unique(['user_id', 'period']);
            $table->index('period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
