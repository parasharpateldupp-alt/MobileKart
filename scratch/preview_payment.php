<?php
session_start();
require_once dirname(__DIR__) . '/config/database.php';
$db = get_db_connection();
$ord = $db->query("SELECT order_id, customer_id, total_amount FROM orders WHERE payment_status = 'PENDING' ORDER BY order_id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$ordId = $ord ? $ord['order_id'] : 21;
$custId = $ord ? $ord['customer_id'] : 7;
$amt = $ord ? $ord['total_amount'] : 143368.82;
$payId = $db->query("SELECT payment_id FROM payments WHERE order_id = $ordId LIMIT 1")->fetchColumn() ?: 12;
$_SESSION['user_id'] = $custId;
$_SESSION['user_name'] = 'Rahul Sharma';
$_SESSION['user_email'] = 'customer@gmail.com';
$_SESSION['user_role'] = 'CUSTOMER';
$payload = json_encode(['order_id' => (int)$ordId, 'payment_id' => (int)$payId, 'amount' => (float)$amt]);
$sig = hash_hmac('sha256', $payload, 'MOCK_SECRET_KEY_ACADEMIC_2026');
$_GET['token'] = base64_encode($payload . '||' . $sig);
require_once dirname(__DIR__) . '/payment/mock_gateway.php';
