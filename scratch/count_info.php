<?php
require_once dirname(__DIR__) . '/config/database.php';
$db = get_db_connection();
$invCount = $db->query('SELECT COUNT(*) FROM invoices')->fetchColumn();
$ordCount = $db->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$orders = $db->query('SELECT order_id, order_number, total_amount, order_status FROM orders LIMIT 3')->fetchAll(PDO::FETCH_ASSOC);
$invoices = $db->query('SELECT invoice_id, invoice_number, order_id, customer_id FROM invoices LIMIT 3')->fetchAll(PDO::FETCH_ASSOC);
echo "INVOICES COUNT: $invCount
";
echo "ORDERS COUNT: $ordCount
";
echo "ORDERS: " . json_encode($orders) . "
";
echo "INVOICES: " . json_encode($invoices) . "
";
