<?php
/**
 * AyoFoods — Mail Test Script
 *
 * Visit this URL in your browser to send a test email:
 *   http://localhost/AyoFoods/backend/test_mail.php?to=your@email.com
 *
 * DELETE or RESTRICT this file before going to production!
 */

// Simple IP guard — only allow localhost
$allowedIPs = ['127.0.0.1', '::1', 'localhost'];
if (!in_array($_SERVER['REMOTE_ADDR'], $allowedIPs)) {
    http_response_code(403);
    die('Access denied.');
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';

header('Content-Type: text/html; charset=utf-8');

$to = filter_var($_GET['to'] ?? '', FILTER_VALIDATE_EMAIL);

if (!$to) {
    echo '<h2>AyoFoods Mail Test</h2>';
    echo '<p>Usage: <code>test_mail.php?to=your@email.com</code></p>';
    echo '<form method="get">';
    echo '  <label>Send test to: <input type="email" name="to" placeholder="you@example.com" style="padding:6px;width:280px"/></label>';
    echo '  <button type="submit" style="padding:6px 16px;margin-left:8px">Send Test</button>';
    echo '</form>';
    echo '<hr><h3>Current Mail Config</h3>';
    echo '<table border="1" cellpadding="6" style="border-collapse:collapse">';
    echo '<tr><th>Setting</th><th>Value</th></tr>';
    echo '<tr><td>MAIL_HOST</td><td>' . MAIL_HOST . '</td></tr>';
    echo '<tr><td>MAIL_PORT</td><td>' . MAIL_PORT . '</td></tr>';
    echo '<tr><td>MAIL_USER</td><td>' . MAIL_USER . '</td></tr>';
    echo '<tr><td>MAIL_PASS</td><td>' . (MAIL_PASS === 'abcdefghijklmnop' ? '<span style="color:red">⚠ Still using placeholder — update config.php!</span>' : str_repeat('•', strlen(MAIL_PASS))) . '</td></tr>';
    echo '<tr><td>MAIL_FROM</td><td>' . MAIL_FROM . '</td></tr>';
    echo '<tr><td>MAIL_FROM_NAME</td><td>' . MAIL_FROM_NAME . '</td></tr>';
    echo '</table>';
    exit;
}

// Send test email
$code     = '123456';
$otpBlock = buildOtpBlock($code);
$content  = "
    <p style='color:#e0e0e0;margin:0 0 8px'>This is a <strong style='color:#fff'>test email</strong> from AyoFoods.</p>
    <p style='color:#a0a0b0;margin:0 0 4px'>If you received this, your SMTP configuration is working correctly!</p>
    {$otpBlock}
    <p style='color:#a0a0b0;font-size:0.82rem;margin:0'>Sent from: <strong>" . MAIL_USER . "</strong> via " . MAIL_HOST . ":" . MAIL_PORT . "</p>
";
$html   = buildEmailTemplate('Mail Test — It Works! ✅', $content);
$result = sendMail($to, $to, 'AyoFoods Mail Test — ' . date('H:i:s'), $html);

echo '<h2>AyoFoods Mail Test</h2>';
if ($result['success']) {
    echo '<p style="color:green;font-size:1.1rem">✅ <strong>Email sent successfully</strong> to <code>' . htmlspecialchars($to) . '</code></p>';
    echo '<p>Check your inbox (and spam folder). The email should arrive within a minute.</p>';
} else {
    echo '<p style="color:red;font-size:1.1rem">❌ <strong>Failed to send email</strong></p>';
    echo '<pre style="background:#f5f5f5;padding:12px;border-radius:6px">' . htmlspecialchars($result['error']) . '</pre>';
    echo '<h3>Common fixes:</h3>';
    echo '<ul>';
    echo '<li>Make sure <code>MAIL_USER</code> is your real Gmail address in <code>config.php</code></li>';
    echo '<li>Make sure <code>MAIL_PASS</code> is a 16-character <strong>App Password</strong> (not your Gmail login password)</li>';
    echo '<li>2-Step Verification must be enabled on your Google account</li>';
    echo '<li>Generate App Password at: <a href="https://myaccount.google.com/apppasswords" target="_blank">myaccount.google.com/apppasswords</a></li>';
    echo '</ul>';
}
echo '<p><a href="test_mail.php">← Back</a></p>';
