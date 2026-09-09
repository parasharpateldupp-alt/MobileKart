<?php
require_once dirname(__DIR__) . '/config/database.php';
 = get_db_connection();
 = ->query('SELECT order_id, order_number FROM orders LIMIT 5');
 = ->fetchAll(PDO::FETCH_ASSOC);
echo json_encode();
