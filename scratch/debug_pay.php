<?php
session_start();
require_once dirname(__DIR__) . '/config/database.php';
$db = get_db_connection();
$ord = $db->query("SELECT order_id, customer_id, total_amount FROM orders ORDER BY order_id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
echo "ORD: " . json_encode($ord) . "
";
$user = $db->query("SELECT user_id, role, name, email FROM users WHERE user_id = " . (int)$ord['customer_id'])->fetch(PDO::FETCH_ASSOC);
echo "USER: " . json_encode($user) . "
";
