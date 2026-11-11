<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_notes', function (Blueprint $table) {
            $table->id();
            $table->string('url', 1000);
            $table->string('access_key')->nullable();
            $table->string('supplier_name')->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->date('emission_date')->nullable();
            $table->string('status')->default('pending'); // pending, processed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_notes');
    }
};
