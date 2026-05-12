<?php
/**
 * AyoFoods — Configuration
 *
 * ════════════════════════════════════════════════════════════
 *  GMAIL SMTP SETUP (required for verification emails)
 * ════════════════════════════════════════════════════════════
 *  1. Sign in to your Google Account
 *  2. Go to: https://myaccount.google.com/security
 *  3. Enable "2-Step Verification" (mandatory for App Passwords)
 *  4. Go to: https://myaccount.google.com/apppasswords
 *  5. App: Mail  |  Device: Other → type "AyoFoods" → click Generate
 *  6. Copy the 16-character password shown (e.g. abcdefghijklmnop)
 *  7. Paste it below as MAIL_PASS (no spaces)
 *  8. Set MAIL_USER to your Gmail address
 *
 *  NOTE: MAIL_FROM can stay as noreply@ayofoods.com — Gmail will
 *  send from your real address but display the friendly name.
 * ════════════════════════════════════════════════════════════
 */

// ── Database ──────────────────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // ← your MySQL username
define('DB_PASS', '');            // ← your MySQL password
define('DB_NAME', 'ayofoods');

define('SITE_URL', 'http://localhost/AyoFoods'); // ← change to your domain

// ── Mail ──────────────────────────────────────────────────────────────────────
define('MAIL_HOST',      'smtp.gmail.com');
define('MAIL_PORT',      587);
define('MAIL_USER',      'lukmanabdulwaris1@gmail.com');        // ← your Gmail address
define('MAIL_PASS',      'wuyd nnsh zcyq jmzn');      // ← 16-char Gmail App Password
define('MAIL_FROM',      'noreply@ayofoods.com');  // display from address
define('MAIL_FROM_NAME', 'AyoFoods');

// ── DB helper ─────────────────────────────────────────────────────────────────
function getDB(): mysqli {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        http_response_code(500);
        die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

// ── CORS & JSON headers ───────────────────────────────────────────────────────
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}
