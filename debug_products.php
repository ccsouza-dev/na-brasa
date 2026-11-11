<?php
require __DIR__ . '/public/index.php'; // Bootstrap Laravel

use App\Models\Product;

$categories = Product::distinct()->pluck('category');
echo "Categories:\n";
foreach ($categories as $cat) {
    echo "- $cat\n";
}

echo "\nSample Bebidas:\n";
$bebidas = Product::where('category', 'LIKE', '%BEBIDA%')->limit(20)->get();
foreach ($bebidas as $p) {
    echo "- {$p->name} ({$p->category})\n";
}
