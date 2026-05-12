<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';

set_error_handler(function($errno, $errstr) {
    error_log("AyoFoods admin error [$errno]: $errstr");
    return true;
});

$db     = getDB();
$action = $_GET['action'] ?? '';
$data   = json_decode(file_get_contents('php://input'), true) ?? [];

// ── Helper: build admin token ─────────────────────────────────────────────────
function buildAdminToken(int $id, string $email): string {
    return 'admin_' . base64_encode(json_encode(['id' => $id, 'email' => $email, 'ts' => time()]));
}

// ── Helper: validate admin token from Authorization header ────────────────────
function getAdminFromToken($db): ?array {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/Bearer\s+(.+)/i', $header, $m)) return null;
    $token = trim($m[1]);

    if (strpos($token, 'admin_') !== 0) return null;
    $raw     = substr($token, 6); // strip 'admin_' prefix
    $payload = json_decode(base64_decode($raw), true);
    if (!$payload || empty($payload['id']) || empty($payload['email'])) return null;

    $stmt = $db->prepare("SELECT id, name, email FROM admin WHERE id=? AND email=?");
    $stmt->bind_param('is', $payload['id'], $payload['email']);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

// ── Helper: require admin auth ────────────────────────────────────────────────
function requireAdmin($db): array {
    $admin = getAdminFromToken($db);
    if (!$admin) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Admin authentication required.']);
        exit;
    }
    return $admin;
}

// ── Helper: send email ────────────────────────────────────────────────────────
// sendMail() is provided by mailer.php

// ── ACTIONS ──────────────────────────────────────────────────────────────────

switch ($action) {

    // ── admin login ───────────────────────────────────────────────────────────
    case 'login': {
        $email = strtolower(trim($data['email'] ?? ''));
        $pass  = $data['password'] ?? '';

        if (!$email || !$pass) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
            exit;
        }

        $stmt = $db->prepare("SELECT id, name, email, password_hash FROM admin WHERE email=?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();

        if (!$admin || !password_verify($pass, $admin['password_hash'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
            exit;
        }

        $token = buildAdminToken($admin['id'], $admin['email']);
        echo json_encode([
            'success' => true,
            'token'   => $token,
            'admin'   => ['id' => $admin['id'], 'name' => $admin['name'], 'email' => $admin['email']],
        ]);
        break;
    }

    // ── dashboard stats ───────────────────────────────────────────────────────
    case 'dashboard': {
        requireAdmin($db);

        $stats = [];

        $r = $db->query("SELECT COUNT(*) AS total FROM orders");
        $stats['total_orders'] = (int)$r->fetch_assoc()['total'];

        $r = $db->query("SELECT COUNT(*) AS total FROM orders WHERE status='pending'");
        $stats['pending_orders'] = (int)$r->fetch_assoc()['total'];

        $r = $db->query("SELECT COALESCE(SUM(total_amount),0) AS revenue FROM orders WHERE status != 'cancelled'");
        $stats['total_revenue'] = (float)$r->fetch_assoc()['revenue'];

        $r = $db->query("SELECT COUNT(*) AS total FROM users");
        $stats['total_users'] = (int)$r->fetch_assoc()['total'];

        echo json_encode(['success' => true, 'data' => $stats]);
        break;
    }

    // ── list orders ───────────────────────────────────────────────────────────
    case 'orders': {
        requireAdmin($db);

        $status = $_GET['status'] ?? '';
        $validStatuses = ['pending','confirmed','preparing','delivered','cancelled'];

        if ($status && in_array($status, $validStatuses)) {
            $stmt = $db->prepare(
                "SELECT o.*, u.name AS user_name, u.email AS user_email
                 FROM orders o LEFT JOIN users u ON o.user_id = u.id
                 WHERE o.status=? ORDER BY o.created_at DESC"
            );
            $stmt->bind_param('s', $status);
        } else {
            $stmt = $db->prepare(
                "SELECT o.*, u.name AS user_name, u.email AS user_email
                 FROM orders o LEFT JOIN users u ON o.user_id = u.id
                 ORDER BY o.created_at DESC"
            );
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $row['items'] = json_decode($row['items'], true);
            $orders[]     = $row;
        }
        echo json_encode(['success' => true, 'data' => $orders]);
        break;
    }

    // ── update order status ───────────────────────────────────────────────────
    case 'update_order': {
        requireAdmin($db);

        $orderId = intval($data['order_id'] ?? 0);
        $status  = $data['status'] ?? '';
        $validStatuses = ['pending','confirmed','preparing','delivered','cancelled'];

        if (!$orderId || !in_array($status, $validStatuses)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Valid order_id and status are required.']);
            exit;
        }

        $stmt = $db->prepare("UPDATE orders SET status=? WHERE id=?");
        $stmt->bind_param('si', $status, $orderId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Order status updated.']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Order not found.']);
        }
        break;
    }

    // ── list users ────────────────────────────────────────────────────────────
    case 'users': {
        requireAdmin($db);

        $result = $db->query("SELECT id, name, email, phone, is_verified, created_at FROM users ORDER BY created_at DESC");
        $users  = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $users]);
        break;
    }

    // ── send notification email ───────────────────────────────────────────────
    case 'send_notification': {
        requireAdmin($db);

        $toEmail = trim($data['email']   ?? '');
        $subject = trim($data['subject'] ?? '');
        $message = trim($data['message'] ?? '');

        if (!$toEmail || !$subject || !$message) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email, subject and message are required.']);
            exit;
        }
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
            exit;
        }

        $content = "<p style=\"color:#e0e0e0;margin:0 0 16px\">" . nl2br(htmlspecialchars($message)) . "</p>";
        $html    = buildEmailTemplate(htmlspecialchars($subject), $content);
        $result  = sendMail($toEmail, $toEmail, $subject, $html);
        echo json_encode(['success' => $result['success'], 'message' => $result['success'] ? 'Notification sent.' : 'Failed to send: ' . $result['error']]);
        break;
    }

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        break;
}

$db->close();
