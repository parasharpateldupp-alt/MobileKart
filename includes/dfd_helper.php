<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * DFD Architecture Helper & Data Flow Audit Module
 * 
 * Maps processes P1.1 to P1.5 and Data Stores D1 to D5:
 *   - P1.1 Manage User Accounts (D1: USER DB)
 *   - P1.2 Catalog & Inventory (D2: PRODUCT / INVENTORY DB)
 *   - P1.3 Order Processing (D3: ORDERS DB)
 *       - P1.3.1 Verify Order
 *       - P1.3.2 Calculate Sub-total
 *       - P1.3.3 Reserve Stock
 *   - P1.4 Payment & Billing (D4: BILLING DB)
 *       - P1.4.1 Verify Customer Auth
 *       - P1.4.2 Process Gateway Auth
 *       - P1.4.3 Generate Invoice
 *   - P1.5 Fulfillment & Tracking (D5: FULFILLMENT DB)
 */

class DFDManager {
    public const PROCESS_MAP = [
        'P1.1' => [
            'name' => 'Manage User Accounts',
            'datastore' => 'D1: USER DB (users, suppliers)',
            'inputs' => ['Registration Data', 'Login Credentials', 'Profile Updates'],
            'outputs' => ['Session Auth Token', 'User Logs', 'Profile Confirmation']
        ],
        'P1.2' => [
            'name' => 'Catalog & Inventory',
            'datastore' => 'D2: PRODUCT / INVENTORY DB (products, categories, brands, inventory_transactions)',
            'inputs' => ['Product Specifications', 'Stock Adjustments', 'Restock Alerts'],
            'outputs' => ['Live Catalog', 'Stock Availability Check', 'Low-Stock Notifications']
        ],
        'P1.3' => [
            'name' => 'Order Processing',
            'datastore' => 'D3: ORDERS DB (cart, cart_items, orders, order_items, stock_reservations)',
            'inputs' => ['Cart Items', 'Shipping Details', 'Stock Alert Levels'],
            'outputs' => ['Verified Order Info', 'Order for Payment', 'Stock Hold / Release']
        ],
        'P1.4' => [
            'name' => 'Payment & Billing',
            'datastore' => 'D4: BILLING DB (payments, invoices, invoice_items)',
            'inputs' => ['Encrypted Payment Token', 'Bank Gateway Auth Code'],
            'outputs' => ['Payment Authorization Receipt', 'Tax Invoice', 'Ledger Updates']
        ],
        'P1.5' => [
            'name' => 'Fulfillment & Tracking',
            'datastore' => 'D5: FULFILLMENT DB (fulfillments, shipment_events)',
            'inputs' => ['Confirmed Paid Order', 'Shipment Dispatch Info', 'Tracking ID'],
            'outputs' => ['Shipment Manifest', 'Live Tracking Timeline', 'Delivery Confirmation']
        ]
    ];

    /**
     * Audit log a DFD data flow event
     */
    public static function logFlow($processId, $action, $details = []) {
        $proc = self::PROCESS_MAP[$processId] ?? ['name' => 'Unknown Process', 'datastore' => 'General'];
        $logPayload = [
            'dfd_process' => $processId . ' (' . $proc['name'] . ')',
            'datastore'   => $proc['datastore'],
            'action'      => $action,
            'metadata'    => $details
        ];
        
        $userId = $_SESSION['user_id'] ?? null;
        if (function_exists('log_activity')) {
            log_activity($userId, "DFD_{$processId}_{$action}", 'DFD_FLOW', null, $logPayload);
        }
    }
}
