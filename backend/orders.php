<?php
require_once 'config.php';

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Place a new order
    $data = json_decode(file_get_contents('php://input'), true);

    $name    = trim($data['customer_name'] ?? '');
    $phone   = trim($data['customer_phone'] ?? '');
    $address = trim($data['customer_address'] ?? '');
    $items   = $data['items'] ?? [];
    $total   = floatval($data['total_amount'] ?? 0);
    $payment = $data['payment_method'] ?? 'cash';

    if (!$name || !$phone || !$address || empty($items) || $total <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    $itemsJson = json_encode($items);
    $stmt = $db->prepare(
        "INSERT INTO orders (customer_name, customer_phone, customer_address, items, total_amount, payment_method)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('ssssds', $name, $phone, $address, $itemsJson, $total, $payment);

    if ($stmt->execute()) {
        $orderId = $db->insert_id;
        echo json_encode(['success' => true, 'order_id' => $orderId, 'message' => 'Order placed successfully!']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to place order']);
    }

} elseif ($method === 'GET') {
    // Get order status by ID
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Order ID required']);
        exit;
    }
    $stmt = $db->prepare("SELECT id, customer_name, status, total_amount, created_at FROM orders WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
    }
}

$db->close();
