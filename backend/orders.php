<?php
require_once 'config.php';

set_error_handler(function($errno, $errstr) {
    error_log("AyoFoods orders error [$errno]: $errstr");
    return true;
});

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ── Helper: decode user token from Authorization header ───────────────────────
function getUserFromToken($db): ?array {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/Bearer\s+(.+)/i', $header, $m)) return null;
    $token   = trim($m[1]);
    $payload = json_decode(base64_decode($token), true);
    if (!$payload || empty($payload['id']) || empty($payload['email'])) return null;

    $stmt = $db->prepare("SELECT id, name, email FROM users WHERE id=? AND email=? AND is_verified=1");
    $stmt->bind_param('is', $payload['id'], $payload['email']);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

// ── GET: my_orders or order by ID ─────────────────────────────────────────────
if ($method === 'GET') {

    if ($action === 'my_orders') {
        $user = getUserFromToken($db);
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required.']);
            exit;
        }

        $stmt = $db->prepare(
            "SELECT id, customer_name, customer_phone, customer_address, items, total_amount,
                    status, payment_method, created_at
             FROM orders WHERE user_id=? ORDER BY created_at DESC"
        );
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $row['items'] = json_decode($row['items'], true);
            $orders[]     = $row;
        }
        echo json_encode(['success' => true, 'data' => $orders]);
        exit;
    }

    // Get single order by ID
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
    exit;
}

// ── POST: place order ─────────────────────────────────────────────────────────
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $name    = trim($data['customer_name']    ?? '');
    $phone   = trim($data['customer_phone']   ?? '');
    $address = trim($data['customer_address'] ?? '');
    $items   = $data['items']                 ?? [];
    $total   = floatval($data['total_amount'] ?? 0);
    $payment = $data['payment_method']        ?? 'cash';

    if (!$name || !$phone || !$address || empty($items) || $total <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    // Validate payment method
    $validPayments = ['cash', 'card', 'transfer', 'upi'];
    if (!in_array($payment, $validPayments)) $payment = 'cash';

    // Extract user_id from token if present (optional — guest orders allowed)
    $user   = getUserFromToken($db);
    $userId = $user ? $user['id'] : null;

    $itemsJson = json_encode($items);

    if ($userId !== null) {
        $stmt = $db->prepare(
            "INSERT INTO orders (user_id, customer_name, customer_phone, customer_address, items, total_amount, payment_method)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('issssds', $userId, $name, $phone, $address, $itemsJson, $total, $payment);
    } else {
        $stmt = $db->prepare(
            "INSERT INTO orders (customer_name, customer_phone, customer_address, items, total_amount, payment_method)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('ssssds', $name, $phone, $address, $itemsJson, $total, $payment);
    }

    if ($stmt->execute()) {
        $orderId = $db->insert_id;
        echo json_encode(['success' => true, 'order_id' => $orderId, 'message' => 'Order placed successfully!']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to place order']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
$db->close();
