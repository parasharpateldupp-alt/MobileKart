<?php
session_start();
['user_id'] = 1;
['user_name'] = 'PATANJALI';
['user_email'] = 'patanjali@patu.com';
['user_role'] = 'ADMIN';
['role'] = 'ADMIN';
require_once dirname(__DIR__) . '/admin/dashboard.php';
