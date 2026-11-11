<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Manually invoke the logic or just use DB
use Illuminate\Support\Facades\DB;

$startDate = '2026-01-01 00:00:00';
$endDate = '2026-12-31 23:59:59';

$orderList = DB::table('orders')
    ->where('status', 'paid')
    ->where('payment_method', '<>', 'CADERNETA')
    ->whereBetween('created_at', [$startDate, $endDate])
    ->orderByDesc('created_at')
    ->get();

$dailyOrders = DB::table('orders')
    ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total) as total'))
    ->where('status', 'paid')
    ->where('payment_method', '<>', 'CADERNETA')
    ->whereBetween('created_at', [$startDate, $endDate])
    ->groupBy(DB::raw('DATE(created_at)'))
    ->get();

echo "Daily Orders Count: " . $dailyOrders->count() . "\n";
echo "Order List Count: " . $orderList->count() . "\n";

if ($dailyOrders->count() > 0) {
    echo "First Daily Order Date: " . $dailyOrders->first()->date . "\n";
}

if ($orderList->count() > 0) {
    echo "First Order Created At: " . $orderList->first()->created_at . "\n";
}
