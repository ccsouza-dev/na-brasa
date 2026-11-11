<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 50);
                $table->decimal('price', 10, 2);
                $table->string('category', 30);
                $table->boolean('active')->default(true);
                $table->decimal('cost_price', 10, 2)->default(0);
                $table->integer('stock_quantity')->default(0);
                $table->integer('min_stock')->default(10);
                $table->string('barcode', 50)->nullable();
                $table->integer('supplier_id')->nullable();
            });
        }

        if (! Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->increments('id');
                $table->string('customer_name', 50)->nullable();
                $table->integer('table_number')->nullable();
                $table->enum('status', ['open', 'paid'])->default('open');
                $table->decimal('total', 10, 2)->default(0);
                $table->timestamp('created_at')->useCurrent();
                $table->string('payment_method', 50)->nullable();
                $table->decimal('paid_amount', 10, 2)->default(0);
                $table->decimal('change_amount', 10, 2)->default(0);
                $table->string('motoboy_name', 100)->nullable();
                $table->dateTime('paid_at')->nullable();
                $table->dateTime('debt_paid_at')->nullable();
                $table->date('debt_due_date')->nullable();
            });
        }

        if (! Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('order_id')->nullable();
                $table->unsignedInteger('product_id')->nullable();
                $table->integer('quantity');
                $table->decimal('price', 10, 2);
                $table->timestamp('created_at')->useCurrent();
                $table->enum('status', ['pending', 'delivered'])->default('pending');
                $table->timestamp('delivered_at')->nullable();
                $table->boolean('is_paid')->default(false);
                $table->foreign('order_id')->references('id')->on('orders');
                $table->foreign('product_id')->references('id')->on('products');
            });
        }

        if (! Schema::hasTable('order_payments')) {
            Schema::create('order_payments', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('order_id');
                $table->decimal('amount', 10, 2);
                $table->string('payment_method', 50);
                $table->timestamp('created_at')->useCurrent();
                $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->string('cnpj', 20)->nullable()->unique();
                $table->text('contact_info')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('expenses')) {
            Schema::create('expenses', function (Blueprint $table) {
                $table->increments('id');
                $table->string('description');
                $table->string('supplier')->nullable();
                $table->string('payment_method', 50)->nullable();
                $table->decimal('amount', 10, 2);
                $table->enum('paid_by', ['jaqueline', 'paloma', 'caixa']);
                $table->dateTime('expense_date')->useCurrent();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('expense_items')) {
            Schema::create('expense_items', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('expense_id');
                $table->unsignedInteger('product_id')->nullable();
                $table->string('product_name', 100)->nullable();
                $table->decimal('quantity', 10, 2)->default(1);
                $table->decimal('unit_price', 10, 2)->default(0);
                $table->decimal('total_price', 10, 2)->default(0);
                $table->foreign('expense_id')->references('id')->on('expenses')->cascadeOnDelete();
                $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('closings')) {
            Schema::create('closings', function (Blueprint $table) {
                $table->increments('id');
                $table->dateTime('start_date');
                $table->dateTime('end_date');
                $table->decimal('total_revenue', 10, 2)->default(0);
                $table->decimal('total_expenses_caixa', 10, 2)->default(0);
                $table->decimal('reimbursement_jaqueline', 10, 2)->default(0);
                $table->decimal('reimbursement_paloma', 10, 2)->default(0);
                $table->decimal('fixed_costs_total', 10, 2)->default(0);
                $table->text('fixed_costs_details')->nullable();
                $table->decimal('net_profit', 10, 2)->default(0);
                $table->decimal('profit_share_jaqueline', 10, 2)->default(0);
                $table->decimal('profit_share_paloma', 10, 2)->default(0);
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->string('setting_key', 50)->primary();
                $table->string('setting_value');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_items');
        Schema::dropIfExists('order_payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('closings');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('products');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('settings');
    }
};
