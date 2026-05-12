<?php
/**
 * AyoFoods — Centralized Mailer
 *
 * HOW TO SET UP GMAIL SMTP:
 * 1. Go to https://myaccount.google.com/security
 * 2. Enable 2-Step Verification
 * 3. Go to https://myaccount.google.com/apppasswords
 * 4. App: Mail | Device: Other → type "AyoFoods" → Generate
 * 5. Copy the 16-character password (no spaces)
 * 6. Paste it as MAIL_PASS in config.php
 * 7. Set MAIL_USER to your Gmail address in config.php
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer-PHPMailer-3cd2a2a/src/Exception.php';
require_once __DIR__ . '/../PHPMailer-PHPMailer-3cd2a2a/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer-PHPMailer-3cd2a2a/src/SMTP.php';

/**
 * Send an HTML email via Gmail SMTP.
 * Returns ['success' => bool, 'error' => string|null]
 */
function sendMail(string $toEmail, string $toName, string $subject, string $htmlBody): array {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        $mail->addReplyTo(MAIL_USER, MAIL_FROM_NAME);

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags(str_replace(
            ['<br>', '<br/>', '<br />'], "\n", $htmlBody
        ));

        $mail->send();
        return ['success' => true, 'error' => null];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $mail->ErrorInfo];
    }
}

/**
 * Wrap content in the branded AyoFoods email layout.
 */
function buildEmailTemplate(string $title, string $content): string {
    $year    = date('Y');
    $siteUrl = defined('SITE_URL') ? SITE_URL : '#';

    return '<!DOCTYPE html>' .
    '<html lang="en"><head><meta charset="UTF-8"/>' .
    '<meta name="viewport" content="width=device-width,initial-scale=1.0"/>' .
    '<title>' . htmlspecialchars($title) . '</title></head>' .
    '<body style="margin:0;padding:0;background:#0d0d1a;font-family:\'Segoe UI\',Arial,sans-serif">' .
    '<table width="100%" cellpadding="0" cellspacing="0" style="background:#0d0d1a;padding:40px 20px">' .
    '<tr><td align="center">' .
    '<table width="100%" style="max-width:520px;background:#1a1a2e;border-radius:16px;overflow:hidden;border:1px solid rgba(255,255,255,0.08)">' .

    // Header
    '<tr><td style="background:linear-gradient(135deg,#e85d04,#f48c06);padding:32px 40px;text-align:center">' .
    '<div style="font-size:2rem;margin-bottom:8px">&#127869;&#65039;</div>' .
    '<h1 style="margin:0;color:#ffffff;font-size:1.6rem;font-weight:800;letter-spacing:-0.5px">' .
    'Ayo<span style="color:#fff3cd">Foods</span></h1>' .
    '<p style="margin:6px 0 0;color:rgba(255,255,255,0.85);font-size:0.85rem">Fresh food delivered to your door</p>' .
    '</td></tr>' .

    // Body
    '<tr><td style="padding:36px 40px;color:#e0e0e0">' .
    '<h2 style="margin:0 0 16px;color:#ffffff;font-size:1.25rem;font-weight:700">' . $title . '</h2>' .
    $content .
    '</td></tr>' .

    // Footer
    '<tr><td style="background:#0d0d1a;padding:20px 40px;text-align:center;border-top:1px solid rgba(255,255,255,0.07)">' .
    '<p style="margin:0 0 8px;color:#a0a0b0;font-size:0.78rem">&copy; ' . $year . ' AyoFoods. All rights reserved.</p>' .
    '<p style="margin:0;color:#a0a0b0;font-size:0.75rem">Lagos, Nigeria &nbsp;|&nbsp; hello@ayofoods.com</p>' .
    '</td></tr>' .

    '</table></td></tr></table></body></html>';
}

/**
 * Build the styled OTP code block.
 */
function buildOtpBlock(string $code): string {
    return
    '<div style="background:#0f3460;border-radius:12px;padding:24px;text-align:center;margin:24px 0;border:1px solid rgba(244,140,6,0.3)">' .
    '<p style="margin:0 0 8px;color:#a0a0b0;font-size:0.8rem;text-transform:uppercase;letter-spacing:1px">Your verification code</p>' .
    '<span style="font-size:2.8rem;font-weight:800;letter-spacing:0.6rem;color:#f48c06;font-family:monospace">' . htmlspecialchars($code) . '</span>' .
    '<p style="margin:10px 0 0;color:#a0a0b0;font-size:0.78rem">Expires in <strong style="color:#e0e0e0">15 minutes</strong></p>' .
    '</div>';
}
