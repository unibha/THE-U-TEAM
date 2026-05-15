<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Require the downloaded PHPMailer files
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

function sendOTP($toEmail, $otpCode) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        // User's Gmail
        $mail->Username   = 'academicmanagementsystem2026@gmail.com';
        // IMPORTANT: Must be a 16-character App Password (not your normal Gmail password)
        $mail->Password   = 'eavecoilerkwaapv'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('academicmanagementsystem2026@gmail.com', 'Academic Management System');
        $mail->addAddress($toEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP for Academic Management System';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                <h2 style='color: #4F46E5; text-align: center;'>Academic Management System</h2>
                <p>Hello,</p>
                <p>You requested to register or verify your account. Here is your One-Time Password (OTP):</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <span style='font-size: 32px; font-weight: bold; background: #f3f4f6; padding: 15px 25px; border-radius: 8px; letter-spacing: 5px; color: #333;'>{$otpCode}</span>
                </div>
                <p>This code will expire in 15 minutes.</p>
                <p>If you did not request this, please ignore this email.</p>
                <hr style='border: none; border-top: 1px solid #ddd; margin-top: 30px;' />
                <p style='font-size: 12px; color: #777; text-align: center;'>&copy; " . date('Y') . " Academic Management System</p>
            </div>
        ";
        $mail->AltBody = "Your OTP for Academic Management System is: {$otpCode}. It expires in 15 minutes.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>
