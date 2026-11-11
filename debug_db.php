<?php
include 'legacy/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$sql = "SELECT * FROM orders WHERE status = 'open'";
$res = $conn->query($sql);
$orders = [];
while($row = $res->fetch_assoc()) {
    $orders[] = $row;
}

echo "Open Orders Count: " . count($orders) . "\n";
print_r($orders);

// Check items for first order if exists
if(count($orders) > 0) {
    $oid = $orders[0]['id'];
    echo "Items for Order $oid:\n";
    $sql_items = "SELECT * FROM order_items WHERE order_id = $oid";
    $res_items = $conn->query($sql_items);
    while($row = $res_items->fetch_assoc()) {
        print_r($row);
    }
}
?>