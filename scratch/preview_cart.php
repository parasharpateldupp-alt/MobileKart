<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'PATANJALI';
$_SESSION['user_email'] = 'patanjali@patu.com';
$_SESSION['user_role'] = 'CUSTOMER';
$_SESSION['cart'] = [
    1 => ['product_id' => 1, 'quantity' => 1, 'price' => 121499.00, 'name' => 'Samsung Galaxy S24 Ultra 5G', 'image' => 'assets/images/products/s24_ultra.svg'],
    3 => ['product_id' => 3, 'quantity' => 1, 'price' => 64399.00, 'name' => 'OnePlus 12 5G', 'image' => 'assets/images/products/oneplus12.svg']
];
require_once dirname(__DIR__) . '/customer/cart.php';
