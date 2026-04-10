<?php
/**
 * mailer.php — Gmail SMTP email sender
 * Uses PHPMailer (installed via composer)
 *
 * Add to composer.json:
 *   "phpmailer/phpmailer": "^6.9"
 *
 * Set these env vars on Render:
 *   GMAIL_USER     = youraddress@gmail.com
 *   GMAIL_PASS     = your-16-char-app-password   (NOT your Gmail login password)
 *   MAIL_FROM_NAME = La Moda
 *
 * To get Gmail App Password:
 *   1. Enable 2-Factor Auth on your Google account
 *   2. Go to myaccount.google.com → Security → App Passwords
 *   3. Create app password for "Mail" → copy the 16-char code
 *   4. Paste it as GMAIL_PASS in Render environment
 */
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendOrderEmail(array $order, string $subject, string $htmlBody): bool {
    // Check PHPMailer is available
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        error_log('PHPMailer not installed. Run: composer require phpmailer/phpmailer');
        return false;
    }

  if (!isset($_ENV['GMAIL_USER']) || !isset($_ENV['GMAIL_PASS'])) {
    error_log('Mail env not configured');
    return false;
}

$gmailUser = $_ENV['GMAIL_USER'];
$gmailPass = $_ENV['GMAIL_PASS'];
$fromName  = $_ENV['MAIL_FROM_NAME'] ?? 'La Moda';

    if (!$gmailUser || !$gmailPass) {
        error_log('GMAIL_USER or GMAIL_PASS not set in environment');
        return false;
    }

    $toEmail = (string)($order['email'] ?? '');
    if (!$toEmail || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        error_log('No valid email for order notification');
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $gmailUser;
        $mail->Password   = $gmailPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom($gmailUser, $fromName);
        $mail->addAddress($toEmail, (string)($order['full_name'] ?? 'Customer'));
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags($htmlBody);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Email failed: ' . $mail->ErrorInfo);
        return false;
    }
}

function buildDeliveredEmail(array $order): string {
    $name  = htmlspecialchars($order['full_name'] ?? 'Customer');
    $items = is_array($order['cart_items'] ?? null) ? $order['cart_items'] : [];
    $total = (int)($order['item_price'] ?? 0);
    $city  = htmlspecialchars($order['city'] ?? '');

    $itemRows = '';
    foreach ($items as $item) {
        $iName  = htmlspecialchars($item['name']  ?? '');
        $iPrice = (int)($item['price'] ?? 0);
        $iQty   = (int)($item['qty']   ?? 1);
        $iSize  = $item['size'] ?? '';
        $itemRows .= "
        <tr>
          <td style='padding:10px 0;border-bottom:1px solid #f5f5f5;'>
            <strong>$iName</strong>" . ($iSize ? " <span style='color:#aaa;font-size:12px;'>($iSize)</span>" : "") . "
            <div style='font-size:12px;color:#888;'>Qty: $iQty</div>
          </td>
          <td style='padding:10px 0;border-bottom:1px solid #f5f5f5;text-align:right;'>₹" . ($iPrice * $iQty) . "</td>
        </tr>";
    }
    if (empty($itemRows)) {
        $iName = htmlspecialchars($order['item_name'] ?? 'Your order');
        $itemRows = "<tr><td style='padding:10px 0;'><strong>$iName</strong></td><td style='text-align:right;'>₹$total</td></tr>";
    }

    return "
    <!DOCTYPE html>
    <html>
    <body style='font-family:sans-serif;background:#f8f8f7;margin:0;padding:0;'>
      <div style='max-width:520px;margin:40px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);'>
        <div style='background:linear-gradient(135deg,#8B2500,#c0392b);padding:32px 28px;text-align:center;'>
          <h1 style='color:#fff;font-size:26px;margin:0;letter-spacing:1px;'>☆ La Moda ☆</h1>
          <p style='color:rgba(255,255,255,.8);margin:8px 0 0;font-size:14px;'>Your order has been delivered!</p>
        </div>
        <div style='padding:28px;'>
          <p style='font-size:16px;color:#333;'>Hi <strong>$name</strong> 👋</p>
          <p style='font-size:14px;color:#555;'>Great news! Your La Moda order has been successfully delivered to <strong>$city</strong>. We hope you love your new fashion picks! 💗</p>

          <div style='background:#f9f9f9;border-radius:10px;padding:16px;margin:20px 0;'>
            <p style='font-size:13px;font-weight:700;color:#333;margin-top:0;margin-bottom:10px;'>Order Summary</p>
            <table style='width:100%;border-collapse:collapse;font-size:14px;'>
              $itemRows
              <tr>
                <td style='padding:12px 0 0;font-weight:700;'>Total Paid</td>
                <td style='padding:12px 0 0;text-align:right;font-weight:700;color:#8B2500;'>₹$total</td>
              </tr>
            </table>
          </div>

          <p style='font-size:14px;color:#555;'>If you have any issues with your order, please reply to this email and we'll make it right.</p>
          <div style='text-align:center;margin:24px 0;'>
            <a href='https://la-moda.onrender.com' style='background:#8B2500;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:700;font-size:14px;'>Shop Again →</a>
          </div>
        </div>
        <div style='background:#fafafa;padding:16px 28px;text-align:center;border-top:1px solid #eee;'>
          <p style='font-size:11px;color:#aaa;margin:0;'>© La Moda · Wear the Moment</p>
        </div>
      </div>
    </body>
    </html>";
}