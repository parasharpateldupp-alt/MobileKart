<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'PATANJALI';
$_SESSION['user_email'] = 'patanjali@patu.com';
$_SESSION['user_role'] = 'ADMIN';
require_once dirname(__DIR__) . '/config/database.php';
$db = get_db_connection();
$inv = $db->query("SELECT invoice_id FROM invoices ORDER BY invoice_id DESC LIMIT 1")->fetchColumn();
$_GET['id'] = $inv ?: 10;
require_once dirname(__DIR__) . '/customer/invoices.php';
