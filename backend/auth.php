<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';

// Suppress warnings from leaking into JSON output
set_error_handler(function($errno, $errstr) {
    error_log("AyoFoods auth error [$errno]: $errstr");
    return true;
});

$db     = getDB();
$action = $_GET['action'] ?? '';
$data   = json_decode(file_get_contents('php://input'), true) ?? [];

// ── Token helpers ─────────────────────────────────────────────────────────────
function buildToken(int $id, string $email): string {
    return base64_encode(json_encode(['id' => $id, 'email' => $email, 'ts' => time()]));
}

function decodeToken(string $token, mysqli $db): ?array {
    $payload = json_decode(base64_decode($token), true);
    if (!$payload || empty($payload['id']) || empty($payload['email'])) return null;
    $stmt = $db->prepare("SELECT id, name, email, phone FROM users WHERE id=? AND email=? AND is_verified=1");
    $stmt->bind_param('is', $payload['id'], $payload['email']);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function getTokenFromRequest(array $data): string {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/i', $header, $m)) return trim($m[1]);
    return $data['token'] ?? '';
}

// ── Code helper ───────────────────────────────────────────────────────────────
function generateCode(): string {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

// ── ACTIONS ───────────────────────────────────────────────────────────────────
switch ($action) {

    // ── REGISTER ─────────────────────────────────────────────────────────────
    case 'register': {
        $name  = trim($data['name']  ?? '');
        $email = strtolower(trim($data['email'] ?? ''));
        $phone = trim($data['phone'] ?? '');
        $pass  = $data['password'] ?? '';

        if (!$name || !$email || !$pass) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Name, email and password are required.']);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
            exit;
        }
        if (strlen($pass) < 6) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
            exit;
        }

        // Check existing
        $chk = $db->prepare("SELECT id, is_verified FROM users WHERE email=?");
        $chk->bind_param('s', $email);
        $chk->execute();
        $existing = $chk->get_result()->fetch_assoc();

        if ($existing && $existing['is_verified']) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Email already registered. Please log in.']);
            exit;
        }

        $hash = password_hash($pass, PASSWORD_BCRYPT);

        if ($existing) {
            // Re-register unverified account
            $upd = $db->prepare("UPDATE users SET name=?, phone=?, password_hash=? WHERE email=?");
            $upd->bind_param('ssss', $name, $phone, $hash, $email);
            $upd->execute();
        } else {
            $ins = $db->prepare("INSERT INTO users (name, email, phone, password_hash, is_verified) VALUES (?,?,?,?,0)");
            $ins->bind_param('ssss', $name, $email, $phone, $hash);
            $ins->execute();
        }

        // Invalidate old codes
        $inv = $db->prepare("UPDATE verification_codes SET used=1 WHERE email=? AND type='register'");
        $inv->bind_param('s', $email);
        $inv->execute();

        // Generate & store new code
        $code      = generateCode();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        $ins2 = $db->prepare("INSERT INTO verification_codes (email, code, type, expires_at) VALUES (?,?,'register',?)");
        $ins2->bind_param('sss', $email, $code, $expiresAt);
        $ins2->execute();

        // Send verification email
        $otpBlock = buildOtpBlock($code);
        $content  = "
            <p style='color:#e0e0e0;margin:0 0 8px'>Hi <strong style='color:#fff'>{$name}</strong>,</p>
            <p style='color:#a0a0b0;margin:0 0 4px'>Welcome to AyoFoods! Use the code below to verify your email address and activate your account.</p>
            {$otpBlock}
            <p style='color:#a0a0b0;font-size:0.82rem;margin:0'>Do not share this code with anyone. If you did not create an account, you can safely ignore this email.</p>
        ";
        $html   = buildEmailTemplate('Verify Your Email Address', $content);
        $result = sendMail($email, $name, 'Verify your AyoFoods account — ' . $code, $html);

        if (!$result['success']) {
            // Log error but don't expose it to user
            error_log('AyoFoods mail error (register): ' . $result['error']);
        }

        echo json_encode(['success' => true, 'message' => 'Account created! Check your email for the 6-digit verification code.']);
        break;
    }

    // ── VERIFY EMAIL ─────────────────────────────────────────────────────────
    case 'verify_email': {
        $email = strtolower(trim($data['email'] ?? ''));
        $code  = trim($data['code'] ?? '');

        if (!$email || strlen($code) !== 6) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email and 6-digit code are required.']);
            exit;
        }

        $stmt = $db->prepare(
            "SELECT id FROM verification_codes
             WHERE email=? AND code=? AND type='register' AND used=0 AND expires_at > NOW()
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->bind_param('ss', $email, $code);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!$row) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid or expired code. Please request a new one.']);
            exit;
        }

        $upd = $db->prepare("UPDATE verification_codes SET used=1 WHERE id=?");
        $upd->bind_param('i', $row['id']);
        $upd->execute();

        $upd2 = $db->prepare("UPDATE users SET is_verified=1 WHERE email=?");
        $upd2->bind_param('s', $email);
        $upd2->execute();

        // Send welcome email
        $stmt3 = $db->prepare("SELECT name FROM users WHERE email=?");
        $stmt3->bind_param('s', $email);
        $stmt3->execute();
        $user = $stmt3->get_result()->fetch_assoc();
        $name = $user['name'] ?? 'there';

        $content = "
            <p style='color:#e0e0e0;margin:0 0 12px'>Hi <strong style='color:#fff'>{$name}</strong>,</p>
            <p style='color:#a0a0b0;margin:0 0 20px'>Your email has been verified successfully. Your AyoFoods account is now active!</p>
            <div style='text-align:center;margin:24px 0'>
              <a href='" . SITE_URL . "/index.html' style='display:inline-block;padding:14px 32px;background:linear-gradient(135deg,#e85d04,#f48c06);color:#fff;text-decoration:none;border-radius:50px;font-weight:700;font-size:0.95rem'>
                &#127869;&#65039; Start Ordering
              </a>
            </div>
            <p style='color:#a0a0b0;font-size:0.82rem;margin:0'>Enjoy fresh Nigerian food delivered to your door.</p>
        ";
        $html = buildEmailTemplate('Welcome to AyoFoods! 🎉', $content);
        sendMail($email, $name, 'Welcome to AyoFoods — Your account is ready!', $html);

        echo json_encode(['success' => true, 'message' => 'Email verified! You can now log in.']);
        break;
    }

    // ── LOGIN ─────────────────────────────────────────────────────────────────
    case 'login': {
        $email = strtolower(trim($data['email'] ?? ''));
        $pass  = $data['password'] ?? '';

        if (!$email || !$pass) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
            exit;
        }

        $stmt = $db->prepare("SELECT id, name, email, phone, password_hash, is_verified FROM users WHERE email=?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user || !password_verify($pass, $user['password_hash'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
            exit;
        }
        if (!$user['is_verified']) {
            http_response_code(403);
            echo json_encode([
                'success'            => false,
                'message'            => 'Please verify your email before logging in.',
                'needs_verification' => true,
                'email'              => $email,
            ]);
            exit;
        }

        $token = buildToken($user['id'], $user['email']);
        echo json_encode([
            'success' => true,
            'token'   => $token,
            'user'    => ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email'], 'phone' => $user['phone']],
        ]);
        break;
    }

    // ── FORGOT PASSWORD ───────────────────────────────────────────────────────
    case 'forgot_password': {
        $email = strtolower(trim($data['email'] ?? ''));

        if (!$email) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email is required.']);
            exit;
        }

        $stmt = $db->prepare("SELECT id, name FROM users WHERE email=? AND is_verified=1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        // Always return success (prevent email enumeration)
        if (!$user) {
            echo json_encode(['success' => true, 'message' => 'If that email exists, a reset code has been sent.']);
            exit;
        }

        // Invalidate old forgot codes
        $inv = $db->prepare("UPDATE verification_codes SET used=1 WHERE email=? AND type='forgot'");
        $inv->bind_param('s', $email);
        $inv->execute();

        $code      = generateCode();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        $ins = $db->prepare("INSERT INTO verification_codes (email, code, type, expires_at) VALUES (?,?,'forgot',?)");
        $ins->bind_param('sss', $email, $code, $expiresAt);
        $ins->execute();

        $name     = $user['name'];
        $otpBlock = buildOtpBlock($code);
        $content  = "
            <p style='color:#e0e0e0;margin:0 0 8px'>Hi <strong style='color:#fff'>{$name}</strong>,</p>
            <p style='color:#a0a0b0;margin:0 0 4px'>We received a request to reset your AyoFoods password. Use the code below:</p>
            {$otpBlock}
            <p style='color:#a0a0b0;font-size:0.82rem;margin:0'>If you did not request a password reset, please ignore this email. Your account is safe.</p>
        ";
        $html   = buildEmailTemplate('Reset Your Password', $content);
        $result = sendMail($email, $name, 'Reset your AyoFoods password — ' . $code, $html);

        if (!$result['success']) {
            error_log('AyoFoods mail error (forgot_password): ' . $result['error']);
        }

        echo json_encode(['success' => true, 'message' => 'If that email exists, a reset code has been sent.']);
        break;
    }

    // ── RESET PASSWORD ────────────────────────────────────────────────────────
    case 'reset_password': {
        $email = strtolower(trim($data['email'] ?? ''));
        $code  = trim($data['code'] ?? '');
        $pass  = $data['password'] ?? '';

        if (!$email || strlen($code) !== 6 || !$pass) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email, 6-digit code and new password are required.']);
            exit;
        }
        if (strlen($pass) < 6) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
            exit;
        }

        $stmt = $db->prepare(
            "SELECT id FROM verification_codes
             WHERE email=? AND code=? AND type='forgot' AND used=0 AND expires_at > NOW()
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->bind_param('ss', $email, $code);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!$row) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid or expired code.']);
            exit;
        }

        $upd = $db->prepare("UPDATE verification_codes SET used=1 WHERE id=?");
        $upd->bind_param('i', $row['id']);
        $upd->execute();

        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $upd2 = $db->prepare("UPDATE users SET password_hash=? WHERE email=?");
        $upd2->bind_param('ss', $hash, $email);
        $upd2->execute();

        // Send confirmation email
        $stmt3 = $db->prepare("SELECT name FROM users WHERE email=?");
        $stmt3->bind_param('s', $email);
        $stmt3->execute();
        $user = $stmt3->get_result()->fetch_assoc();
        $name = $user['name'] ?? 'there';

        $content = "
            <p style='color:#e0e0e0;margin:0 0 12px'>Hi <strong style='color:#fff'>{$name}</strong>,</p>
            <p style='color:#a0a0b0;margin:0 0 20px'>Your AyoFoods password has been reset successfully. You can now log in with your new password.</p>
            <p style='color:#a0a0b0;font-size:0.82rem;margin:0'>If you did not make this change, please contact us immediately at <a href='mailto:hello@ayofoods.com' style='color:#f48c06'>hello@ayofoods.com</a>.</p>
        ";
        $html = buildEmailTemplate('Password Reset Successful', $content);
        sendMail($email, $name, 'Your AyoFoods password has been reset', $html);

        echo json_encode(['success' => true, 'message' => 'Password reset successfully! You can now log in.']);
        break;
    }

    // ── GET PROFILE ───────────────────────────────────────────────────────────
    case 'get_profile': {
        $token = getTokenFromRequest($data);
        if (!$token) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authorization token required.']);
            exit;
        }
        $user = decodeToken($token, $db);
        if (!$user) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid or expired token.']);
            exit;
        }
        echo json_encode(['success' => true, 'user' => $user]);
        break;
    }

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown action: ' . htmlspecialchars($action)]);
        break;
}

$db->close();
