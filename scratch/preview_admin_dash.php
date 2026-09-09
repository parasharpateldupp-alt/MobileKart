<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'PATANJALI';
$_SESSION['user_email'] = 'patanjali@patu.com';
$_SESSION['user_role'] = 'ADMIN';
require_once dirname(__DIR__) . '/admin/dashboard.php';
