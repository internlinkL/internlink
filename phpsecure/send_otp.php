<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// ─────────────────────────────────────────────
//  send_otp.php — internLink
//  Generates OTP, saves to DB, emails it.
//  Used by: login.php (admin 2FA)
//           register.php (email verification)
// ─────────────────────────────────────────────

require_once __DIR__ . '/mailer.php';

/**
 * Generate OTP, store in DB, send to user email.
 * Includes resend rate limiting — max 3 sends per 10 minutes per user.
 *
 * @param PDO    $pdo      Database connection
 * @param array  $user     User array (must have id, email, first_name)
 * @param bool   $isResend Pass true when this is a resend request
 * @return bool|string     true on success, error message string on failure
 */
function sendOtp(PDO $pdo, array $user, bool $isResend = false): bool
{
    // ── Resend rate limiting ──────────────────────────────────────────────────
    // Max 3 OTP sends per 10 minutes per user
    if ($isResend) {
        $resendKey      = 'otp_resend_count_' . $user['id'];
        $resendUntilKey = 'otp_resend_until_'  . $user['id'];

        if (!empty($_SESSION[$resendUntilKey]) && time() < $_SESSION[$resendUntilKey]) {
            $wait = $_SESSION[$resendUntilKey] - time();
            error_log("send_otp: resend blocked for user {$user['id']} — {$wait}s remaining");
            return false;
        }

        $_SESSION[$resendKey] = ($_SESSION[$resendKey] ?? 0) + 1;

        if ($_SESSION[$resendKey] >= 3) {
            $_SESSION[$resendUntilKey] = time() + 600; // 10-minute block
            $_SESSION[$resendKey]      = 0;
            error_log("send_otp: resend limit reached for user {$user['id']}");
            return false;
        }
    }

    // ── Generate OTP ──────────────────────────────────────────────────────────
    $otp     = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires = date('Y-m-d H:i:s', time() + 600); // 10 minutes

    // ── Save OTP to DB ────────────────────────────────────────────────────────
    try {
        $pdo->prepare(
            "UPDATE users SET two_fa_code = ?, two_fa_expires = ? WHERE id = ?"
        )->execute([$otp, $expires, $user['id']]);
    } catch (PDOException $e) {
        error_log('send_otp DB error: ' . $e->getMessage());
        return false;
    }

    // ── Build email ───────────────────────────────────────────────────────────
    $name    = htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8');
    $subject = 'Your internLink verification code';
    $body    = "
    <div style='font-family:sans-serif;max-width:480px;margin:auto;padding:32px;'>
        <h2 style='color:#4f8ef7;margin-bottom:8px;'>internLink</h2>
        <p style='color:#333;font-size:15px;'>Hi {$name},</p>
        <p style='color:#333;font-size:15px;'>Your verification code is:</p>
        <div style='
            font-size:36px;
            font-weight:bold;
            letter-spacing:12px;
            color:#111;
            background:#f0f4ff;
            border:1px solid #d0dcff;
            border-radius:10px;
            padding:20px 32px;
            text-align:center;
            margin:24px 0;
        '>{$otp}</div>
        <p style='color:#555;font-size:13px;'>
            This code expires in <strong>10 minutes</strong>.<br/>
            If you did not request this, you can safely ignore this email.
        </p>
        <hr style='border:none;border-top:1px solid #eee;margin:24px 0;'/>
        <p style='color:#aaa;font-size:12px;'>internLink — Connecting students with opportunities.</p>
    </div>
    ";

    return sendMail($user['email'], $user['first_name'], $subject, $body);
}
