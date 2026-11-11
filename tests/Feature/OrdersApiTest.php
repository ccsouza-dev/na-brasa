<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class OrdersApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('customer_name')->nullable();
            $table->integer('table_number')->nullable();
            $table->string('status')->default('open');
            $table->decimal('total', 10, 2)->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->string('payment_method')->nullable();
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('change_amount', 10, 2)->default(0);
            $table->timestamp('paid_at')->nullable();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->string('category');
            $table->boolean('active')->default(true);
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->integer('stock_quantity')->default(0);
            $table->integer('min_stock')->default(0);
            $table->string('barcode')->nullable();
            $table->integer('supplier_id')->nullable();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('order_id')->unsigned()->nullable();
            $table->integer('product_id')->unsigned()->nullable();
            $table->integer('quantity');
            $table->decimal('price', 10, 2);
            $table->timestamp('created_at')->useCurrent();
            $table->string('status')->default('pending');
            $table->timestamp('delivered_at')->nullable();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('setting_key')->primary();
            $table->string('setting_value');
        });
    }

    public function test_create_order_add_item_and_close_order(): void
    {
        $product = Product::create([
            'name' => 'Espetinho',
            'price' => 10.00,
            'category' => 'espetinho',
            'active' => true,
            'cost_price' => 5.00,
            'stock_quantity' => 100,
            'min_stock' => 5,
        ]);

        $response = $this->postJson('/api.php?action=create_order', [
            'table' => 1,
            'customer' => 'Teste',
        ]);

        $response->assertStatus(200)->assertJson([
            'success' => true,
        ]);

        $orderId = $response->json('order_id');
        $this->assertNotNull($orderId);

        $addItem = $this->postJson('/api.php?action=add_item', [
            'order_id' => $orderId,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 10.00,
        ]);

        $addItem->assertStatus(200)->assertJson([
            'success' => true,
        ]);

        $itemId = $addItem->json('item_id');
        $this->assertNotNull($itemId);

        $this->assertEquals(1, Order::count());
        $order = Order::first();
        $this->assertEquals(20.00, (float) $order->total);

        $this->assertEquals(1, OrderItem::count());

        $markDelivered = $this->postJson('/api.php?action=mark_delivered', [
            'item_id' => $itemId,
        ]);

        $markDelivered->assertStatus(200)->assertJson([
            'success' => true,
        ]);

        $this->assertEquals('delivered', OrderItem::first()->status);

        $close = $this->postJson('/api.php?action=close_order', [
            'order_id' => $orderId,
            'payment_method' => 'Dinheiro',
            'paid_amount' => 20.00,
            'change_amount' => 0.00,
        ]);

        $close->assertStatus(200)->assertJson([
            'success' => true,
        ]);

        $order->refresh();
        $this->assertEquals('paid', $order->status);
        $this->assertNotNull($order->paid_at);
    }

    public function test_get_open_orders_and_performance_metrics(): void
    {
        $product = Product::create([
            'name' => 'Cerveja',
            'price' => 8.00,
            'category' => 'bebida',
            'active' => true,
            'cost_price' => 4.00,
            'stock_quantity' => 50,
            'min_stock' => 5,
        ]);

        $order = Order::create([
            'customer_name' => 'Cliente',
            'table_number' => 2,
            'status' => 'open',
            'total' => 8.00,
            'created_at' => now()->subMinutes(10),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 8.00,
            'created_at' => now()->subMinutes(5),
            'status' => 'pending',
        ]);

        $open = $this->getJson('/api.php?action=get_open_orders');
        $open->assertStatus(200);

        $data = $open->json();
        $this->assertCount(1, $data);
        $this->assertEquals(2, $data[0]['table_number']);
        $this->assertNotEmpty($data[0]['items']);

        $perf = $this->getJson('/api.php?action=get_performance_metrics');
        $perf->assertStatus(200);
        $perf->assertJsonStructure([
            'delivery_by_category',
            'avg_open_time',
        ]);
    }
}

