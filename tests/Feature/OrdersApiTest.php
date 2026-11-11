<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrdersApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
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

    public function test_get_open_orders(): void
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
    }
}

