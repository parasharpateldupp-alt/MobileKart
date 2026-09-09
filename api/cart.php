<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Cart API Controller (DFD P1.3 Order Processing)
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$db = get_db_connection();
$userId = $_SESSION['user_id'] ?? null;

// Helper to get or create cart for user
function get_user_cart_id($db, $userId) {
    $stmt = $db->prepare("SELECT cart_id FROM cart WHERE user_id = ?");
    $stmt->execute([$userId]);
    $cartId = $stmt->fetchColumn();
    if (!$cartId) {
        $ins = $db->prepare("INSERT INTO cart (user_id) VALUES (?)");
        $ins->execute([$userId]);
        $cartId = $db->lastInsertId();
    }
    return (int)$cartId;
}

try {
    switch ($action) {
        case 'add':
            $productId = (int)($_POST['product_id'] ?? 0);
            $qty = max(1, (int)($_POST['quantity'] ?? 1));

            // Validate product
            $pStmt = $db->prepare("SELECT product_id, product_name, stock_quantity, status FROM products WHERE product_id = ?");
            $pStmt->execute([$productId]);
            $product = $pStmt->fetch();

            if (!$product || $product['status'] !== 'ACTIVE') {
                echo json_encode(['success' => false, 'message' => 'Product is currently unavailable.']);
                exit;
            }

            if ($product['stock_quantity'] < 1) {
                echo json_encode(['success' => false, 'message' => 'Sorry, this smartphone is out of stock.']);
                exit;
            }

            if ($userId) {
                $cartId = get_user_cart_id($db, $userId);

                // Current qty in cart
                $cQtyStmt = $db->prepare("SELECT quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
                $cQtyStmt->execute([$cartId, $productId]);
                $existingQty = (int)$cQtyStmt->fetchColumn();

                $newQty = $existingQty + $qty;
                if ($newQty > $product['stock_quantity']) {
                    echo json_encode([
                        'success' => false,
                        'message' => "Requested quantity exceeds available stock (Only {$product['stock_quantity']} available)."
                    ]);
                    exit;
                }

                $upsert = $db->prepare("
                    INSERT INTO cart_items (cart_id, product_id, quantity)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
                ");
                $upsert->execute([$cartId, $productId, $qty]);
            } else {
                // Guest session cart
                if (!isset($_SESSION['guest_cart'])) {
                    $_SESSION['guest_cart'] = [];
                }
                $existing = $_SESSION['guest_cart'][$productId] ?? 0;
                $newQty = $existing + $qty;
                if ($newQty > $product['stock_quantity']) {
                    echo json_encode([
                        'success' => false,
                        'message' => "Requested quantity exceeds available stock (Only {$product['stock_quantity']} available)."
                    ]);
                    exit;
                }
                $_SESSION['guest_cart'][$productId] = $newQty;
            }

            $totalItems = get_cart_count();
            echo json_encode([
                'success' => true,
                'message' => "{$product['product_name']} added to your cart.",
                'total_items' => $totalItems
            ]);
            break;

        case 'update':
            $cartItemId = (int)($_POST['cart_item_id'] ?? 0);
            $qty = (int)($_POST['quantity'] ?? 1);

            if ($userId) {
                // Check stock
                $chk = $db->prepare("
                    SELECT ci.cart_item_id, ci.product_id, p.stock_quantity, p.product_name 
                    FROM cart_items ci 
                    JOIN cart c ON ci.cart_id = c.cart_id 
                    JOIN products p ON ci.product_id = p.product_id 
                    WHERE ci.cart_item_id = ? AND c.user_id = ?
                ");
                $chk->execute([$cartItemId, $userId]);
                $item = $chk->fetch();

                if (!$item) {
                    echo json_encode(['success' => false, 'message' => 'Cart item not found.']);
                    exit;
                }

                if ($qty > $item['stock_quantity']) {
                    echo json_encode([
                        'success' => false,
                        'message' => "Cannot set quantity above available stock ({$item['stock_quantity']})."
                    ]);
                    exit;
                }

                if ($qty <= 0) {
                    $del = $db->prepare("DELETE FROM cart_items WHERE cart_item_id = ?");
                    $del->execute([$cartItemId]);
                } else {
                    $up = $db->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?");
                    $up->execute([$qty, $cartItemId]);
                }
            } else {
                // Guest cart by product_id
                $productId = $cartItemId;
                if ($qty <= 0) {
                    unset($_SESSION['guest_cart'][$productId]);
                } else {
                    $_SESSION['guest_cart'][$productId] = $qty;
                }
            }

            echo json_encode(['success' => true, 'total_items' => get_cart_count()]);
            break;

        case 'remove':
            $cartItemId = (int)($_POST['cart_item_id'] ?? 0);
            if ($userId) {
                $del = $db->prepare("
                    DELETE ci FROM cart_items ci
                    JOIN cart c ON ci.cart_id = c.cart_id
                    WHERE ci.cart_item_id = ? AND c.user_id = ?
                ");
                $del->execute([$cartItemId, $userId]);
            } else {
                unset($_SESSION['guest_cart'][$cartItemId]);
            }

            echo json_encode(['success' => true, 'total_items' => get_cart_count()]);
            break;

        case 'count':
            echo json_encode(['success' => true, 'total_items' => get_cart_count()]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid API action.']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Cart processing error: ' . $e->getMessage()]);
}
