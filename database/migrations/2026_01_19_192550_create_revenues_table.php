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
        Schema::create('revenues', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->string('source')->nullable();
            $table->string('payment_method')->nullable();
            $table->decimal('amount', 10, 2);
            $table->enum('received_by', ['jaqueline', 'paloma', 'caixa']);
            $table->dateTime('revenue_date')->useCurrent();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revenues');
    }
};
