<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'PATANJALI';
$_SESSION['user_email'] = 'patanjali@patu.com';
$_SESSION['user_role'] = 'CUSTOMER';
$_SESSION['cart'] = [
    1 => ['product_id' => 1, 'quantity' => 1, 'price' => 121499.00, 'name' => 'Samsung Galaxy S24 Ultra 5G', 'image' => 'assets/images/products/s24_ultra.svg']
];
require_once dirname(__DIR__) . '/customer/checkout.php';
