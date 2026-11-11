<?php

use Illuminate\Support\Facades\DB;
use App\Models\Revenue;
use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\TransactionItem;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

echo "Starting migration (v2)...\n";

DB::beginTransaction();

try {
    // 0. Clean tables (using delete to avoid implicit commit from TRUNCATE)
    TransactionItem::query()->delete();
    Transaction::query()->delete();

    // 1. Migrate Revenues
    $revenues = Revenue::all();
    echo "Migrating " . $revenues->count() . " revenues...\n";
    foreach ($revenues as $rev) {
        Transaction::create([
            'type' => 'income',
            'amount' => $rev->amount,
            'payment_method' => $rev->payment_method,
            'description' => $rev->description,
            'category' => $rev->source,
            'responsible' => $rev->received_by,
            'transaction_date' => $rev->revenue_date,
            'original_source' => 'revenues',
            'original_id' => $rev->id,
            'created_at' => $rev->created_at ?? now(),
        ]);
    }

    // 2. Migrate Expenses and Items
    $expenses = Expense::all();
    echo "Migrating " . $expenses->count() . " expenses...\n";
    foreach ($expenses as $exp) {
        $trans = Transaction::create([
            'type' => 'expense',
            'amount' => $exp->amount,
            'payment_method' => $exp->payment_method,
            'description' => $exp->description,
            'category' => $exp->supplier,
            'responsible' => $exp->paid_by,
            'transaction_date' => $exp->expense_date,
            'original_source' => 'expenses',
            'original_id' => $exp->id,
            'created_at' => $exp->created_at ?? now(),
        ]);

        // Migrate items
        $items = ExpenseItem::where('expense_id', $exp->id)->get();
        foreach ($items as $item) {
            TransactionItem::create([
                'transaction_id' => $trans->id,
                'product_id' => $item->product_id,
                'name' => $item->product_name ?: ($item->product_id ? 'Produto #'.$item->product_id : 'Item'),
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total_price' => $item->total_price,
                'created_at' => $trans->created_at,
                'updated_at' => $trans->created_at,
            ]);
        }
    }

    // 3. Migrate Paid Orders
    $orders = Order::where('status', 'paid')
                   ->where('payment_method', '<>', 'CADERNETA')
                   ->get();
                   
    echo "Migrating " . $orders->count() . " paid orders...\n";
    
    foreach ($orders as $order) {
        $desc = "Venda #{$order->id}";
        if ($order->customer_name) {
            $desc .= " - {$order->customer_name}";
        }
        
        Transaction::create([
            'type' => 'income',
            'amount' => $order->total, 
            'payment_method' => $order->payment_method,
            'description' => $desc,
            'category' => 'VENDA',
            'responsible' => 'caixa',
            'transaction_date' => $order->paid_at ?? $order->created_at,
            'order_id' => $order->id,
            'original_source' => 'orders',
            'original_id' => $order->id,
            'created_at' => $order->created_at,
        ]);
        // Note: We are NOT migrating OrderItems to TransactionItems yet, 
        // as OrderItems are tied to Kitchen/Order logic.
    }

    DB::commit();
    echo "Migration v2 completed successfully.\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
