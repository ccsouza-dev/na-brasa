<?php

use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Order;
use App\Models\OrderItem;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

echo "Starting Order Items migration...\n";

DB::beginTransaction();

try {
    // Get all transactions that came from orders
    $transactions = Transaction::whereNotNull('order_id')->get();
    
    echo "Found " . $transactions->count() . " order-based transactions.\n";
    
    $count = 0;
    
    foreach ($transactions as $trans) {
        // Check if items already exist
        if ($trans->items()->count() > 0) {
            continue;
        }

        // Get order items
        $orderItems = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('order_id', $trans->order_id)
            ->select('order_items.*', 'products.name as product_name')
            ->get();
            
        foreach ($orderItems as $item) {
            TransactionItem::create([
                'transaction_id' => $trans->id,
                'product_id' => $item->product_id,
                'name' => $item->product_name ?: 'Produto #' . $item->product_id,
                'quantity' => $item->quantity,
                'unit_price' => $item->price,
                'total_price' => $item->quantity * $item->price,
                'created_at' => $trans->created_at,
                'updated_at' => $trans->created_at,
            ]);
            $count++;
        }
    }

    DB::commit();
    echo "Successfully migrated $count items.\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
