<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Revenue;
use App\Models\Supplier;
use App\Models\Setting;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\TransactionItem;

use App\Models\PendingNote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApiController extends Controller
{
    public function handle(Request $request)
    {
        $action = $request->input('action');

        if (!$action && $request->query('action')) {
            $action = $request->query('action');
        }

        switch ($action) {
            case 'get_products':
                return $this->getProducts($request);
            case 'search_products':
                return $this->searchProducts($request);
            case 'get_tables_status':
                return $this->getTablesStatus();
            case 'create_order':
                return $this->createOrder($request);
            case 'add_item':
                return $this->addItem($request);
            case 'add_items':
                return $this->addItems($request);
            case 'get_open_orders':
                return $this->getOpenOrders($request);
            case 'get_table_items':
                return $this->getTableItems($request);
            case 'mark_delivered':
                return $this->markDelivered($request);
            case 'close_order':
                return $this->closeOrder($request);
            case 'delete_order':
                return $this->deleteOrder($request);
            case 'delete_order_item':
                return $this->deleteOrderItem($request);
            case 'update_order_item_quantity':
                return $this->updateOrderItemQuantity($request);
            case 'update_order_customer':
                return $this->updateOrderCustomer($request);
            case 'get_order_details':
                return $this->getOrderDetails($request);
            case 'get_report':
                return $this->getReport();
            case 'update_product':
                return $this->updateProduct($request);
            case 'get_low_stock':
                return $this->getLowStock();
            case 'create_product':
                return $this->createProduct($request);
            case 'lookup_ean':
                return $this->lookupEan($request);
            case 'lookup_cnpj':
                return $this->lookupCnpj($request);
            case 'add_expense':
                return $this->addExpense($request);
            case 'add_revenue':
                return $this->addRevenue($request);
            case 'get_financial_summary':
                return $this->getFinancialSummary($request);

            case 'delete_expense':
                return $this->deleteExpense($request);
            case 'delete_revenue':
                return $this->deleteRevenue($request);
            case 'update_expense':
                return $this->updateExpense($request);
            case 'update_revenue':
                return $this->updateRevenue($request);
            case 'update_order_financials':
                return $this->updateOrderFinancials($request);
            case 'get_closings':
                return $this->getClosings();
            case 'get_server_ip':
                return $this->getServerIp();
            case 'reset_test_data':
                return $this->resetTestData();
            case 'store_pending_note':
                return $this->storePendingNote($request);
            case 'get_pending_notes':
                return $this->getPendingNotes();
            case 'delete_pending_note':
                return $this->deletePendingNote($request);
            case 'delete_all_pending_notes':
                return $this->deleteAllPendingNotes();
            case 'get_daily_summary_dates':
                return $this->getDailySummaryDates();
            case 'get_daily_summary_data':
                return $this->getDailySummaryData($request);
            case 'lookup_nfce_items':
                return $this->lookupNfceItems($request);
            case 'delete_order_item':
                return $this->deleteOrderItem($request);
            case 'sync_suppliers':
                return $this->syncSuppliers();
            case 'search_suppliers':
                return $this->searchSuppliers($request);
            case 'search_descriptions':
                return $this->searchDescriptions($request);
            case 'get_debts':
                return $this->getDebts();
            case 'settle_debt':
                return $this->settleDebt($request);
            case 'update_item_quantity':
                return $this->updateOrderItemQuantity($request);
            default:
                return response()->json(['error' => 'Invalid action'], 400);
        }
    }


    private function getProducts()
    {
        $query = Product::where('active', 1);

        $category = request()->query('category', request()->input('category'));
        if ($category && $category !== 'todos') {
            $normalized = mb_strtoupper(trim((string) $category), 'UTF-8');
            $query->whereRaw('UPPER(category) = ?', [$normalized]);
        }

        $products = $query->orderBy('name')->get();
        return response()->json($products);
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $safeColumn = preg_replace('/[^a-zA-Z0-9_]/', '', $column);

        return Schema::hasColumn($safeTable, $safeColumn);
    }

    private function searchProducts(Request $request)
    {
        try {
            $q = $request->query('q', $request->input('q', ''));
            $q = trim($q);
            if (mb_strlen($q) < 2) {
                return response()->json([]);
            }

            $hasEan = $this->tableHasColumn('products', 'ean');
            $hasBarcode = $this->tableHasColumn('products', 'barcode');

            $query = DB::table('products')->where('active', 1);

            if ($hasEan) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', '%' . $q . '%')
                        ->orWhere('ean', 'like', '%' . $q . '%');
                });
                $select = ['id', 'name', 'cost_price', 'stock_quantity', 'ean'];
            } elseif ($hasBarcode) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', '%' . $q . '%')
                        ->orWhere('barcode', 'like', '%' . $q . '%');
                });
                $select = ['id', 'name', 'cost_price', 'stock_quantity', 'barcode as ean'];
            } else {
                $query->where('name', 'like', '%' . $q . '%');
                $select = ['id', 'name', 'cost_price', 'stock_quantity'];
            }

            $products = $query->limit(20)->get($select);

            return response()->json($products);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error in searchProducts: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    private function getTablesStatus()
    {
        try {
            $totalTables = 20;
            $tables = [];
            $rows = DB::select("
                SELECT 
                    o.id,
                    o.table_number,
                    o.customer_name,
                    o.total
                FROM orders o
                WHERE o.status = 'open'
                GROUP BY o.id, o.table_number, o.customer_name, o.total
            ");

            $byTable = [];
            $orderIds = [];

            foreach ($rows as $row) {
                $tableNumber = (int) $row->table_number;
                if (!isset($byTable[$tableNumber])) {
                    $byTable[$tableNumber] = [
                        'number' => $tableNumber,
                        'status' => 'busy',
                        'sla_status' => 'ok',
                        'total' => 0,
                        'customers' => [],
                        'orders' => [],
                        'items' => [],
                    ];
                }

                $byTable[$tableNumber]['orders'][] = [
                    'id' => (int) $row->id,
                    'customer_name' => $row->customer_name,
                    'total' => (float) $row->total,
                    'sla_status' => 'ok',
                ];

                if (!empty($row->customer_name)) {
                    $byTable[$tableNumber]['customers'][] = $row->customer_name;
                }

                $byTable[$tableNumber]['total'] += (float) $row->total;
                $orderIds[] = $row->id;
            }

            if (!empty($orderIds)) {
                $idsStr = implode(',', array_map('intval', $orderIds));
                $itemsRows = DB::select("
                    SELECT 
                        oi.order_id,
                        p.name,
                        SUM(oi.quantity) as qty,
                        SUM(oi.quantity * oi.price) as subtotal
                    FROM order_items oi
                    JOIN products p ON oi.product_id = p.id
                    WHERE oi.order_id IN ($idsStr)
                    GROUP BY oi.order_id, oi.product_id, p.name
                    ORDER BY oi.order_id, subtotal DESC
                ");

                foreach ($itemsRows as $item) {
                    $orderId = (int) $item->order_id;
                    foreach ($byTable as &$tableRow) {
                        foreach ($tableRow['orders'] as $ord) {
                            if ($ord['id'] === $orderId) {
                                $tableRow['items'][] = [
                                    'order_id' => $orderId,
                                    'name' => $item->name,
                                    'qty' => $item->qty,
                                    'subtotal' => $item->subtotal,
                                ];
                                break 2;
                            }
                        }
                    }
                    unset($tableRow);
                }
            }

            for ($i = 1; $i <= $totalTables; $i++) {
                if (isset($byTable[$i])) {
                    $customers = array_unique($byTable[$i]['customers']);
                    $customerLabel = '';
                    if (count($customers) === 1) {
                        $customerLabel = $customers[0];
                    } elseif (count($customers) > 1) {
                        $customerLabel = implode(' / ', array_slice($customers, 0, 2));
                        if (count($customers) > 2) {
                            $customerLabel .= ' + ' . (count($customers) - 2);
                        }
                    }

                    $tables[] = [
                        'number' => $i,
                        'status' => 'busy',
                        'sla_status' => $byTable[$i]['sla_status'],
                        'orders' => $byTable[$i]['orders'],
                        'customer' => $customerLabel,
                        'total' => $byTable[$i]['total'],
                        'items' => $byTable[$i]['items'],
                    ];
                } else {
                    $tables[] = [
                        'number' => $i,
                        'status' => 'free',
                        'sla_status' => 'none',
                    ];
                }
            }

            return response()->json($tables);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error in getTablesStatus: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    private function createOrder(Request $request)
    {
        $customer = $request->input('customer');
        if ($customer !== null) {
            $customer = mb_strtoupper(trim((string) $customer), 'UTF-8');
        }
        $table = (int) $request->input('table');

        if ($table <= 0) {
            return response()->json(['success' => false, 'message' => 'Mesa inválida'], 400);
        }

        $order = Order::create([
            'customer_name' => $customer,
            'table_number' => $table,
            'status' => 'open',
            'total' => 0,
        ]);

        return response()->json(['success' => true, 'order_id' => $order->id]);
    }

    private function addItem(Request $request)
    {
        $orderId = (int) $request->input('order_id');
        $productId = (int) $request->input('product_id');
        $qty = (int) $request->input('quantity');
        $price = (float) $request->input('price');

        if ($orderId <= 0 || $productId <= 0 || $qty <= 0 || $price < 0) {
            return response()->json(['success' => false, 'message' => 'Dados inválidos'], 400);
        }

        return DB::transaction(function () use ($orderId, $productId, $qty, $price) {
            $item = OrderItem::create([
                'order_id' => $orderId,
                'product_id' => $productId,
                'quantity' => $qty,
                'price' => $price,
                'status' => 'pending',
            ]);

            $totalItem = $qty * $price;

            DB::table('orders')
                ->where('id', $orderId)
                ->update([
                    'total' => DB::raw("total + $totalItem"),
                ]);

            DB::table('products')
                ->where('id', $productId)
                ->update([
                    'stock_quantity' => DB::raw("stock_quantity - $qty"),
                ]);

            return response()->json(['success' => true, 'item_id' => $item->id]);
        });
    }

    private function addItems(Request $request)
    {
        $orderId = (int) $request->input('order_id');
        $items = $request->input('items', []);

        if ($orderId <= 0 || !is_array($items) || count($items) === 0) {
            return response()->json(['success' => false, 'message' => 'Dados inválidos'], 400);
        }

        $order = Order::find($orderId);
        if (!$order || $order->status !== 'open') {
            return response()->json(['success' => false, 'message' => 'Comanda inválida'], 400);
        }

        return DB::transaction(function () use ($orderId, $items) {
            $createdIds = [];
            $totalIncrease = 0;

            foreach ($items as $it) {
                $productId = (int) ($it['product_id'] ?? 0);
                $qty = (int) ($it['quantity'] ?? 0);
                $price = (float) ($it['price'] ?? -1);

                if ($productId <= 0 || $qty <= 0 || $price < 0) {
                    throw new \InvalidArgumentException('Item inválido');
                }

                $item = OrderItem::create([
                    'order_id' => $orderId,
                    'product_id' => $productId,
                    'quantity' => $qty,
                    'price' => $price,
                    'status' => 'pending',
                ]);

                $createdIds[] = $item->id;
                $totalIncrease += ($qty * $price);

                DB::table('products')
                    ->where('id', $productId)
                    ->update([
                        'stock_quantity' => DB::raw("stock_quantity - $qty"),
                    ]);
            }

            DB::table('orders')
                ->where('id', $orderId)
                ->update([
                    'total' => DB::raw("total + $totalIncrease"),
                ]);

            return response()->json(['success' => true, 'item_ids' => $createdIds]);
        });
    }

    private function getOpenOrders(Request $request)
    {
        $tableNumber = $request->query('table');
        $query = Order::where('status', 'open')->orderByDesc('created_at');

        if ($tableNumber) {
            $query->where('table_number', (int) $tableNumber);
        }

        $orders = [];
        $now = time();

        foreach ($query->get() as $order) {
            $orderArr = $order->toArray();

            $items = DB::table('order_items as oi')
                ->join('products as p', 'oi.product_id', '=', 'p.id')
                ->select(
                    'oi.*',
                    'p.name',
                    'p.category'
                )
                ->where('oi.order_id', $order->id)
                ->orderBy('oi.status')
                ->orderBy('oi.created_at')
                ->get();

            $orderArr['items'] = [];
            $orderArr['max_wait_time'] = 0;

            $orderCreated = strtotime($order->created_at);
            $orderArr['open_duration'] = round(($now - $orderCreated) / 60);

            foreach ($items as $item) {
                $created = strtotime($item->created_at);
                if ($item->status === 'delivered' && $item->delivered_at) {
                    $delivered = strtotime($item->delivered_at);
                    $minutes = round(($delivered - $created) / 60);
                } else {
                    $minutes = round(($now - $created) / 60);
                }

                $itemArr = (array) $item;
                $itemArr['wait_time'] = $minutes;
                $itemArr['sla_color'] = 'gray';

                $orderArr['items'][] = $itemArr;
            }

            $orders[] = $orderArr;
        }

        return response()->json($orders);
    }

    private function getTableItems(Request $request)
    {
        $orderId = $request->input('order_id');

        if (!$orderId) {
            $table = $request->input('table');
            if (!$table) {
                return response()->json([]);
            }

            $order = Order::where('table_number', $table)
                ->where('status', 'open')
                ->first();

            if (!$order) {
                return response()->json([]);
            }

            $orderId = $order->id;
        }

        $items = DB::table('order_items as oi')
            ->join('products as p', 'oi.product_id', '=', 'p.id')
            ->select(
                'oi.*',
                'p.name',
                'p.category',
                'p.price as product_price'
            )
            ->where('oi.order_id', $orderId)
            ->orderByDesc('oi.created_at')
            ->get();

        $result = [];
        $now = time();

        foreach ($items as $item) {
            $itemArr = (array) $item;
            // Mantendo wait_time zerado apenas para compatibilidade se algum frontend antigo ainda esperar o campo, 
            // mas removendo o calculo real e a cor do SLA.
            $itemArr['wait_time'] = 0;
            $itemArr['sla_color'] = 'gray';

            $result[] = $itemArr;
        }

        return response()->json($result);
    }

    private function markDelivered(Request $request)
    {
        $itemId = (int) $request->input('item_id');
        if ($itemId <= 0) {
            return response()->json(['success' => false, 'message' => 'Item inválido'], 400);
        }

        DB::table('order_items')
            ->where('id', $itemId)
            ->update([
                'status' => 'delivered',
                'delivered_at' => now(),
            ]);

        return response()->json(['success' => true]);
    }

    private function deleteOrderItem(Request $request)
    {
        $itemId = (int) $request->input('item_id');

        if ($itemId <= 0) {
            return response()->json(['success' => false, 'message' => 'Item inválido'], 400);
        }

        $item = OrderItem::find($itemId);
        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item não encontrado'], 404);
        }

        $order = Order::find($item->order_id);
        if (!$order || $order->status !== 'open') {
            return response()->json(['success' => false, 'message' => 'Comanda fechada ou inválida'], 400);
        }

        return DB::transaction(function () use ($item, $order) {
            // Restore stock
            DB::table('products')
                ->where('id', $item->product_id)
                ->update([
                    'stock_quantity' => DB::raw("stock_quantity + $item->quantity"),
                ]);

            // Update order total
            $totalItem = $item->quantity * $item->price;
            DB::table('orders')
                ->where('id', $order->id)
                ->update([
                    'total' => DB::raw("total - $totalItem"),
                ]);

            // Delete item
            $item->delete();

            // Check if order is empty, if so, maybe we should not delete the order automatically, just leave it empty with 0 total
            // But if the total becomes negative (floating point errors), fix it
            $currentTotal = DB::table('orders')->where('id', $order->id)->value('total');
            if ($currentTotal < 0) {
                DB::table('orders')->where('id', $order->id)->update(['total' => 0]);
            }

            return response()->json(['success' => true]);
        });
    }

    private function updateOrderItemQuantity(Request $request)
    {
        $itemId = (int) $request->input('item_id');
        $newQuantity = (int) $request->input('quantity');

        if ($itemId <= 0 || $newQuantity <= 0) {
            return response()->json(['success' => false, 'message' => 'Dados inválidos'], 400);
        }

        $item = OrderItem::find($itemId);
        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item não encontrado'], 404);
        }

        $order = Order::find($item->order_id);
        if (!$order || $order->status !== 'open') {
            return response()->json(['success' => false, 'message' => 'Comanda fechada ou inválida'], 400);
        }

        if ($item->quantity == $newQuantity) {
            return response()->json(['success' => true, 'message' => 'Quantidade inalterada']);
        }

        return DB::transaction(function () use ($item, $order, $newQuantity) {
            $diff = $newQuantity - $item->quantity;
            $price = $item->price;

            // Update Stock
            // If diff is positive (added items), reduce stock.
            // If diff is negative (removed items), increase stock.
            DB::table('products')
                ->where('id', $item->product_id)
                ->update([
                    'stock_quantity' => DB::raw("stock_quantity - $diff"),
                ]);

            // Update Order Total
            $totalDiff = $diff * $price;
            DB::table('orders')
                ->where('id', $order->id)
                ->update([
                    'total' => DB::raw("total + $totalDiff"),
                ]);
            
            // Update Item Quantity
            $item->quantity = $newQuantity;
            $item->save();

            // Safety check for negative total
            $currentTotal = DB::table('orders')->where('id', $order->id)->value('total');
            if ($currentTotal < 0) {
                DB::table('orders')->where('id', $order->id)->update(['total' => 0]);
            }

            return response()->json(['success' => true]);
        });
    }

    private function deleteOrder(Request $request)
    {
        $orderId = (int) $request->input('order_id');
        if (!$orderId) {
            $orderId = (int) $request->input('id');
        }
        
        if ($orderId <= 0) {
            return response()->json(['success' => false, 'message' => 'Pedido inválido'], 400);
        }

        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Comanda não encontrada'], 404);
        }

        // Allow deleting paid orders as well, per user request to enable full management
        // if ($order->status !== 'open') {
        //    return response()->json(['success' => false, 'message' => 'Só é possível excluir comandas em aberto'], 400);
        // }

        DB::transaction(function () use ($orderId) {
            OrderItem::where('order_id', $orderId)->delete();
            Order::where('id', $orderId)->delete();
        });

        $table = (int) $order->table_number;
        $remaining = DB::table('orders')
            ->where('table_number', $table)
            ->where('status', 'open')
            ->count();

        if ($remaining > 0) {
            $emptyOpenOrders = DB::table('orders')
                ->where('table_number', $table)
                ->where('status', 'open')
                ->where('total', '<=', 0)
                ->pluck('id')
                ->all();
            if (!empty($emptyOpenOrders)) {
                DB::transaction(function () use ($emptyOpenOrders) {
                    OrderItem::whereIn('order_id', $emptyOpenOrders)->delete();
                    Order::whereIn('id', $emptyOpenOrders)->delete();
                });
            }
            $remaining = DB::table('orders')
                ->where('table_number', $table)
                ->where('status', 'open')
                ->count();
        }

        return response()->json(['success' => true, 'table_free' => $remaining === 0]);
    }

    private function closeOrder(Request $request)
    {
        $orderId = (int) $request->input('order_id');
        $method = $request->input('payment_method', 'Dinheiro');
        $paid = (float) $request->input('paid_amount', 0);
        $change = (float) $request->input('change_amount', 0);
        $debtDueDate = $request->input('debt_due_date');

        if ($orderId <= 0) {
            return response()->json(['success' => false, 'message' => 'Pedido inválido'], 400);
        }

        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Comanda não encontrada'], 404);
        }

        $updateData = [
            'status' => 'paid',
            'payment_method' => $method,
            'paid_amount' => $paid,
            'change_amount' => $change,
            'paid_at' => now(),
        ];

        if ($debtDueDate) {
            $updateData['debt_due_date'] = $debtDueDate;
        }

        DB::table('orders')
            ->where('id', $orderId)
            ->update($updateData);

        if ($method !== 'CADERNETA') {
            $desc = "Venda #{$orderId}";
            if ($order->customer_name) {
                $desc .= " - {$order->customer_name}";
            }
            $transaction = Transaction::create([
                'type' => 'income',
                'amount' => $order->total, 
                'payment_method' => $method,
                'description' => $desc,
                'category' => 'VENDA',
                'responsible' => 'caixa',
                'transaction_date' => now(),
                'order_id' => $orderId,
                'original_source' => 'orders',
                'original_id' => $orderId,
            ]);

            // Add items to transaction
            $orderItems = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('order_id', $orderId)
                ->select('order_items.*', 'products.name as product_name')
                ->get();

            foreach ($orderItems as $item) {
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $item->product_id,
                    'name' => $item->product_name ?: 'Produto #' . $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->price,
                    'total_price' => $item->quantity * $item->price,
                ]);
            }
        }

        $table = (int) $order->table_number;
        $remaining = DB::table('orders')
            ->where('table_number', $table)
            ->where('status', 'open')
            ->count();

        return response()->json(['success' => true, 'table_free' => $remaining === 0]);
    }

    private function getReport()
    {
        $row = DB::table('orders')
            ->selectRaw('COUNT(*) as total_orders, SUM(total) as total_revenue')
            ->where('status', 'paid')
            ->where('payment_method', '<>', 'CADERNETA')
            ->first();

        if (!$row) {
            $row = (object) ['total_orders' => 0, 'total_revenue' => 0];
        }

        return response()->json($row);
    }

    private function getPerformanceMetrics()
    {
        $deliveryRows = DB::table('order_items as oi')
            ->join('products as p', 'oi.product_id', '=', 'p.id')
            ->select('p.category', 'oi.created_at', 'oi.delivered_at', 'oi.status')
            ->where('oi.status', 'delivered')
            ->whereNotNull('oi.delivered_at')
            ->get();

        $categoryTotals = [];
        $categoryCounts = [];

        foreach ($deliveryRows as $row) {
            $created = strtotime($row->created_at);
            $delivered = strtotime($row->delivered_at);
            if ($created && $delivered && $delivered >= $created) {
                $minutes = ($delivered - $created) / 60;
                if (!isset($categoryTotals[$row->category])) {
                    $categoryTotals[$row->category] = 0;
                    $categoryCounts[$row->category] = 0;
                }
                $categoryTotals[$row->category] += $minutes;
                $categoryCounts[$row->category] += 1;
            }
        }

        $deliveryMetrics = [];
        foreach ($categoryTotals as $category => $totalMinutes) {
            $count = $categoryCounts[$category] ?: 1;
            $deliveryMetrics[$category] = (int) round($totalMinutes / $count);
        }

        $orders = DB::table('orders')
            ->select('created_at', 'paid_at', 'status')
            ->where(function ($q) {
                $q->where('status', 'open')
                    ->orWhere(function ($q2) {
                        $q2->where('status', 'paid')
                            ->whereNotNull('paid_at');
                    });
            })
            ->get();

        $now = time();
        $totalDuration = 0;
        $countOrders = 0;

        foreach ($orders as $order) {
            $created = strtotime($order->created_at);
            $endTime = $order->paid_at ? strtotime($order->paid_at) : $now;
            if ($created && $endTime && $endTime >= $created) {
                $minutes = ($endTime - $created) / 60;
                $totalDuration += $minutes;
                $countOrders++;
            }
        }

        $avgOpenTime = $countOrders > 0 ? (int) round($totalDuration / $countOrders) : 0;

        return response()->json([
            'delivery_by_category' => $deliveryMetrics,
            'avg_open_time' => $avgOpenTime,
        ]);
    }

    private function updateProduct(Request $request)
    {
        try {
            $id = (int) $request->input('id');
            if ($id <= 0) {
                return response()->json(['success' => false, 'message' => 'ID de produto inválido.'], 400);
            }

            $name = $request->input('name');
            if ($name !== null) {
                $name = mb_strtoupper(trim((string) $name), 'UTF-8');
            }
            $category = $request->input('category');
            if ($category !== null) {
                $category = mb_strtoupper(trim((string) $category), 'UTF-8');
            }

            $costInput = $request->input('cost_price', 0);
            $costInput = str_replace(',', '.', (string) $costInput);
            $cost = (float) $costInput;

            $priceInput = $request->input('price', 0);
            $priceInput = str_replace(',', '.', (string) $priceInput);
            $price = (float) $priceInput;

            $min = (int) $request->input('min_stock', 0);

            $addStock = (int) $request->input('add_stock', 0);

            $ean = $request->input('ean');
            $hasEan = $this->tableHasColumn('products', 'ean');
            $hasBarcode = $this->tableHasColumn('products', 'barcode');

            $setStock = null;
            if ($request->has('stock_quantity') && $request->input('stock_quantity') !== '') {
                $setStock = (int) $request->input('stock_quantity');
            }

            $fields = [];
            if ($name !== null && $name !== '') {
                $fields['name'] = $name;
            }
            if ($category !== null && $category !== '') {
                $fields['category'] = $category;
            }
            $fields['cost_price'] = $cost;
            $fields['price'] = $price;
            $fields['min_stock'] = $min;
            
            if ($ean !== null) {
                if ($hasEan) {
                    $fields['ean'] = $ean;
                } elseif ($hasBarcode) {
                    $fields['barcode'] = $ean;
                }
            }
            
            if ($setStock !== null) {
                $fields['stock_quantity'] = $setStock;
            }

            if (!empty($fields)) {
                $updated = DB::table('products')
                    ->where('id', $id)
                    ->update($fields);

                // Note: update returns number of affected rows. 
                // If data is same, it returns 0, which is not false.
                // It only returns false on error (but usually throws exception).
            }

            if ($addStock !== 0 && $setStock === null) {
                if ($this->tableHasColumn('products', 'stock_quantity')) {
                    DB::table('products')
                        ->where('id', $id)
                        ->update([
                            'stock_quantity' => DB::raw('stock_quantity + ' . $addStock),
                        ]);
                }
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()], 500);
        }
    }

    private function getLowStock()
    {
        $alerts = Product::where('active', 1)
            ->whereColumn('stock_quantity', '<=', 'min_stock')
            ->orderBy('name')
            ->get();

        return response()->json($alerts);
    }

    private function createProduct(Request $request)
    {
        try {
            $name = $request->input('name');
            if ($name !== null) {
                $name = mb_strtoupper(trim((string) $name), 'UTF-8');
            }
            $priceInput = $request->input('price', 0);
            $priceInput = str_replace(',', '.', (string) $priceInput);
            $price = (float) $priceInput;

            if (empty($name) || $price <= 0) {
                return response()->json(['success' => false, 'message' => 'Nome e preço são obrigatórios.'], 400);
            }

            $costInput = $request->input('cost_price', 0);
            $costInput = str_replace(',', '.', (string) $costInput);
            $cost = (float) $costInput;

            $stock = (int) $request->input('stock_quantity', 0);
            $min = (int) $request->input('min_stock', 10);
            $ean = $request->input('ean');

            $category = $request->input('category', 'Geral');
            if ($category !== null) {
                $category = mb_strtoupper(trim((string) $category), 'UTF-8');
            }

            $hasEan = $this->tableHasColumn('products', 'ean');
            $hasBarcode = $this->tableHasColumn('products', 'barcode');

            $data = [
                'name' => $name,
                'price' => $price,
                'cost_price' => $cost,
                'stock_quantity' => $stock,
                'min_stock' => $min,
                'category' => $category,
                'active' => 1
            ];

            if ($ean !== null && $ean !== '') {
                if ($hasEan) {
                    $data['ean'] = $ean;
                } elseif ($hasBarcode) {
                    $data['barcode'] = $ean;
                }
            }

            $id = DB::table('products')->insertGetId($data);

            if ($id) {
                return response()->json(['success' => true, 'id' => $id]);
            } else {
                return response()->json(['success' => false, 'message' => 'Erro ao criar produto.'], 500);
            }

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()], 500);
        }
    }

    private function lookupCnpj(Request $request)
    {
        $cnpj = '';
        if ($request->query('cnpj')) {
            $cnpj = $request->query('cnpj');
        } elseif ($request->input('cnpj')) {
            $cnpj = $request->input('cnpj');
        }

        $cnpj = preg_replace('/\D/', '', (string) $cnpj);

        if (strlen($cnpj) !== 14) {
            return response()->json(['success' => false, 'message' => 'CNPJ inválido.']);
        }

        $url = 'https://brasilapi.com.br/api/cnpj/v1/' . urlencode($cnpj);
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return response()->json(['success' => false, 'message' => 'Não foi possível consultar o CNPJ no momento.']);
        }

        $data = json_decode($response, true);
        if (!$data || isset($data['message'])) {
            return response()->json(['success' => false, 'message' => 'CNPJ não encontrado.']);
        }

        $razao = '';
        if (isset($data['razao_social']) && $data['razao_social']) {
            $razao = $data['razao_social'];
        } elseif (isset($data['nome_fantasia']) && $data['nome_fantasia']) {
            $razao = $data['nome_fantasia'];
        }

        if ($razao === '') {
            return response()->json(['success' => false, 'message' => 'Não foi possível obter a razão social para este CNPJ.']);
        }

        $formatted = substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3) . '.' . substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12, 2);

        return response()->json([
            'success' => true,
            'cnpj' => $cnpj,
            'cnpj_formatado' => $formatted,
            'razao_social' => $razao,
        ]);
    }

    private function lookupEan(Request $request)
    {
        $ean = '';
        if ($request->query('ean')) {
            $ean = $request->query('ean');
        } elseif ($request->input('ean')) {
            $ean = $request->input('ean');
        }

        $ean = preg_replace('/\D/', '', (string) $ean);

        if ($ean === '') {
            return response()->json(['success' => false, 'message' => 'EAN inválido.']);
        }

        $url = 'https://world.openfoodfacts.org/api/v0/product/' . urlencode($ean) . '.json';
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        $found = false;
        $data = null;

        if ($response !== false) {
            $data = json_decode($response, true);
            if ($data && isset($data['status']) && (int) $data['status'] === 1) {
                $found = true;
            }
        }

        if (!$found && strlen($ean) > 0 && $ean[0] === '0') {
            $eanTrimmed = ltrim($ean, '0');
            if ($eanTrimmed !== '') {
                $url2 = 'https://world.openfoodfacts.org/api/v0/product/' . urlencode($eanTrimmed) . '.json';
                $response2 = @file_get_contents($url2, false, $context);
                if ($response2 !== false) {
                    $data2 = json_decode($response2, true);
                    if ($data2 && isset($data2['status']) && (int) $data2['status'] === 1) {
                        $found = true;
                        $data = $data2;
                    }
                }
            }
        }

        if (!$found) {
            return response()->json(['success' => false, 'message' => 'Produto não encontrado para este EAN.']);
        }

        $name = '';
        if (isset($data['product']['product_name']) && $data['product']['product_name']) {
            $name = $data['product']['product_name'];
        } elseif (isset($data['product']['generic_name']) && $data['product']['generic_name']) {
            $name = $data['product']['generic_name'];
        }

        return response()->json(['success' => true, 'name' => $name, 'ean' => $ean]);
    }

    private function addExpense(Request $request)
    {
        $description = (string) $request->input('description');
        if ($description !== '') {
            $description = mb_strtoupper(trim($description), 'UTF-8');
        }
        $amount = (float) $request->input('amount', 0);
        $paidBy = (string) $request->input('paid_by');
        if ($paidBy !== '') {
            $paidBy = mb_strtoupper(trim($paidBy), 'UTF-8');
        }
        $supplier = (string) $request->input('supplier', '');
        if ($supplier !== '') {
            $supplier = mb_strtoupper(trim($supplier), 'UTF-8');
        }
        $method = (string) $request->input('payment_method', '');
        if ($method !== '') {
            $method = mb_strtoupper(trim($method), 'UTF-8');
        }
        $date = $request->input('date');
        if (!$date) {
            $date = $request->input('expense_date');
        }
        if (!$date) {
            $date = now()->format('Y-m-d H:i:s');
        }

        $items = $request->input('items', []);

        return DB::transaction(function () use ($description, $amount, $paidBy, $supplier, $method, $date, $items) {
            $transaction = Transaction::create([
                'type' => 'expense',
                'description' => $description,
                'category' => $supplier, // category stores supplier for expenses
                'payment_method' => $method,
                'amount' => $amount,
                'responsible' => $paidBy,
                'transaction_date' => $date,
                'created_at' => now(),
            ]);

            if ($supplier) {
                $exists = Supplier::where('name', $supplier)->exists();
                if (!$exists) {
                    Supplier::create([
                        'name' => $supplier,
                        'created_at' => now()
                    ]);
                }
            }

            foreach ($items as $item) {
                $productId = isset($item['product_id']) && $item['product_id'] ? (int) $item['product_id'] : null;
                $productName = isset($item['name']) ? (string) $item['name'] : '';
                if ($productName !== '') {
                    $productName = mb_strtoupper(trim($productName), 'UTF-8');
                }
                $qty = isset($item['quantity']) ? (float) $item['quantity'] : 0;
                $unit = isset($item['unit_price']) ? (float) $item['unit_price'] : 0;
                $total = $qty * $unit;

                // Try to find product by name if ID is missing
                if (!$productId && $productName) {
                    $prod = DB::table('products')->where('name', $productName)->first();
                    if ($prod) {
                        $productId = $prod->id;
                    }
                }

                \App\Models\TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $productId,
                    'name' => $productName,
                    'quantity' => $qty,
                    'unit_price' => $unit,
                    'total_price' => $total,
                ]);

                if ($productId) {
                    DB::table('products')
                        ->where('id', $productId)
                        ->update([
                            'stock_quantity' => DB::raw('stock_quantity + ' . $qty),
                            'cost_price' => $unit,
                        ]);
                }
            }

            return response()->json(['success' => true]);
        });
    }

    private function addRevenue(Request $request)
    {
        $description = (string) $request->input('description');
        if ($description !== '') {
            $description = mb_strtoupper(trim($description), 'UTF-8');
        }
        $source = (string) $request->input('source');
        if ($source !== '') {
            $source = mb_strtoupper(trim($source), 'UTF-8');
        }
        $amount = (float) $request->input('amount', 0);
        $receivedBy = (string) $request->input('received_by');
        if ($receivedBy !== '') {
            $receivedBy = mb_strtoupper(trim($receivedBy), 'UTF-8');
        }
        $method = (string) $request->input('payment_method');
        if ($method !== '') {
            $method = mb_strtoupper(trim($method), 'UTF-8');
        }
        $date = $request->input('date');
        if (!$date) {
            $date = now()->format('Y-m-d H:i:s');
        }

        if ($amount == 0) {
            return response()->json(['success' => false, 'message' => 'Valor inválido.']);
        }

        Transaction::create([
            'type' => 'income',
            'description' => $description,
            'category' => $source,
            'payment_method' => $method,
            'amount' => $amount,
            'responsible' => $receivedBy,
            'transaction_date' => $date,
            'created_at' => now(),
        ]);

        if ($source) {
            $exists = Supplier::where('name', $source)->exists();
            if (!$exists) {
                Supplier::create([
                    'name' => $source,
                    'created_at' => now()
                ]);
            }
        }

        return response()->json(['success' => true]);
    }

    private function getFinancialSummary(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if ($startDate && strlen($startDate) === 10) {
            $startDate .= ' 00:00:00';
        }

        if ($endDate && strlen($endDate) === 10) {
            $endDate .= ' 23:59:59';
        }

        if (!$endDate) {
            $endDate = now()->format('Y-m-d H:i:s');
        }

        if (!$startDate) {
            $startDate = '2024-01-01 00:00:00';
        }

        // 1. Total Revenue (Income)
        $revenue = DB::table('transactions')
            ->where('type', 'income')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('amount');

        // 2. Revenue by Method
        $revenueByMethod = [];
        $incomeMethods = DB::table('transactions')
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->where('type', 'income')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->groupBy('payment_method')
            ->get();

        foreach ($incomeMethods as $row) {
            $key = $row->payment_method ? mb_strtoupper($row->payment_method, 'UTF-8') : 'OUTROS';
            if ($key === 'ESTORNO') {
                continue;
            }
            if (!isset($revenueByMethod[$key])) {
                $revenueByMethod[$key] = 0.0;
            }
            $revenueByMethod[$key] += (float) $row->total;
        }

        // 3. Expenses by Responsible
        $expRows = DB::table('transactions')
            ->select('responsible', DB::raw('SUM(amount) as total'))
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->groupBy('responsible')
            ->get();

        $expenses = ['jaqueline' => 0.0, 'paloma' => 0.0, 'caixa' => 0.0];
        $totalExpenses = 0.0;
        foreach ($expRows as $row) {
            $resp = strtolower($row->responsible ?? '');
            if (isset($expenses[$resp])) {
                $expenses[$resp] = (float) $row->total;
            } else {
                 $expenses[$resp] = (float) $row->total;
            }
            $totalExpenses += (float) $row->total;
        }

        // 4. Lists (Mapped for compatibility)
        $expenseListRaw = Transaction::where('type', 'expense')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderByDesc('transaction_date')
            ->limit(100)
            ->get();

        $expenseList = $expenseListRaw->map(function($item) {
            return [
                'id' => $item->id,
                'description' => $item->description,
                'amount' => $item->amount,
                'payment_method' => $item->payment_method,
                'supplier' => $item->category,
                'paid_by' => $item->responsible,
                'expense_date' => $item->transaction_date,
                'created_at' => $item->created_at,
            ];
        });

        $revenueListRaw = Transaction::where('type', 'income')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderByDesc('transaction_date')
            ->limit(100)
            ->get();

        $revenueList = $revenueListRaw->map(function($item) {
            $desc = $item->description;
            if (empty($desc) && !empty($item->order_id)) {
                $desc = 'Pedido #' . $item->order_id;
            }
            
            return [
                'id' => $item->id,
                'description' => $desc,
                'amount' => $item->amount,
                'payment_method' => $item->payment_method,
                'source' => $item->category ?: 'Venda',
                'received_by' => $item->responsible,
                'revenue_date' => $item->transaction_date,
                'created_at' => $item->created_at,
            ];
        });

        $orderList = DB::table('transactions')
            ->join('orders', 'transactions.order_id', '=', 'orders.id')
            ->where('transactions.type', 'income')
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
            ->select('orders.*', 'transactions.id as transaction_id')
            ->orderByDesc('transactions.transaction_date')
            ->get();

        // 5. Daily Data
        $dailyOrders = DB::table('transactions')
            ->select(DB::raw('DATE(transaction_date) as date'), DB::raw('SUM(amount) as total'))
            ->where('type', 'income')
            ->whereNotNull('order_id')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(transaction_date)'))
            ->get();

        $dailyManual = DB::table('transactions')
            ->select(DB::raw('DATE(transaction_date) as date'), DB::raw('SUM(amount) as total'))
            ->where('type', 'income')
            ->whereNull('order_id')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(transaction_date)'))
            ->get();

        $dailyExpenses = DB::table('transactions')
            ->select(DB::raw('DATE(transaction_date) as date'), DB::raw('SUM(amount) as total'))
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(transaction_date)'))
            ->get();

        // 6. Sales by Category (Transactions Source)
        $salesByCatRows = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->select('products.category', DB::raw('SUM(transaction_items.total_price) as total'))
            ->where('transactions.type', 'income')
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
            ->groupBy('products.category')
            ->get();

        $salesByCategory = [];
        foreach ($salesByCatRows as $row) {
            $cat = $row->category ?: 'OUTROS';
            $salesByCategory[] = [
                'category' => $cat,
                'total' => (float) $row->total
            ];
        }

        // 7. Receivables (Caderneta)
        $receivablesTotal = DB::table('orders')
            ->where('payment_method', 'CADERNETA')
            ->whereNull('debt_paid_at')
            ->where('status', 'paid')
            ->sum('total');

        $receivablesUpcoming = DB::table('orders')
            ->select('debt_due_date', DB::raw('SUM(total) as total'))
            ->where('payment_method', 'CADERNETA')
            ->whereNull('debt_paid_at')
            ->where('status', 'paid')
            ->groupBy('debt_due_date')
            ->orderBy('debt_due_date', 'asc')
            ->limit(5)
            ->get()
            ->map(function($row) {
                return ['date' => $row->debt_due_date, 'total' => (float)$row->total];
            });

        return response()->json([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'revenue' => $revenue,
            'receivables_total' => (float) $receivablesTotal,
            'receivables_upcoming' => $receivablesUpcoming,
            'revenue_by_method' => $revenueByMethod,
            'sales_by_category' => $salesByCategory,
            'expenses' => $expenses,
            'total_expenses' => $totalExpenses,
            'expense_list' => $expenseList,
            'revenue_list' => $revenueList,
            'order_list' => $orderList,
            'daily_orders' => $dailyOrders,
            'daily_manual_revenues' => $dailyManual,
            'daily_expenses' => $dailyExpenses,
        ]);
    }

    private function performClosing(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = now()->format('Y-m-d H:i:s');
        $revenue = (float) $request->input('revenue', 0);
        $expCaixa = (float) $request->input('exp_caixa', 0);
        $reimbJaque = (float) $request->input('reimb_jaque', 0);
        $reimbPaloma = (float) $request->input('reimb_paloma', 0);
        $fixedTotal = (float) $request->input('fixed_total', 0);
        $fixedDetails = $request->input('fixed_details', []);
        $netProfit = (float) $request->input('net_profit', 0);
        $shareJaque = (float) $request->input('share_jaque', 0);
        $sharePaloma = (float) $request->input('share_paloma', 0);

        Closing::create([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_revenue' => $revenue,
            'total_expenses_caixa' => $expCaixa,
            'reimbursement_jaqueline' => $reimbJaque,
            'reimbursement_paloma' => $reimbPaloma,
            'fixed_costs_total' => $fixedTotal,
            'fixed_costs_details' => json_encode($fixedDetails),
            'net_profit' => $netProfit,
            'profit_share_jaqueline' => $shareJaque,
            'profit_share_paloma' => $sharePaloma,
            'created_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    private function deleteExpense(Request $request)
    {
        $id = (int) $request->input('id');
        if ($id <= 0) {
            return response()->json(['success' => false, 'message' => 'ID inválido.'], 400);
        }

        $transaction = Transaction::find($id);
        if ($transaction && $transaction->type === 'expense') {
            $transaction->delete();
        }

        return response()->json(['success' => true]);
    }

    private function deleteRevenue(Request $request)
    {
        $id = (int) $request->input('id');
        if ($id <= 0) {
            return response()->json(['success' => false, 'message' => 'ID inválido.'], 400);
        }

        $transaction = Transaction::find($id);
        if ($transaction && $transaction->type === 'income') {
            $transaction->delete();
        }

        return response()->json(['success' => true]);
    }

    private function updateExpense(Request $request)
    {
        $id = (int) $request->input('id');
        if ($id <= 0) {
            return response()->json(['success' => false, 'message' => 'ID inválido.'], 400);
        }

        $transaction = Transaction::find($id);
        
        if (!$transaction || $transaction->type !== 'expense') {
            return response()->json(['success' => false, 'message' => 'Despesa não encontrada']);
        }

        $description = (string) $request->input('description');
        if ($description !== '') {
            $transaction->description = mb_strtoupper(trim($description), 'UTF-8');
        }
        
        $amount = (float) $request->input('amount');
        if ($amount > 0) {
            $transaction->amount = $amount;
        }

        $paidBy = (string) $request->input('paid_by');
        if ($paidBy !== '') {
            $transaction->responsible = mb_strtoupper(trim($paidBy), 'UTF-8');
        }

        $supplier = (string) $request->input('supplier');
        if ($supplier !== '') {
            $transaction->category = mb_strtoupper(trim($supplier), 'UTF-8');
        }

        $method = (string) $request->input('payment_method');
        if ($method !== '') {
            $transaction->payment_method = mb_strtoupper(trim($method), 'UTF-8');
        }

        $date = $request->input('date');
        if (!$date) {
            $date = $request->input('expense_date');
        }
        if ($date) {
            $transaction->transaction_date = $date;
        }

        $transaction->save();
        
        // Update Supplier if new
        if ($supplier) {
            $exists = Supplier::where('name', $supplier)->exists();
            if (!$exists) {
                Supplier::create([
                    'name' => $supplier,
                    'created_at' => now()
                ]);
            }
        }

        return response()->json(['success' => true]);
    }

    private function updateRevenue(Request $request)
    {
        $id = (int) $request->input('id');
        if ($id <= 0) {
            return response()->json(['success' => false, 'message' => 'ID inválido.'], 400);
        }

        $transaction = Transaction::find($id);
        
        if (!$transaction || $transaction->type !== 'income') {
            return response()->json(['success' => false, 'message' => 'Receita não encontrada']);
        }

        $description = (string) $request->input('description');
        if ($description !== '') {
            $transaction->description = mb_strtoupper(trim($description), 'UTF-8');
        }
        $source = (string) $request->input('source');
        if ($source !== '') {
            $transaction->category = mb_strtoupper(trim($source), 'UTF-8');
        }
        $amount = (float) $request->input('amount');
        if ($amount > 0) {
            $transaction->amount = $amount;
        }
        
        $method = (string) $request->input('payment_method');
        if ($method !== '') {
            $transaction->payment_method = mb_strtoupper(trim($method), 'UTF-8');
        }
        
        // Handle date
        $date = $request->input('date');
        if (!$date) {
            $date = $request->input('revenue_date');
        }
        if ($date) {
            $transaction->transaction_date = $date;
        }

        if ($request->has('received_by')) {
            $receivedBy = (string) $request->input('received_by');
            if ($receivedBy !== '') {
                $transaction->responsible = mb_strtoupper(trim($receivedBy), 'UTF-8');
            }
        }

        $transaction->save();

        // Update Supplier/Source if new
        if ($source) {
            $exists = Supplier::where('name', $source)->exists();
            if (!$exists) {
                Supplier::create([
                    'name' => $source,
                    'created_at' => now()
                ]);
            }
        }

        return response()->json(['success' => true]);
    }

    private function updateOrderFinancials(Request $request)
    {
        $id = (int) $request->input('id');
        if ($id <= 0) {
            return response()->json(['success' => false, 'message' => 'ID inválido.'], 400);
        }

        $amount = (float) $request->input('amount', 0);
        $method = (string) $request->input('payment_method');
        if ($method !== '') {
            $method = mb_strtoupper(trim($method), 'UTF-8');
        }
        $date = $request->input('date');

        $updateData = [
            'total' => $amount,
            'payment_method' => $method,
        ];

        if ($date) {
            $updateData['created_at'] = $date . ' ' . date('H:i:s');
        }

        DB::table('orders')
            ->where('id', $id)
            ->update($updateData);

        return response()->json(['success' => true]);
    }

    private function updateOrderCustomer(Request $request)
    {
        $orderId = (int) $request->input('order_id');
        $customer = $request->input('customer_name');
        
        if ($orderId <= 0) {
            return response()->json(['success' => false, 'message' => 'ID inválido'], 400);
        }

        DB::table('orders')->where('id', $orderId)->update(['customer_name' => $customer]);
        
        return response()->json(['success' => true]);
    }

    private function getOrderDetails(Request $request)
    {
        $orderId = (int) $request->input('order_id');
        if ($orderId <= 0) {
            return response()->json(['success' => false, 'message' => 'ID inválido'], 400);
        }
        
        $items = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('order_items.order_id', $orderId)
            ->select('order_items.id', 'products.name', 'order_items.quantity', 'order_items.price')
            ->orderBy('order_items.created_at', 'desc')
            ->get();
            
        return response()->json($items);
    }

    private function getServerIp()
    {
        $localIP = getHostByName(getHostName());

        if (function_exists('socket_create')) {
            try {
                $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
                if ($sock) {
                    @socket_connect($sock, '8.8.8.8', 53);
                    $socketIP = null;
                    @socket_getsockname($sock, $socketIP);
                    @socket_close($sock);
                    if ($socketIP) {
                        $localIP = $socketIP;
                    }
                }
            } catch (\Throwable $e) {
            }
        }

        if ((!$localIP || $localIP === '127.0.0.1' || str_starts_with($localIP, '26.')) && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            @exec('ipconfig', $output);
            $candidates = [];
            if ($output) {
                foreach ($output as $line) {
                    if (preg_match('/IPv4.*:\s*(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/', $line, $matches)) {
                        $ip = $matches[1];
                        if ($ip === '127.0.0.1') {
                            continue;
                        }
                        $candidates[] = $ip;
                    }
                }
            }

            foreach ($candidates as $ip) {
                if (str_starts_with($ip, '192.168.')) {
                    $localIP = $ip;
                    break;
                }
                if (str_starts_with($ip, '10.')) {
                    $localIP = $ip;
                    break;
                }
                if (str_starts_with($ip, '172.')) {
                    $localIP = $ip;
                    break;
                }
            }
        }

        return response()->json(['ip' => $localIP]);
    }

    private function lookupNfceItems(Request $request)
    {
        $url = $request->input('url');
        if (!$url) {
            return response()->json(['success' => false, 'message' => 'URL não fornecida.']);
        }

        $data = $this->scrapeNfceData($url);

        if (isset($data['error'])) {
            return response()->json(['success' => false, 'message' => $data['error']]);
        }

        return response()->json($data);
    }

    private function scrapeNfceData($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['error' => 'URL inválida.'];
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 15,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'header' => "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8\r\n"
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ]);

        $html = @file_get_contents($url, false, $context);

        if (!$html) {
            return ['error' => 'Não foi possível acessar a URL (pode haver bloqueio ou erro de rede).'];
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML($html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);

        // Extract Supplier Name
        $supplierName = '';
        $topoNodes = $xpath->query('//*[contains(@class, "txtTopo")]');
        if ($topoNodes->length > 0) {
            $supplierName = mb_strtoupper(trim($topoNodes->item(0)->textContent), 'UTF-8');
        } else {
             $strongs = $xpath->query('//div[@id="conteudo"]//div[1]');
             if ($strongs->length > 0) {
                 $supplierName = mb_strtoupper(trim($strongs->item(0)->textContent), 'UTF-8');
             }
        }
        
        $purchaseDateIso = null;
        $purchaseDateBr = null;

        if (preg_match('/Emiss[aã]o[^0-9]*(\d{2}\/\d{2}\/\d{4})/i', $html, $matches)) {
            $purchaseDateBr = $matches[1];
        } elseif (preg_match('/Data\s+de\s+Emiss[aã]o[^0-9]*(\d{2}\/\d{2}\/\d{4})/i', $html, $matches)) {
            $purchaseDateBr = $matches[1];
        } elseif (preg_match('/Data\s+da\s+Compra[^0-9]*(\d{2}\/\d{2}\/\d{4})/i', $html, $matches)) {
            $purchaseDateBr = $matches[1];
        }

        if ($purchaseDateBr) {
            $parts = explode('/', $purchaseDateBr);
            if (count($parts) === 3) {
                $purchaseDateIso = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
            }
        }

        $items = [];
        $rows = $xpath->query('//tr[starts-with(@id, "Item")] | //tr[contains(@class, "item")] | //table[contains(@class, "table")]//tr');

        if ($rows->length === 0) {
            $rows = $xpath->query('//tr');
        }

        foreach ($rows as $row) {
            $text = $row->textContent;
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);
            
            $qty = 1;
            $unitPrice = 0;
            $name = '';
            $found = false;

            if (preg_match('/Qtde\.?:?\s*([\d\.,]+)/i', $text, $mQty) && 
                preg_match('/Vl\.?\s*Unit\.?:?\s*([\d\.,]+)/i', $text, $mUnit)) {
                
                $found = true;
                $nameParts = preg_split('/(Qtde|Código|Vl\.|Un:)/i', $text);
                if (count($nameParts) > 0) {
                    $name = trim($nameParts[0]);
                }

                $qStr = str_replace('.', '', $mQty[1]);
                $qStr = str_replace(',', '.', $qStr);
                $qty = (float) $qStr;
                
                $uStr = str_replace('.', '', $mUnit[1]);
                $uStr = str_replace(',', '.', $uStr);
                $unitPrice = (float) $uStr;
            } 
            else {
                $cols = $xpath->query('.//td', $row);
                if ($cols->length >= 3) {
                    $nameNode = $xpath->query('.//*[contains(@class, "txtTit")]', $row)->item(0);
                    $qtyNode = $xpath->query('.//*[contains(@class, "qtd") or contains(@class, "Rqtd")]', $row)->item(0);
                    $unitNode = $xpath->query('.//*[contains(@class, "vlUnit") or contains(@class, "RvlUnit") or contains(@class, "vUnCom")]', $row)->item(0);
                    $totalNode = $xpath->query('.//*[contains(@class, "vlItem") or contains(@class, "RvlItem") or contains(@class, "vProd") or contains(@class, "valor")]', $row)->item(0);

                    if ($nameNode && ($unitNode || $totalNode)) {
                        $found = true;
                        $name = trim($nameNode->textContent);

                        if ($qtyNode) {
                            $qStr = trim($qtyNode->textContent);
                            $qStr = preg_replace('/[^0-9\.,]/', '', $qStr);
                            $qStr = str_replace('.', '', $qStr);
                            $qStr = str_replace(',', '.', $qStr);
                            $qtyCandidate = (float) $qStr;
                            if ($qtyCandidate > 0) {
                                $qty = $qtyCandidate;
                            }
                        }

                        if ($unitNode) {
                            $uStr = trim($unitNode->textContent);
                            $uStr = preg_replace('/[^0-9\.,]/', '', $uStr);
                            $uStr = str_replace('.', '', $uStr);
                            $uStr = str_replace(',', '.', $uStr);
                            $unitPrice = (float) $uStr;
                        } elseif ($totalNode) {
                            $tStr = trim($totalNode->textContent);
                            $tStr = preg_replace('/[^0-9\.,]/', '', $tStr);
                            $tStr = str_replace('.', '', $tStr);
                            $tStr = str_replace(',', '.', $tStr);
                            $totalCandidate = (float) $tStr;
                            if ($totalCandidate > 0 && $qty > 0) {
                                $unitPrice = $totalCandidate / $qty;
                            }
                        }
                    }
                }
            }

            if ($found && $name && $unitPrice > 0) {
                $name = preg_replace('/\(Código:?\s*\d+\)/i', '', $name);
                $name = trim($name);
                
                $items[] = [
                    'name' => $name,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'total_price' => $qty * $unitPrice
                ];
            }
        }

        if (empty($items)) {
            foreach ($rows as $row) {
                $text = $row->textContent;
                $text = preg_replace('/\s+/', ' ', $text);
                $text = trim($text);

                if ($text === '') {
                    continue;
                }

                if (stripos($text, 'total') !== false || stripos($text, 'subtotal') !== false || stripos($text, 'tribut') !== false) {
                    continue;
                }

                if (!preg_match_all('/\d+[\.]*\d*,\d{2}/', $text, $matches)) {
                    continue;
                }

                $numberTokens = $matches[0];
                $lastToken = end($numberTokens);

                $qty = 1.0;
                $qtyMatch = null;
                if (preg_match('/Qtde\.?:?\s*([\d\.,]+)/i', $text, $qtyMatch) ||
                    preg_match('/Qtd\.?:?\s*([\d\.,]+)/i', $text, $qtyMatch)) {
                    $qStr = preg_replace('/[^0-9\.,]/', '', $qtyMatch[1]);
                    $qStr = str_replace('.', '', $qStr);
                    $qStr = str_replace(',', '.', $qStr);
                    $qVal = (float) $qStr;
                    if ($qVal > 0) {
                        $qty = $qVal;
                    }
                } elseif (preg_match('/([0-9]+)\s*(UN|KG|L|UNID|PT|BD|CJ|FR|PO|VD|LA)/i', $text, $qtyMatch)) {
                    $qVal = (float) $qtyMatch[1];
                    if ($qVal > 0) {
                        $qty = $qVal;
                    }
                }

                $tStr = preg_replace('/[^0-9\.,]/', '', $lastToken);
                $tStr = str_replace('.', '', $tStr);
                $tStr = str_replace(',', '.', $tStr);
                $totalVal = (float) $tStr;
                if ($totalVal <= 0) {
                    continue;
                }

                $namePart = preg_replace('/\d+[\.]*\d*,\d{2}/', '', $text);
                $namePart = preg_replace('/Qtde\.?:?[^A-Za-z]+/i', '', $namePart);
                $namePart = preg_replace('/Qtd\.?:?[^A-Za-z]+/i', '', $namePart);
                $namePart = preg_replace('/[0-9]+x/i', '', $namePart);
                $namePart = trim($namePart);

                if ($namePart === '') {
                    continue;
                }

                $unitPrice = $qty > 0 ? $totalVal / $qty : $totalVal;

                $items[] = [
                    'name' => $namePart,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalVal
                ];
            }
        }

        if (empty($items)) {
            $fullText = $dom->textContent;
            $fullText = preg_replace('/\s+/', ' ', $fullText);

            $pattern = '/(.+?)\s*\(Código:\s*([0-9]+)\s*\)\s*Qtde\.\s*:\s*([\d\.,]+)\s*UN:\s*([A-Z]+)\s*Vl\. Unit\.\s*:\s*([\d\.,]+)\s*Vl\. Total\s*([\d\.,]+)/iu';
            if (preg_match_all($pattern, $fullText, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $name = trim($m[1]);
                    $qtyStr = str_replace('.', '', $m[3]);
                    $qtyStr = str_replace(',', '.', $qtyStr);
                    $qty = (float) $qtyStr;

                    $unitStr = str_replace('.', '', $m[5]);
                    $unitStr = str_replace(',', '.', $unitStr);
                    $unitPrice = (float) $unitStr;

                    $totalStr = str_replace('.', '', $m[6]);
                    $totalStr = str_replace(',', '.', $totalStr);
                    $total = (float) $totalStr;

                    if ($name && $qty > 0 && $unitPrice > 0 && $total > 0) {
                        $items[] = [
                            'name' => $name,
                            'quantity' => $qty,
                            'unit_price' => $unitPrice,
                            'total_price' => $total,
                        ];
                    }
                }
            }
        }

        $totalAmount = 0.0;
        foreach ($items as $it) {
            $totalAmount += isset($it['total_price']) ? (float) $it['total_price'] : 0.0;
        }

        if ($totalAmount == 0) {
            if (preg_match('/(Valor\s+Total|VALOR\s+TOTAL|Valor\s+a\s+Pagar|VALOR\s+A\s+PAGAR)[^0-9]+([\d\.\,]+)\b/i', $html, $mGlobal)) {
                $gStr = preg_replace('/[^0-9\.,]/', '', $mGlobal[2]);
                $gStr = str_replace('.', '', $gStr);
                $gStr = str_replace(',', '.', $gStr);
                $totalAmount = (float) $gStr;
            }
        }

        if ($totalAmount == 0) {
             $totalNode = $xpath->query('//*[contains(text(), "Valor Total")]/following-sibling::*');
             if ($totalNode->length > 0) {
                 $tStr = trim($totalNode->item(0)->textContent);
                 $tStr = str_replace('R$', '', $tStr);
                 $tStr = trim($tStr);
                 $tStr = str_replace('.', '', $tStr);
                 $tStr = str_replace(',', '.', $tStr);
                 $totalAmount = (float) $tStr;
             }
        }

        return [
            'success' => true,
            'items' => $items,
            'total_amount' => $totalAmount,
            'supplier_name' => $supplierName,
            'purchase_date' => $purchaseDateIso,
            'purchase_date_br' => $purchaseDateBr,
        ];
    }

    private function storePendingNote(Request $request)
    {
        $url = $request->input('url');
        if (!$url) {
            return response()->json(['success' => false, 'message' => 'URL não fornecida.']);
        }

        $exists = PendingNote::where('url', $url)->where('status', 'pending')->first();
        if ($exists) {
            return response()->json(['success' => true, 'message' => 'Nota já está na lista pendente.']);
        }

        $data = $this->scrapeNfceData($url);
        
        $supplier = (isset($data['supplier_name']) && $data['supplier_name']) ? $data['supplier_name'] : 'Desconhecido';
        $total = (isset($data['total_amount'])) ? $data['total_amount'] : 0;
        $date = (isset($data['purchase_date'])) ? $data['purchase_date'] : null;

        PendingNote::create([
            'url' => $url,
            'supplier_name' => $supplier,
            'total_amount' => $total,
            'emission_date' => $date,
            'status' => 'pending'
        ]);

        return response()->json(['success' => true]);
    }

    private function getPendingNotes()
    {
        $notes = PendingNote::where('status', 'pending')->orderByDesc('created_at')->get();
        return response()->json($notes);
    }

    private function getPendingNoteDetails(Request $request)
    {
        $id = $request->input('id');
        $note = PendingNote::find($id);
        if ($note) {
            return response()->json($note);
        }
        return response()->json(['success' => false, 'message' => 'Nota não encontrada']);
    }

    private function deletePendingNote(Request $request)
    {
        $id = $request->input('id');
        $note = PendingNote::find($id);
        if ($note) {
            $note->delete();
        }
        return response()->json(['success' => true]);
    }

    private function deleteAllPendingNotes()
    {
        PendingNote::where('status', 'pending')->delete();
        return response()->json(['success' => true]);
    }

    private function resetTestData()
    {
        DB::table('order_items')->delete();
        DB::table('orders')->delete();

        return response()->json(['success' => true]);
    }

    private function syncSuppliers()
    {
        $count = 0;

        // Sync Expenses
        $expenses = Expense::select('supplier')->distinct()->whereNotNull('supplier')->where('supplier', '!=', '')->get();
        foreach ($expenses as $exp) {
            $exists = Supplier::where('name', $exp->supplier)->exists();
            if (!$exists) {
                Supplier::create([
                    'name' => $exp->supplier,
                    'created_at' => now()
                ]);
                $count++;
            }
        }

        // Sync Revenues
        $revenues = Revenue::select('source')->distinct()->whereNotNull('source')->where('source', '!=', '')->get();
        foreach ($revenues as $rev) {
            $exists = Supplier::where('name', $rev->source)->exists();
            if (!$exists) {
                Supplier::create([
                    'name' => $rev->source,
                    'created_at' => now()
                ]);
                $count++;
            }
        }

        return response()->json(['success' => true, 'count' => $count]);
    }

    private function searchSuppliers(Request $request)
    {
        $query = $request->input('query');
        if (!$query) {
            return response()->json([]);
        }

        $suppliers = Supplier::where('name', 'like', '%' . $query . '%')
            ->orderBy('name')
            ->limit(10)
            ->get();

        return response()->json($suppliers);
    }

    private function searchDescriptions(Request $request)
    {
        $q = $request->input('query');
        if (!$q || mb_strlen($q) < 1) {
            return response()->json([]);
        }

        $results = Transaction::select('description as name')
            ->where('type', 'expense')
            ->where('description', 'like', '%' . $q . '%')
            ->distinct()
            ->limit(10)
            ->get();

        return response()->json($results);
    }

    private function getDailySummaryDates()
    {
        $dates = DB::table('orders')
            ->select(DB::raw('DATE(DATE_SUB(created_at, INTERVAL 3 HOUR)) as sale_date'))
            ->where('status', 'paid')
            ->distinct()
            ->orderByDesc('sale_date')
            ->pluck('sale_date');

        return response()->json($dates);
    }

    private function getDailySummaryData(Request $request)
    {
        $date = $request->input('date');
        if (!$date) {
            // Default to today if no date provided (adjust for timezone -3h)
            $date = now()->subHours(3)->format('Y-m-d');
        }

        $orders = DB::table('orders')
            ->select('payment_method', DB::raw('SUM(total) as total'), DB::raw('COUNT(*) as count'))
            ->where('status', 'paid')
            ->where('payment_method', '<>', 'CADERNETA')
            ->whereRaw('DATE(DATE_SUB(created_at, INTERVAL 3 HOUR)) = ?', [$date])
            ->groupBy('payment_method')
            ->get();

        $formatted = [];
        $totalRevenue = 0.0;
        foreach ($orders as $row) {
            $key = $row->payment_method ? mb_strtoupper($row->payment_method, 'UTF-8') : 'OUTROS';
            if ($key === 'ESTORNO') continue;
            
            if (!isset($formatted[$key])) {
                $formatted[$key] = [
                    'total' => 0.0,
                    'count' => 0
                ];
            }
            $formatted[$key]['total'] += (float) $row->total;
            $formatted[$key]['count'] += (int) $row->count;
            $totalRevenue += (float) $row->total;
        }

        return response()->json([
            'payment_methods' => $formatted,
            'total_revenue' => $totalRevenue
        ]);
    }

    private function getDebts()
    {
        $debts = Order::select('id', 'customer_name', 'total', 'created_at', 'paid_at', 'debt_due_date')
            ->where('payment_method', 'CADERNETA')
            ->whereNull('debt_paid_at')
            ->where('status', 'paid')
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($debts as $debt) {
            $debt->items = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('order_id', $debt->id)
                ->select('products.name as product_name', 'order_items.quantity', 'order_items.price')
                ->get();
        }

        return response()->json($debts);
    }

    private function settleDebt(Request $request)
    {
        $orderId = (int) $request->input('order_id');
        $method = $request->input('payment_method');
        $createRevenue = $request->input('create_revenue');
        
        // Handle boolean or string inputs
        if ($createRevenue === 'true') $createRevenue = true;
        if ($createRevenue === 'false') $createRevenue = false;

        if ($orderId <= 0) {
            return response()->json(['success' => false, 'message' => 'ID inválido']);
        }

        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Pedido não encontrado']);
        }

        $order->debt_paid_at = now();
        $order->save();

        if ($createRevenue) {
            $desc = "Recebimento Fiado - Pedido #{$orderId} " . ($order->customer_name ? "({$order->customer_name})" : "");
            $transaction = Transaction::create([
                'type' => 'income',
                'description' => $desc,
                'amount' => $order->total,
                'transaction_date' => date('Y-m-d H:i:s'),
                'payment_method' => $method,
                'category' => 'CADERNETA',
                'responsible' => 'caixa',
                'order_id' => $orderId,
                'original_source' => 'orders',
                'original_id' => $orderId,
            ]);

            // Add items to transaction
            $orderItems = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('order_id', $orderId)
                ->select('order_items.*', 'products.name as product_name')
                ->get();

            foreach ($orderItems as $item) {
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $item->product_id,
                    'name' => $item->product_name ?: 'Produto #' . $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->price,
                    'total_price' => $item->quantity * $item->price,
                ]);
            }
        }

        return response()->json(['success' => true]);
    }

}
